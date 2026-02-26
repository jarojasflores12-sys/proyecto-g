<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Reports {
    private const REPORT_RATE_LIMIT_MAX = 10;
    private const REPORT_RATE_LIMIT_WINDOW = 60;

    public static function init(): void {
        add_action('admin_post_catgame_report_submission', [__CLASS__, 'handle_report_submission']);
        add_action('wp_ajax_catgame_report_submission', [__CLASS__, 'handle_report_submission']);
        add_action('admin_post_catgame_moderate_report', [__CLASS__, 'handle_moderate_report']);
    }

    public static function endpoint_report_url(): string {
        return admin_url('admin-ajax.php');
    }

    public static function can_user_participate(int $user_id, string &$message = ''): bool {
        if ($user_id <= 0) {
            $message = 'Debes iniciar sesión para continuar.';
            return false;
        }

        if (self::has_active_grave_strike($user_id)) {
            $message = 'Tu cuenta del juego está bloqueada por una sanción grave activa.';
            return false;
        }

        $active = self::active_strikes_count($user_id);
        if ($active < 3) {
            return true;
        }

        $message = 'Tu cuenta del juego está temporalmente bloqueada por sanciones activas.';
        return false;
    }

    public static function active_strikes_count(int $user_id): int {
        if ($user_id <= 0) {
            return 0;
        }

        global $wpdb;
        $table = CatGame_DB::table('strikes');
        $now = current_time('mysql');
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND expires_at >= %s", $user_id, $now));
    }

    public static function add_strike(int $user_id, string $kind, string $severity, string $reason_code): void {
        if ($user_id <= 0) {
            return;
        }

        global $wpdb;
        $table = CatGame_DB::table('strikes');
        $created_at = current_time('mysql');
        $expires_at = gmdate('Y-m-d H:i:s', strtotime($created_at . ' +1 year'));
        $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'kind' => $kind,
                'severity' => $severity,
                'reason_code' => $reason_code,
                'created_at' => $created_at,
                'expires_at' => $expires_at,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
    }

    public static function create_notification(int $user_id, string $message): void {
        if ($user_id <= 0 || trim($message) === '') {
            return;
        }

        global $wpdb;
        $table = CatGame_DB::table('notifications');
        $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'message' => sanitize_text_field($message),
                'created_at' => current_time('mysql'),
                'read_at' => null,
            ],
            ['%d', '%s', '%s', '%s']
        );
    }

    public static function list_notifications(int $user_id, int $limit = 20): array {
        if ($user_id <= 0) {
            return [];
        }

        global $wpdb;
        $table = CatGame_DB::table('notifications');
        $limit = max(1, (int) $limit);
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d", $user_id, $limit), ARRAY_A);
    }

    public static function mark_notifications_read(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        global $wpdb;
        $table = CatGame_DB::table('notifications');
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET read_at = %s WHERE user_id = %d AND read_at IS NULL", current_time('mysql'), $user_id));
    }

    public static function has_pending_reports(int $submission_id): bool {
        if ($submission_id <= 0) {
            return false;
        }

        global $wpdb;
        $table = CatGame_DB::table('reports');
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE submission_id = %d AND status = 'pending'", $submission_id));
        return $count > 0;
    }

    public static function report_button_html(array $submission, int $current_user_id): string {
        if ($current_user_id <= 0) {
            return '';
        }

        $submission_id = (int) ($submission['id'] ?? 0);
        $author_id = (int) ($submission['user_id'] ?? 0);
        $is_hidden = !empty($submission['is_hidden']);
        if ($submission_id <= 0 || $author_id <= 0 || $author_id === $current_user_id || $is_hidden) {
            return '';
        }

        $nonce = wp_create_nonce('catgame_nonce');
        return '<button type="button" class="secondary cg-report-btn" data-report-btn="1" data-submission-id="' . (int) $submission_id . '" data-nonce="' . esc_attr($nonce) . '">Reportar</button>';
    }

    public static function handle_report_submission(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión para reportar.'], 401);
        }

        if (!check_ajax_referer('catgame_nonce', 'nonce', false)) {
            error_log('catgame_report_submission: invalid nonce');
            wp_send_json_error(['message' => 'Solicitud inválida.'], 403);
        }

        $user_id = get_current_user_id();
        $block_message = '';
        if (!self::can_user_participate($user_id, $block_message)) {
            wp_send_json_error(['message' => $block_message], 403);
        }

        if (!self::within_report_rate_limit($user_id)) {
            error_log('catgame_report_submission: rate limit user ' . (string) $user_id);
            wp_send_json_error(['message' => 'Has alcanzado el límite de reportes. Intenta nuevamente en un minuto.'], 429);
        }

        $submission_id = (int) ($_POST['submission_id'] ?? 0);
        $reason = sanitize_key((string) wp_unslash($_POST['reason'] ?? ''));
        $detail = sanitize_textarea_field((string) wp_unslash($_POST['detail'] ?? ''));
        if (!in_array($reason, ['not_pet', 'human', 'inappropriate', 'other'], true)) {
            wp_send_json_error(['message' => 'Motivo inválido.'], 400);
        }

        if (function_exists('mb_substr')) {
            $detail = mb_substr($detail, 0, 250);
        } else {
            $detail = substr($detail, 0, 250);
        }

        $submission = CatGame_Submissions::get_submission($submission_id);
        if (!$submission) {
            wp_send_json_error(['message' => 'La publicación no existe.'], 404);
        }

        $author_id = (int) ($submission['user_id'] ?? 0);
        if ($author_id === $user_id) {
            wp_send_json_error(['message' => 'No puedes reportar tu propia publicación.'], 400);
        }

        global $wpdb;
        $reports_table = CatGame_DB::table('reports');
        $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$reports_table} WHERE submission_id = %d AND reported_user_id = %d LIMIT 1", $submission_id, $user_id));
        if ($exists > 0) {
            wp_send_json_error(['message' => 'Ya reportaste esta publicación.'], 409);
        }

        $inserted = $wpdb->insert(
            $reports_table,
            [
                'submission_id' => $submission_id,
                'reported_user_id' => $user_id,
                'reason' => $reason,
                'detail' => $detail,
                'created_at' => current_time('mysql'),
                'status' => 'pending',
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            error_log('catgame_report_submission: insert failed for submission ' . (string) $submission_id . ' user ' . (string) $user_id);
            wp_send_json_error(['message' => 'No se pudo registrar el reporte.'], 500);
        }

        CatGame_Submissions::hide_submission($submission_id, 'report_pending');

        wp_send_json_success(['message' => 'Reporte enviado. Esta publicación quedó en revisión.', 'submission_id' => $submission_id]);
    }

    public static function handle_moderate_report(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('catgame_moderate_report');

        $report_id = (int) ($_POST['report_id'] ?? 0);
        $resolution = sanitize_key((string) wp_unslash($_POST['resolution'] ?? ''));
        $severity = sanitize_key((string) wp_unslash($_POST['severity'] ?? ''));
        if ($report_id <= 0 || !in_array($resolution, ['restored', 'removed', 'false_report'], true)) {
            wp_safe_redirect(admin_url('admin.php?page=catgame-moderation'));
            exit;
        }

        global $wpdb;
        $reports_table = CatGame_DB::table('reports');
        $report = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$reports_table} WHERE id = %d", $report_id), ARRAY_A);
        if (!$report) {
            wp_safe_redirect(admin_url('admin.php?page=catgame-moderation'));
            exit;
        }

        $submission_id = (int) ($report['submission_id'] ?? 0);
        $submission = CatGame_Submissions::get_submission($submission_id);
        $author_id = (int) ($submission['user_id'] ?? 0);
        $reporter_id = (int) ($report['reported_user_id'] ?? 0);

        if ($resolution === 'restored') {
            CatGame_Submissions::unhide_submission($submission_id);
            self::create_notification($author_id, 'Tu publicación fue restaurada por moderación.');
        } elseif ($resolution === 'removed') {
            if (!in_array($severity, ['leve', 'moderado', 'grave'], true)) {
                $severity = 'leve';
            }
            CatGame_Submissions::hide_submission($submission_id, 'removed');
            self::add_strike($author_id, 'author', $severity, 'removed_' . $severity);
            self::create_notification($author_id, 'Tu publicación fue eliminada por moderación (' . $severity . ').');
            if ($severity === 'grave') {
                CatGame_Submissions::hide_user_submissions($author_id, 'removed');
            }
        } elseif ($resolution === 'false_report') {
            self::add_strike($reporter_id, 'reporter', 'leve', 'false_report');
            if (!self::has_pending_reports($submission_id)) {
                CatGame_Submissions::unhide_submission($submission_id);
            }
        }

        $wpdb->update(
            $reports_table,
            [
                'status' => 'resolved',
                'resolved_at' => current_time('mysql'),
                'resolution' => $resolution,
                'admin_user_id' => get_current_user_id(),
                'severity' => ($resolution === 'removed') ? $severity : null,
            ],
            ['id' => $report_id],
            ['%s', '%s', '%s', '%d', '%s'],
            ['%d']
        );

        if ($resolution === 'restored' || $resolution === 'false_report') {
            $has_more = self::has_pending_reports($submission_id);
            if (!$has_more) {
                CatGame_Submissions::unhide_submission($submission_id);
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=catgame-moderation'));
        exit;
    }

    private static function within_report_rate_limit(int $user_id): bool {
        if ($user_id <= 0) {
            return false;
        }

        $key = 'catgame_report_rl_' . $user_id;
        $now = time();
        $bucket = get_transient($key);
        if (!is_array($bucket)) {
            $bucket = ['count' => 0, 'reset_at' => $now + self::REPORT_RATE_LIMIT_WINDOW];
        }

        $count = (int) ($bucket['count'] ?? 0);
        $reset_at = (int) ($bucket['reset_at'] ?? ($now + self::REPORT_RATE_LIMIT_WINDOW));
        if ($reset_at <= $now) {
            $count = 0;
            $reset_at = $now + self::REPORT_RATE_LIMIT_WINDOW;
        }

        if ($count >= self::REPORT_RATE_LIMIT_MAX) {
            return false;
        }

        $count++;
        set_transient($key, ['count' => $count, 'reset_at' => $reset_at], self::REPORT_RATE_LIMIT_WINDOW);
        return true;
    }
}
