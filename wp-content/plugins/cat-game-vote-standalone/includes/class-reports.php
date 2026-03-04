<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Reports {
    private const REPORT_RATE_LIMIT_MAX = 10;
    private const REPORT_RATE_LIMIT_WINDOW = 60;
    private const UPLOAD_BAN_META_KEY = 'catgame_upload_banned_until';
    private const NOTIFICATIONS_META_KEY = 'catgame_notifications';
    private const APPEAL_WINDOW_HOURS = 72;
    private const APPEAL_RATE_LIMIT_MAX = 3;

    public static function init(): void {
        add_action('admin_post_catgame_report_submission', [__CLASS__, 'handle_report_submission']);
        add_action('wp_ajax_catgame_report_submission', [__CLASS__, 'handle_report_submission']);
        add_action('admin_post_catgame_moderate_report', [__CLASS__, 'handle_moderate_report']);
        add_action('wp_ajax_catgame_get_notifications', [__CLASS__, 'handle_get_notifications']);
        add_action('wp_ajax_catgame_mark_notifications_read', [__CLASS__, 'handle_mark_notifications_read']);
        add_action('wp_ajax_catgame_submit_appeal', [__CLASS__, 'handle_submit_appeal']);
        add_action('admin_post_catgame_decide_appeal', [__CLASS__, 'handle_decide_appeal']);
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

    private static function moderation_action_from_resolution(string $resolution): string {
        if ($resolution === 'restored') {
            return 'restore';
        }

        if ($resolution === 'removed') {
            return 'delete';
        }

        return 'restore';
    }

    private static function moderation_severity_from_report(?string $severity): string {
        $normalized = sanitize_key((string) $severity);
        if ($normalized === 'moderado') {
            return 'moderada';
        }

        if (!in_array($normalized, ['leve', 'moderada', 'grave'], true)) {
            return 'leve';
        }

        return $normalized;
    }

    private static function save_moderation_action(int $submission_id, int $user_id, string $action, string $severity, string $reason, string $detail, int $admin_user_id): void {
        if ($submission_id <= 0 || $user_id <= 0) {
            return;
        }

        global $wpdb;
        $table = CatGame_DB::table('moderation_actions');

        $current = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$table} WHERE submission_id = %d AND is_current = 1 ORDER BY id DESC LIMIT 1", $submission_id), ARRAY_A);
        if (is_array($current) && !empty($current['id'])) {
            $wpdb->update(
                $table,
                ['is_current' => 0],
                ['id' => (int) $current['id']],
                ['%d'],
                ['%d']
            );
        }

        $wpdb->insert(
            $table,
            [
                'submission_id' => $submission_id,
                'user_id' => $user_id,
                'action' => $action,
                'severity' => $severity,
                'reason' => $reason,
                'detail' => $detail,
                'decided_by' => $admin_user_id,
                'decided_at' => current_time('mysql'),
                'prev_action_id' => is_array($current) ? (int) ($current['id'] ?? 0) : null,
                'is_current' => 1,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d']
        );
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

        self::save_moderation_action(
            $submission_id,
            $author_id,
            self::moderation_action_from_resolution($resolution),
            self::moderation_severity_from_report($severity),
            $report_reason,
            sanitize_text_field((string) ($report['detail'] ?? '')),
            $admin_user_id
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

    public static function appeal_button_html(array $submission, int $current_user_id): string {
        if ($current_user_id <= 0) {
            return '';
        }

        $submission_id = (int) ($submission['id'] ?? 0);
        $author_id = (int) ($submission['user_id'] ?? 0);
        if ($submission_id <= 0 || $author_id !== $current_user_id) {
            return '';
        }

        $appeal = self::get_submission_appeal($submission_id);
        if (is_array($appeal) && (string) ($appeal['status'] ?? '') === 'pending') {
            return '<p class="cg-appeal-state">Apelación pendiente</p>';
        }

        $message = '';
        $status_code = 0;
        if (!self::can_submit_appeal($submission_id, $current_user_id, $message, $status_code)) {
            if ($message === '') {
                return '';
            }

            return '<p class="cg-appeal-state">' . esc_html($message) . '</p>';
        }

        return '<button type="button" class="secondary cg-appeal-btn" data-appeal-btn="1" data-submission-id="' . (int) $submission_id . '">Apelar</button>';
    }

    public static function list_pending_appeals(int $limit = 100): array {
        global $wpdb;
        $appeals_table = CatGame_DB::table('appeals');
        $subs_table = CatGame_DB::table('submissions');
        $actions_table = CatGame_DB::table('moderation_actions');
        $limit = max(1, min(200, (int) $limit));

        $sql = $wpdb->prepare(
            "SELECT a.*, s.title, s.attachment_id, ma.action AS current_action, ma.severity AS current_severity, ma.reason AS current_reason, ma.decided_at AS moderation_decided_at
            FROM {$appeals_table} a
            INNER JOIN {$subs_table} s ON s.id = a.submission_id
            LEFT JOIN {$actions_table} ma ON ma.submission_id = a.submission_id AND ma.is_current = 1
            WHERE a.status = 'pending'
            ORDER BY a.created_at ASC
            LIMIT %d",
            $limit
        );

        return $wpdb->get_results($sql, ARRAY_A);
    }

    public static function handle_submit_appeal(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión para apelar.'], 401);
        }

        if (!check_ajax_referer('catgame_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Solicitud inválida.'], 403);
        }

        $user_id = get_current_user_id();
        $submission_id = (int) ($_POST['submission_id'] ?? 0);
        $message = sanitize_textarea_field((string) wp_unslash($_POST['message'] ?? ''));

        if (function_exists('mb_substr')) {
            $message = mb_substr($message, 0, 500);
        } else {
            $message = substr($message, 0, 500);
        }

        if ($submission_id <= 0 || trim($message) === '') {
            wp_send_json_error(['message' => 'Completa el mensaje de apelación.'], 400);
        }

        if (self::count_user_appeals_last_24h($user_id) >= self::APPEAL_RATE_LIMIT_MAX) {
            wp_send_json_error(['message' => 'Has alcanzado el límite de apelaciones por hoy'], 429);
        }

        $validation_message = '';
        $status_code = 400;
        if (!self::can_submit_appeal($submission_id, $user_id, $validation_message, $status_code)) {
            wp_send_json_error(['message' => $validation_message !== '' ? $validation_message : 'No se puede apelar este caso.'], $status_code);
        }

        global $wpdb;
        $table = CatGame_DB::table('appeals');
        $inserted = $wpdb->insert(
            $table,
            [
                'submission_id' => $submission_id,
                'user_id' => $user_id,
                'message' => $message,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        if ($inserted === false) {
            wp_send_json_error(['message' => 'No se pudo enviar la apelación.'], 500);
        }

        wp_send_json_success(['message' => 'Apelación enviada (pendiente)']);
    }

    public static function handle_decide_appeal(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('catgame_decide_appeal');

        $appeal_id = (int) ($_POST['appeal_id'] ?? 0);
        $decision = sanitize_key((string) wp_unslash($_POST['decision'] ?? ''));
        $admin_note = sanitize_textarea_field((string) wp_unslash($_POST['admin_note'] ?? ''));
        if ($appeal_id <= 0 || !in_array($decision, ['accepted', 'rejected'], true)) {
            wp_safe_redirect(admin_url('admin.php?page=catgame-moderation&appeal_notice=invalid'));
            exit;
        }

        global $wpdb;
        $appeals_table = CatGame_DB::table('appeals');
        $appeal = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$appeals_table} WHERE id = %d", $appeal_id), ARRAY_A);
        if (!is_array($appeal) || (string) ($appeal['status'] ?? '') !== 'pending') {
            wp_safe_redirect(admin_url('admin.php?page=catgame-moderation&appeal_notice=invalid'));
            exit;
        }

        $submission_id = (int) ($appeal['submission_id'] ?? 0);
        $user_id = (int) ($appeal['user_id'] ?? 0);
        $admin_user_id = get_current_user_id();

        if ($decision === 'accepted') {
            self::accept_appeal($submission_id, $user_id, $admin_user_id);
        }

        $wpdb->update(
            $appeals_table,
            [
                'status' => $decision,
                'decided_at' => current_time('mysql'),
                'decided_by' => $admin_user_id,
                'admin_note' => $admin_note,
            ],
            ['id' => $appeal_id],
            ['%s', '%s', '%d', '%s'],
            ['%d']
        );

        if ($decision === 'accepted') {
            self::add_notification(
                $user_id,
                'appeal',
                'Apelación aceptada',
                'Apelación aceptada: tu publicación fue restaurada.',
                'appeal:' . $appeal_id . ':accepted'
            );
        } else {
            $suffix = $admin_note !== '' ? ' Motivo: ' . $admin_note : '';
            self::add_notification(
                $user_id,
                'appeal',
                'Apelación rechazada',
                'Apelación rechazada.' . $suffix,
                'appeal:' . $appeal_id . ':rejected'
            );
        }

        wp_safe_redirect(admin_url('admin.php?page=catgame-moderation&appeal_notice=' . $decision));
        exit;
    }

    private static function can_submit_appeal(int $submission_id, int $user_id, string &$message = '', int &$status_code = 400): bool {
        $message = '';
        $status_code = 400;

        if ($submission_id <= 0 || $user_id <= 0) {
            $message = 'Datos inválidos para apelar.';
            return false;
        }

        $submission = CatGame_Submissions::get_submission($submission_id);
        if (!$submission || (int) ($submission['user_id'] ?? 0) !== $user_id) {
            $message = 'Solo el dueño de la publicación puede apelar.';
            $status_code = 403;
            return false;
        }

        if (self::get_submission_appeal($submission_id)) {
            $message = 'Ya existe una apelación para esta publicación.';
            $status_code = 409;
            return false;
        }

        $current_action = self::get_current_moderation_action($submission_id);
        if (!is_array($current_action)) {
            $message = 'No existe una moderación activa para esta publicación.';
            return false;
        }

        $action_name = sanitize_key((string) ($current_action['action'] ?? ''));
        if (!in_array($action_name, ['delete', 'strike', 'suspend_3d'], true)) {
            $message = 'Esta moderación no admite apelación.';
            return false;
        }

        $decided_at = strtotime((string) ($current_action['decided_at'] ?? ''));
        if ($decided_at <= 0) {
            $message = 'No se pudo validar la fecha de moderación.';
            return false;
        }

        if ((time() - $decided_at) > (self::APPEAL_WINDOW_HOURS * HOUR_IN_SECONDS)) {
            $message = 'La ventana de apelación expiró (72h).';
            return false;
        }

        return true;
    }

    private static function count_user_appeals_last_24h(int $user_id): int {
        if ($user_id <= 0) {
            return 0;
        }

        global $wpdb;
        $table = CatGame_DB::table('appeals');
        $threshold = gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS);
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND created_at >= %s", $user_id, $threshold));
    }

    private static function get_submission_appeal(int $submission_id): ?array {
        if ($submission_id <= 0) {
            return null;
        }

        global $wpdb;
        $table = CatGame_DB::table('appeals');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE submission_id = %d ORDER BY id DESC LIMIT 1", $submission_id), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    private static function get_current_moderation_action(int $submission_id): ?array {
        if ($submission_id <= 0) {
            return null;
        }

        global $wpdb;
        $table = CatGame_DB::table('moderation_actions');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE submission_id = %d AND is_current = 1 ORDER BY id DESC LIMIT 1", $submission_id), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    private static function accept_appeal(int $submission_id, int $user_id, int $admin_user_id): void {
        $current_action = self::get_current_moderation_action($submission_id);
        if (!is_array($current_action)) {
            CatGame_Submissions::unhide_submission($submission_id);
            return;
        }

        $action_name = sanitize_key((string) ($current_action['action'] ?? ''));
        $current_action_id = (int) ($current_action['id'] ?? 0);
        $decided_at = (string) ($current_action['decided_at'] ?? '');

        CatGame_Submissions::unhide_submission($submission_id);

        if (in_array($action_name, ['suspend_3d', 'delete'], true)) {
            self::set_upload_ban_until($user_id, 0);
            self::remove_last_author_strike($user_id, $decided_at);
        }

        if ($action_name === 'strike') {
            self::remove_last_author_strike($user_id, $decided_at);
        }

        global $wpdb;
        $table = CatGame_DB::table('moderation_actions');
        if ($current_action_id > 0) {
            $wpdb->update($table, ['is_current' => 0], ['id' => $current_action_id], ['%d'], ['%d']);
        }

        $wpdb->insert(
            $table,
            [
                'submission_id' => $submission_id,
                'user_id' => $user_id,
                'action' => 'restore',
                'severity' => 'leve',
                'reason' => 'other',
                'detail' => 'Apelación aceptada',
                'decided_by' => $admin_user_id,
                'decided_at' => current_time('mysql'),
                'prev_action_id' => $current_action_id > 0 ? $current_action_id : null,
                'is_current' => 1,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d']
        );
    }

    private static function remove_last_author_strike(int $user_id, string $decided_at = ''): void {
        if ($user_id <= 0) {
            return;
        }

        global $wpdb;
        $table = CatGame_DB::table('strikes');
        $sql = "SELECT id FROM {$table} WHERE user_id = %d AND kind = 'author' ORDER BY created_at DESC, id DESC LIMIT 1";
        $params = [$user_id];
        if ($decided_at !== '') {
            $sql = "SELECT id FROM {$table} WHERE user_id = %d AND kind = 'author' AND created_at >= %s ORDER BY created_at DESC, id DESC LIMIT 1";
            $params[] = $decided_at;
        }

        $strike_id = (int) $wpdb->get_var($wpdb->prepare($sql, ...$params));
        if ($strike_id > 0) {
            $wpdb->delete($table, ['id' => $strike_id], ['%d']);
        }
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
