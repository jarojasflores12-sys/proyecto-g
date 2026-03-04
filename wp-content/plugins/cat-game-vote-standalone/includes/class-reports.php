<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Reports {
    private const REPORT_RATE_LIMIT_MAX = 10;
    private const REPORT_RATE_LIMIT_WINDOW = 60;
    private const UPLOAD_BAN_META_KEY = 'catgame_upload_banned_until';
    private const NOTIFICATIONS_META_KEY = 'catgame_notifications';

    public static function init(): void {
        add_action('admin_post_catgame_report_submission', [__CLASS__, 'handle_report_submission']);
        add_action('wp_ajax_catgame_report_submission', [__CLASS__, 'handle_report_submission']);
        add_action('admin_post_catgame_moderate_report', [__CLASS__, 'handle_moderate_report']);
        add_action('wp_ajax_catgame_get_notifications', [__CLASS__, 'handle_get_notifications']);
        add_action('wp_ajax_catgame_mark_notifications_read', [__CLASS__, 'handle_mark_notifications_read']);
    }

    public static function endpoint_report_url(): string {
        return admin_url('admin-ajax.php');
    }

    public static function can_user_participate(int $user_id, string &$message = ''): bool {
        if ($user_id <= 0) {
            $message = 'Debes iniciar sesión para continuar.';
            return false;
        }

        return true;
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


    public static function active_strikes_count_by_kind(int $user_id, string $kind): int {
        if ($user_id <= 0 || !in_array($kind, ['author', 'reporter'], true)) {
            return 0;
        }

        global $wpdb;
        $table = CatGame_DB::table('strikes');
        $now = current_time('mysql');
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND kind = %s AND expires_at >= %s", $user_id, $kind, $now));
    }

    public static function add_strike(int $user_id, string $kind, string $severity, string $reason_code, int $admin_user_id = 0): void {
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
                'admin_user_id' => $admin_user_id > 0 ? $admin_user_id : null,
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%d']
        );
    }

    public static function get_upload_ban_until(int $user_id): int {
        if ($user_id <= 0) {
            return 0;
        }

        $value = get_user_meta($user_id, self::UPLOAD_BAN_META_KEY, true);
        if ($value === '' || $value === null) {
            return 0;
        }

        return is_numeric($value) ? (int) $value : (int) strtotime((string) $value);
    }

    public static function is_upload_banned(int $user_id): bool {
        $until = self::get_upload_ban_until($user_id);
        return $until > time();
    }

    public static function set_upload_ban_until(int $user_id, int $until_ts): void {
        if ($user_id <= 0) {
            return;
        }

        if ($until_ts <= 0) {
            delete_user_meta($user_id, self::UPLOAD_BAN_META_KEY);
            return;
        }

        update_user_meta($user_id, self::UPLOAD_BAN_META_KEY, $until_ts);
    }


    private static function moderation_reason_label(string $reason_code): string {
        $map = [
            'not_pet' => 'No es una mascota',
            'human' => 'Aparece una persona',
            'inappropriate' => 'Contenido inapropiado',
            'other' => 'Otro',
        ];

        return $map[$reason_code] ?? 'Revisión de moderación';
    }

    private static function submission_label(?array $submission, int $submission_id): string {
        if (is_array($submission) && class_exists('CatGame_Submissions')) {
            $title = trim((string) CatGame_Submissions::title_label($submission));
            if ($title !== '') {
                return $title;
            }
        }

        return 'Publicación #' . (int) $submission_id;
    }

    private static function debug_log(string $message): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('catgame_notifications: ' . $message);
        }
    }

    public static function add_notification(int $user_id, string $type, string $title, string $message, string $event_key = ""): void {
        if ($user_id <= 0 || trim($title) === '' || trim($message) === '') {
            return;
        }

        $items = self::get_notifications($user_id, 200, false);
        $event_key = sanitize_key($event_key);
        if ($event_key !== '') {
            foreach ($items as $item) {
                if (sanitize_key((string) ($item['event_key'] ?? '')) === $event_key) {
                    self::debug_log('skip duplicate event ' . $event_key);
                    return;
                }
            }
        }

        array_unshift($items, [
            'id' => wp_generate_uuid4(),
            'event_key' => $event_key,
            'type' => sanitize_key($type),
            'title' => sanitize_text_field($title),
            'message' => sanitize_textarea_field($message),
            'created_at' => gmdate('c'),
            'read_at' => null,
        ]);

        $items = array_slice($items, 0, 200);
        update_user_meta($user_id, self::NOTIFICATIONS_META_KEY, $items);
    }

    public static function create_notification(int $user_id, string $message): void {
        self::add_notification($user_id, 'system', 'Actualización', $message);
    }

    public static function get_notifications(int $user_id, int $limit = 50, bool $sort = true): array {
        if ($user_id <= 0) {
            return [];
        }

        $items = get_user_meta($user_id, self::NOTIFICATIONS_META_KEY, true);
        if (!is_array($items)) {
            return [];
        }

        if ($sort) {
            usort($items, static function (array $a, array $b): int {
                return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
            });
        }

        $limit = max(1, (int) $limit);
        return array_slice($items, 0, $limit);
    }

    public static function unread_notifications_count(int $user_id): int {
        $items = self::get_notifications($user_id, 200);
        $count = 0;
        foreach ($items as $item) {
            if (empty($item['read_at'])) {
                $count++;
            }
        }
        return $count;
    }

    public static function list_notifications(int $user_id, int $limit = 20): array {
        return self::get_notifications($user_id, $limit);
    }

    public static function mark_all_read(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        $items = self::get_notifications($user_id, 200, false);
        if (!$items) {
            return;
        }

        $now = gmdate('c');
        foreach ($items as &$item) {
            if (empty($item['read_at'])) {
                $item['read_at'] = $now;
            }
        }
        unset($item);

        update_user_meta($user_id, self::NOTIFICATIONS_META_KEY, $items);
    }

    public static function mark_notifications_read(int $user_id): void {
        self::mark_all_read($user_id);
    }

    public static function handle_get_notifications(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión.'], 401);
        }

        if (!check_ajax_referer('catgame_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Solicitud inválida.'], 403);
        }

        $user_id = get_current_user_id();
        $items = self::get_notifications($user_id, 50);
        wp_send_json_success([
            'items' => $items,
            'unread_count' => self::unread_notifications_count($user_id),
        ]);
    }

    public static function handle_mark_notifications_read(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión.'], 401);
        }

        if (!check_ajax_referer('catgame_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Solicitud inválida.'], 403);
        }

        $user_id = get_current_user_id();
        self::mark_all_read($user_id);
        wp_send_json_success(['unread_count' => 0]);
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
        self::add_notification($user_id, 'moderation', 'Reporte recibido', 'Recibimos tu reporte y lo estamos revisando.', 'report_received_' . (int) $submission_id);

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

        if (($report['status'] ?? '') === 'resolved') {
            self::debug_log('skip already resolved report ' . (string) $report_id);
            wp_safe_redirect(admin_url('admin.php?page=catgame-moderation'));
            exit;
        }

        $submission_id = (int) ($report['submission_id'] ?? 0);
        $submission = CatGame_Submissions::get_submission($submission_id);
        $author_id = (int) ($submission['user_id'] ?? 0);
        $reporter_id = (int) ($report['reported_user_id'] ?? 0);
        $admin_user_id = get_current_user_id();

        $report_reason = sanitize_key((string) ($report['reason'] ?? 'other'));
        $reason_label = self::moderation_reason_label($report_reason);
        $submission_label = self::submission_label($submission, $submission_id);

        if ($resolution === 'restored') {
            CatGame_Submissions::unhide_submission($submission_id);
            self::add_notification(
                $author_id,
                'moderation',
                'Reporte revisado',
                'Revisamos el reporte sobre tu publicación “' . $submission_label . '”. No detectamos infracción. Tu publicación sigue visible.',
                'moderation_' . (int) $report_id . '_restored'
            );
        } elseif ($resolution === 'removed') {
            if (!in_array($severity, ['leve', 'moderado', 'grave'], true)) {
                $severity = 'leve';
            }

            CatGame_Submissions::hide_submission($submission_id, 'removed');
            self::add_strike($author_id, 'author', $severity, 'submission_removed', $admin_user_id);

            self::add_notification(
                $author_id,
                'moderation',
                'Publicación eliminada',
                'Tu publicación “' . $submission_label . '” fue eliminada por incumplir las reglas: ' . $reason_label . ' (gravedad: ' . $severity . ').',
                'moderation_' . (int) $report_id . '_removed'
            );

            $author_strikes = self::active_strikes_count_by_kind($author_id, 'author');
            self::add_notification(
                $author_id,
                'strike',
                'Sanción aplicada',
                'Recibiste un strike (' . (int) $author_strikes . '/3) por: ' . $reason_label . '.',
                'moderation_' . (int) $report_id . '_strike'
            );

            if ($report_reason === 'human') {
                self::set_upload_ban_until($author_id, time() + (3 * DAY_IN_SECONDS));
                self::add_notification(
                    $author_id,
                    'suspension',
                    'Cuenta suspendida',
                    'Tu cuenta fue suspendida por 3 días por: ' . $reason_label . '. Durante la suspensión puedes reaccionar, pero no subir fotos.',
                    'moderation_' . (int) $report_id . '_suspension'
                );
            }

            if ($severity === 'grave') {
                CatGame_Submissions::hide_user_submissions($author_id, 'removed');
                self::set_upload_ban_until($author_id, time() + YEAR_IN_SECONDS);
                self::add_notification(
                    $author_id,
                    'account',
                    'Cuenta eliminada',
                    'Tu cuenta fue eliminada por infracción grave: ' . $reason_label . '. Si crees que es un error, contacta al administrador.',
                    'moderation_' . (int) $report_id . '_account'
                );
            } elseif ($author_strikes >= 3) {
                self::set_upload_ban_until($author_id, time() + (7 * DAY_IN_SECONDS));
                self::add_notification(
                    $author_id,
                    'suspension',
                    'Cuenta suspendida',
                    'Tu cuenta fue suspendida por 7 días por acumulación de strikes. Durante la suspensión puedes reaccionar, pero no subir fotos.',
                    'moderation_' . (int) $report_id . '_suspension7'
                );
            }
        } elseif ($resolution === 'false_report') {
            self::add_strike($reporter_id, 'reporter', 'leve', 'false_report', $admin_user_id);
            if (self::active_strikes_count($reporter_id) >= 3) {
                self::set_upload_ban_until($reporter_id, time() + (7 * DAY_IN_SECONDS));
                self::add_notification(
                    $reporter_id,
                    'suspension',
                    'Cuenta suspendida',
                    'Tu cuenta fue suspendida por 7 días por acumulación de strikes. Durante la suspensión puedes reaccionar, pero no subir fotos.',
                    'moderation_' . (int) $report_id . '_reporter_suspension7'
                );
            }
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
                'admin_user_id' => $admin_user_id,
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
