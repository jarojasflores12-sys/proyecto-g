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
    private const CRON_HOOK_ENFORCE_GRAVE = 'catgame_enforce_grave_cases_event';
    private const GRAVE_ENFORCEMENT_LAST_RUN_OPTION = 'catgame_grave_enforcement_last_run';
    private const GRAVE_ENFORCEMENT_HISTORY_OPTION = 'catgame_grave_enforcement_history';
    private const GRAVE_ENFORCEMENT_HISTORY_LIMIT = 20;

    public static function init(): void {
        add_action('admin_post_catgame_report_submission', [__CLASS__, 'handle_report_submission']);
        add_action('wp_ajax_catgame_report_submission', [__CLASS__, 'handle_report_submission']);
        add_action('admin_post_catgame_moderate_report', [__CLASS__, 'handle_moderate_report']);
        add_action('wp_ajax_catgame_get_notifications', [__CLASS__, 'handle_get_notifications']);
        add_action('wp_ajax_catgame_mark_notifications_read', [__CLASS__, 'handle_mark_notifications_read']);
        add_action('wp_ajax_catgame_submit_appeal', [__CLASS__, 'handle_submit_appeal']);
        add_action('admin_post_catgame_decide_appeal', [__CLASS__, 'handle_decide_appeal']);
        add_action('admin_post_catgame_run_grave_enforcement', [__CLASS__, 'handle_manual_grave_enforcement']);
        add_action(self::CRON_HOOK_ENFORCE_GRAVE, [__CLASS__, 'enforce_grave_case_deadlines']);

        self::maybe_schedule_grave_enforcement();
        self::register_cli_commands();
    }

    public static function deactivate(): void {
        $timestamp = wp_next_scheduled(self::CRON_HOOK_ENFORCE_GRAVE);
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, self::CRON_HOOK_ENFORCE_GRAVE);
        }
    }

    private static function maybe_schedule_grave_enforcement(): void {
        if (!wp_next_scheduled(self::CRON_HOOK_ENFORCE_GRAVE)) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK_ENFORCE_GRAVE);
        }
    }

    private static function register_cli_commands(): void {
        if (!(defined('WP_CLI') && WP_CLI)) {
            return;
        }

        if (!class_exists('WP_CLI')) {
            return;
        }

        \WP_CLI::add_command('catgame bans-rebuild', static function (array $args, array $assoc_args): void {
            $user_id = isset($assoc_args['user_id']) ? (int) $assoc_args['user_id'] : 0;
            if ($user_id > 0) {
                self::rebuild_bans_for_user($user_id);
                \WP_CLI::success('Bans recalculados para user_id=' . $user_id);
                return;
            }

            $processed = self::rebuild_bans_for_all_users();
            \WP_CLI::success('Bans recalculados para ' . $processed . ' usuarios.');
        });
    }


    public static function get_last_grave_enforcement_run(): array {
        $value = get_option(self::GRAVE_ENFORCEMENT_LAST_RUN_OPTION, []);
        return is_array($value) ? $value : [];
    }

    public static function get_grave_enforcement_history(): array {
        $items = get_option(self::GRAVE_ENFORCEMENT_HISTORY_OPTION, []);
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $normalized[] = [
                'ran_at' => sanitize_text_field((string) ($item['ran_at'] ?? '')),
                'processed' => max(0, (int) ($item['processed'] ?? 0)),
                'source' => sanitize_key((string) ($item['source'] ?? 'runtime')),
                'duration_ms' => max(0, (int) ($item['duration_ms'] ?? 0)),
                'status' => sanitize_key((string) ($item['status'] ?? 'ok')),
            ];
        }

        return array_slice($normalized, 0, self::GRAVE_ENFORCEMENT_HISTORY_LIMIT);
    }

    public static function handle_manual_grave_enforcement(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('catgame_run_grave_enforcement');

        $processed = self::enforce_grave_case_deadlines('manual');
        wp_safe_redirect(admin_url('admin.php?page=catgame-moderation&grave_enforced=1&grave_enforced_count=' . (int) $processed));
        exit;
    }

    public static function endpoint_report_url(): string {
        return admin_url('admin-ajax.php');
    }

    public static function can_user_participate(int $user_id, string &$message = ''): bool {
        self::enforce_grave_case_deadlines();

        if ($user_id <= 0) {
            $message = 'Debes iniciar sesión para continuar.';
            return false;
        }

        if (self::is_login_blocked($user_id)) {
            $message = 'Cuenta eliminada por infracción grave.';
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
        $ban = self::get_ban_row($user_id);
        if (!$ban || empty($ban['upload_banned_until'])) {
            return 0;
        }

        return (int) strtotime((string) $ban['upload_banned_until']);
    }

    public static function is_upload_banned(int $user_id): bool {
        return self::is_upload_blocked($user_id);
    }

    public static function set_upload_ban_until(int $user_id, int $until_ts): void {
        if ($user_id <= 0) {
            return;
        }

        $until = $until_ts > 0 ? gmdate('Y-m-d H:i:s', $until_ts) : null;
        self::upsert_ban_fields($user_id, ['upload_banned_until' => $until]);
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
        self::enforce_grave_case_deadlines();
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
            if ($severity === 'moderado') {
                $severity = 'moderada';
            }
            if (!in_array($severity, ['leve', 'moderada', 'grave'], true)) {
                $severity = 'leve';
            }

            CatGame_Submissions::hide_submission($submission_id, 'removed');
            self::apply_moderation_penalty($author_id, $submission_id, $severity, $report_reason, $admin_user_id);

            self::add_notification(
                $author_id,
                'moderation',
                'Publicación eliminada',
                'Tu publicación “' . $submission_label . '” fue eliminada por incumplir las reglas: ' . $reason_label . ' (gravedad: ' . $severity . ').',
                'moderation_' . (int) $report_id . '_removed'
            );

        } elseif ($resolution === 'false_report') {
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
        self::enforce_grave_case_deadlines();
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

        self::mark_grave_case_pending_if_needed($submission_id, $user_id);

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
        } elseif (self::is_grave_case_submission($submission_id)) {
            self::close_grave_case($submission_id, 'rejected');
            self::execute_perma_ban($user_id, 'grave_rejected');
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

        $window_hours = ((string) ($current_action['severity'] ?? '') === 'grave') ? 24 : self::APPEAL_WINDOW_HOURS;
        if ((time() - $decided_at) > ($window_hours * HOUR_IN_SECONDS)) {
            $message = $window_hours === 24 ? 'La ventana de apelación expiró (24h).' : 'La ventana de apelación expiró (72h).';
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
        self::mark_infraction_reversed_by_submission($submission_id, 'appeal_accepted');
        self::close_grave_case($submission_id, 'accepted');
        self::recalculate_user_bans($user_id);

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

    private static function apply_moderation_penalty(int $user_id, int $submission_id, string $severity, string $reason_code, int $admin_user_id): void {
        if ($user_id <= 0 || $submission_id <= 0) {
            return;
        }

        $points_map = [
            'leve' => 1,
            'moderada' => 3,
            'grave' => 9,
        ];
        $points = $points_map[$severity] ?? 1;

        self::insert_infraction($user_id, $submission_id, $severity, $points, $reason_code, $admin_user_id);

        if ($severity === 'moderada') {
            self::extend_upload_ban($user_id, time() + (3 * DAY_IN_SECONDS));
        } elseif ($severity === 'grave') {
            self::set_hard_hold_grave($user_id);
            self::open_grave_case($user_id, $submission_id);
        }

        self::apply_escalation_upload_ban($user_id);
    }

    private static function insert_infraction(int $user_id, int $submission_id, string $severity, int $points, string $reason_code, int $admin_user_id): void {
        global $wpdb;
        $table = CatGame_DB::table('infractions');
        $created_at = current_time('mysql');
        $expires_at = gmdate('Y-m-d H:i:s', strtotime($created_at . ' +1 year'));
        $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'submission_id' => $submission_id > 0 ? $submission_id : null,
                'severity' => $severity,
                'points' => $points,
                'reason_code' => sanitize_key($reason_code),
                'created_at' => $created_at,
                'expires_at' => $expires_at,
                'decided_by' => $admin_user_id > 0 ? $admin_user_id : null,
            ],
            ['%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d']
        );
    }

    public static function points_total(int $user_id): int {
        if ($user_id <= 0) {
            return 0;
        }

        global $wpdb;
        $table = CatGame_DB::table('infractions');
        $now = current_time('mysql');
        return (int) $wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(points),0) FROM {$table} WHERE user_id = %d AND expires_at > %s AND reversed_at IS NULL", $user_id, $now));
    }

    private static function apply_escalation_upload_ban(int $user_id): void {
        $total = self::points_total($user_id);
        if ($total >= 9) {
            self::extend_upload_ban($user_id, time() + (7 * DAY_IN_SECONDS));
            return;
        }

        if ($total >= 3) {
            self::extend_upload_ban($user_id, time() + (3 * DAY_IN_SECONDS));
        }
    }

    private static function set_hard_hold_grave(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        $until = gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS);
        self::upsert_ban_fields($user_id, [
            'hard_hold_until' => $until,
            'react_banned_until' => $until,
            'upload_banned_until' => $until,
        ]);
    }

    private static function extend_upload_ban(int $user_id, int $until_ts): void {
        if ($user_id <= 0 || $until_ts <= 0) {
            return;
        }

        $ban = self::get_ban_row($user_id);
        $current_ts = !empty($ban['upload_banned_until']) ? strtotime((string) $ban['upload_banned_until']) : 0;
        if ($current_ts >= $until_ts) {
            return;
        }

        self::upsert_ban_fields($user_id, ['upload_banned_until' => gmdate('Y-m-d H:i:s', $until_ts)]);
    }

    private static function get_ban_row(int $user_id): ?array {
        if ($user_id <= 0) {
            return null;
        }

        global $wpdb;
        $table = CatGame_DB::table('bans');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $user_id), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    private static function upsert_ban_fields(int $user_id, array $fields): void {
        if ($user_id <= 0 || empty($fields)) {
            return;
        }

        global $wpdb;
        $table = CatGame_DB::table('bans');
        $existing = self::get_ban_row($user_id);
        $formats = [];
        foreach ($fields as $k => $_v) {
            $formats[] = $k === 'perma_banned' ? '%d' : '%s';
        }

        if ($existing) {
            $wpdb->update($table, $fields, ['user_id' => $user_id], $formats, ['%d']);
            return;
        }

        $data = array_merge(['user_id' => $user_id], $fields);
        $insert_formats = array_merge(['%d'], $formats);
        $wpdb->insert($table, $data, $insert_formats);
    }

    public static function is_login_blocked(int $user_id): bool {
        $ban = self::get_ban_row($user_id);
        return is_array($ban) && (int) ($ban['perma_banned'] ?? 0) === 1;
    }

    public static function is_upload_blocked(int $user_id): bool {
        $ban = self::get_ban_row($user_id);
        if (!$ban) {
            return false;
        }

        $now = time();
        $upload_until = !empty($ban['upload_banned_until']) ? strtotime((string) $ban['upload_banned_until']) : 0;
        $hold_until = !empty($ban['hard_hold_until']) ? strtotime((string) $ban['hard_hold_until']) : 0;
        return $upload_until > $now || $hold_until > $now;
    }

    public static function is_react_blocked(int $user_id): bool {
        $ban = self::get_ban_row($user_id);
        if (!$ban) {
            return false;
        }

        $now = time();
        $react_until = !empty($ban['react_banned_until']) ? strtotime((string) $ban['react_banned_until']) : 0;
        $hold_until = !empty($ban['hard_hold_until']) ? strtotime((string) $ban['hard_hold_until']) : 0;
        return $react_until > $now || $hold_until > $now;
    }

    private static function open_grave_case(int $user_id, int $submission_id): void {
        global $wpdb;
        $table = CatGame_DB::table('grave_cases');
        $existing_id = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE submission_id = %d AND case_status = 'open' LIMIT 1", $submission_id));
        $decided_at = current_time('mysql');
        $expires_at = gmdate('Y-m-d H:i:s', strtotime($decided_at . ' +24 hours'));
        if ($existing_id > 0) {
            $wpdb->update($table, [
                'user_id' => $user_id,
                'decided_at' => $decided_at,
                'expires_at' => $expires_at,
                'appeal_status' => 'none',
                'case_status' => 'open',
                'closed_at' => null,
            ], ['id' => $existing_id], ['%d', '%s', '%s', '%s', '%s', '%s'], ['%d']);
            return;
        }

        $wpdb->insert($table, [
            'user_id' => $user_id,
            'submission_id' => $submission_id,
            'decided_at' => $decided_at,
            'expires_at' => $expires_at,
            'appeal_status' => 'none',
            'case_status' => 'open',
        ], ['%d', '%d', '%s', '%s', '%s', '%s']);
    }

    private static function mark_grave_case_pending_if_needed(int $submission_id, int $user_id): void {
        if (!self::is_grave_case_submission($submission_id)) {
            return;
        }

        global $wpdb;
        $table = CatGame_DB::table('grave_cases');
        $extended_until = gmdate('Y-m-d H:i:s', time() + (30 * DAY_IN_SECONDS));
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET appeal_status = 'pending' WHERE submission_id = %d AND case_status = 'open'", $submission_id));
        self::upsert_ban_fields($user_id, ['hard_hold_until' => $extended_until, 'upload_banned_until' => $extended_until, 'react_banned_until' => $extended_until]);
    }

    private static function close_grave_case(int $submission_id, string $status): void {
        global $wpdb;
        $table = CatGame_DB::table('grave_cases');
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET case_status = %s, appeal_status = %s, closed_at = %s WHERE submission_id = %d AND case_status = 'open'", $status, $status, current_time('mysql'), $submission_id));
    }

    private static function is_grave_case_submission(int $submission_id): bool {
        if ($submission_id <= 0) {
            return false;
        }

        global $wpdb;
        $table = CatGame_DB::table('grave_cases');
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE submission_id = %d AND case_status = 'open'", $submission_id));
        return $count > 0;
    }

    public static function enforce_grave_case_deadlines(string $source = 'runtime'): int {
        global $wpdb;
        $table = CatGame_DB::table('grave_cases');
        $now = current_time('mysql');
        $started_at = microtime(true);
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE case_status = 'open' AND appeal_status = 'none' AND expires_at <= %s LIMIT 50", $now), ARRAY_A);
        if (!is_array($rows) || empty($rows)) {
            self::track_grave_enforcement_run(0, $source, 0, 'ok');
            return 0;
        }

        $processed = 0;
        foreach ($rows as $row) {
            $user_id = (int) ($row['user_id'] ?? 0);
            $submission_id = (int) ($row['submission_id'] ?? 0);
            if ($user_id <= 0 || $submission_id <= 0) {
                continue;
            }

            self::close_grave_case($submission_id, 'expired');
            self::execute_perma_ban($user_id, 'grave_expired_no_appeal');
            $processed++;
        }

        $duration_ms = (int) round((microtime(true) - $started_at) * 1000);
        self::track_grave_enforcement_run($processed, $source, $duration_ms, 'ok');
        return $processed;
    }

    private static function mark_infraction_reversed_by_submission(int $submission_id, string $reason = 'appeal_accepted'): void {
        if ($submission_id <= 0) {
            return;
        }

        global $wpdb;
        $table = CatGame_DB::table('infractions');
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$table} SET reversed_at = %s, reverse_reason = %s WHERE submission_id = %d AND reversed_at IS NULL",
                current_time('mysql'),
                sanitize_key($reason),
                $submission_id
            )
        );
    }

    private static function recalculate_user_bans(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        $ban = self::get_ban_row($user_id);
        if (!$ban) {
            self::apply_escalation_upload_ban($user_id);
            return;
        }

        if ((int) ($ban['perma_banned'] ?? 0) === 1) {
            return;
        }

        self::upsert_ban_fields($user_id, [
            'hard_hold_until' => null,
            'react_banned_until' => null,
            'upload_banned_until' => null,
        ]);
        self::apply_escalation_upload_ban($user_id);
    }


    private static function track_grave_enforcement_run(int $processed, string $source = 'runtime', int $duration_ms = 0, string $status = 'ok'): void {
        $record = [
            'ran_at' => current_time('mysql'),
            'processed' => max(0, (int) $processed),
            'source' => sanitize_key($source),
            'duration_ms' => max(0, (int) $duration_ms),
            'status' => sanitize_key($status),
        ];

        update_option(self::GRAVE_ENFORCEMENT_LAST_RUN_OPTION, $record, false);

        $history = self::get_grave_enforcement_history();
        array_unshift($history, $record);
        $history = array_slice($history, 0, self::GRAVE_ENFORCEMENT_HISTORY_LIMIT);
        update_option(self::GRAVE_ENFORCEMENT_HISTORY_OPTION, $history, false);
    }

    public static function rebuild_bans_for_user(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        self::enforce_grave_case_deadlines('cli');
        self::recalculate_user_bans($user_id);

        global $wpdb;
        $grave_cases_table = CatGame_DB::table('grave_cases');
        $open_grave_case = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$grave_cases_table} WHERE user_id = %d AND case_status = 'open'",
                $user_id
            )
        );

        if ($open_grave_case > 0) {
            $extended_until = gmdate('Y-m-d H:i:s', time() + (30 * DAY_IN_SECONDS));
            self::upsert_ban_fields($user_id, [
                'hard_hold_until' => $extended_until,
                'upload_banned_until' => $extended_until,
                'react_banned_until' => $extended_until,
            ]);
        }
    }

    public static function rebuild_bans_for_all_users(): int {
        global $wpdb;
        $infractions_table = CatGame_DB::table('infractions');
        $grave_cases_table = CatGame_DB::table('grave_cases');

        $user_ids = $wpdb->get_col("SELECT DISTINCT user_id FROM {$infractions_table}");
        $grave_user_ids = $wpdb->get_col("SELECT DISTINCT user_id FROM {$grave_cases_table}");
        $all_ids = array_unique(array_map('intval', array_merge(is_array($user_ids) ? $user_ids : [], is_array($grave_user_ids) ? $grave_user_ids : [])));
        $all_ids = array_values(array_filter($all_ids, static function (int $id): bool {
            return $id > 0;
        }));

        foreach ($all_ids as $user_id) {
            self::rebuild_bans_for_user($user_id);
        }

        return count($all_ids);
    }

    private static function email_hash(string $email): string {
        $email = strtolower(trim($email));
        return hash('sha256', $email . '|' . wp_salt('auth'));
    }

    public static function is_email_perma_banned(string $email): bool {
        $hash = self::email_hash($email);
        global $wpdb;
        $table = CatGame_DB::table('perma_bans');
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE email_hash = %s", $hash));
        return $count > 0;
    }

    public static function execute_perma_ban(int $user_id, string $reason_code): void {
        if ($user_id <= 0) {
            return;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }

        global $wpdb;
        $perma_table = CatGame_DB::table('perma_bans');
        $bans_table = CatGame_DB::table('bans');
        $hash = self::email_hash((string) $user->user_email);
        $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$perma_table} WHERE email_hash = %s LIMIT 1", $hash));
        if ($exists <= 0) {
            $wpdb->insert($perma_table, [
                'email_hash' => $hash,
                'created_at' => current_time('mysql'),
                'reason_code' => sanitize_key($reason_code),
                'note' => 'auto',
            ], ['%s', '%s', '%s', '%s']);
        }

        self::upsert_ban_fields($user_id, [
            'perma_banned' => 1,
            'perma_banned_at' => current_time('mysql'),
            'upload_banned_until' => null,
            'react_banned_until' => null,
            'hard_hold_until' => null,
        ]);

        self::purge_user_game_data($user_id);
        wp_destroy_current_session();
        wp_clear_auth_cookie();
    }

    private static function purge_user_game_data(int $user_id): void {
        global $wpdb;

        $submissions_table = CatGame_DB::table('submissions');
        $votes_table = CatGame_DB::table('votes');
        $reactions_table = CatGame_DB::table('reactions');
        $reports_table = CatGame_DB::table('reports');
        $appeals_table = CatGame_DB::table('appeals');
        $moderation_actions_table = CatGame_DB::table('moderation_actions');
        $infractions_table = CatGame_DB::table('infractions');
        $grave_cases_table = CatGame_DB::table('grave_cases');

        $submission_rows = $wpdb->get_results($wpdb->prepare("SELECT id, attachment_id FROM {$submissions_table} WHERE user_id = %d", $user_id), ARRAY_A);
        if (is_array($submission_rows)) {
            foreach ($submission_rows as $row) {
                $submission_id = (int) ($row['id'] ?? 0);
                $attachment_id = (int) ($row['attachment_id'] ?? 0);
                if ($submission_id > 0) {
                    $wpdb->delete($votes_table, ['submission_id' => $submission_id], ['%d']);
                    $wpdb->delete($reactions_table, ['submission_id' => $submission_id], ['%d']);
                    $wpdb->delete($reports_table, ['submission_id' => $submission_id], ['%d']);
                    $wpdb->delete($appeals_table, ['submission_id' => $submission_id], ['%d']);
                    $wpdb->delete($moderation_actions_table, ['submission_id' => $submission_id], ['%d']);
                    $wpdb->delete($infractions_table, ['submission_id' => $submission_id], ['%d']);
                    $wpdb->delete($grave_cases_table, ['submission_id' => $submission_id], ['%d']);
                }
                if ($attachment_id > 0) {
                    wp_delete_attachment($attachment_id, true);
                }
            }
        }

        $wpdb->delete($submissions_table, ['user_id' => $user_id], ['%d']);
        $wpdb->delete($reactions_table, ['user_id' => $user_id], ['%d']);
        $wpdb->delete($votes_table, ['user_id' => $user_id], ['%d']);
        $wpdb->delete($reports_table, ['reported_user_id' => $user_id], ['%d']);
        $wpdb->delete($appeals_table, ['user_id' => $user_id], ['%d']);
        $wpdb->delete($moderation_actions_table, ['user_id' => $user_id], ['%d']);
        $wpdb->delete($infractions_table, ['user_id' => $user_id], ['%d']);
        $wpdb->delete($grave_cases_table, ['user_id' => $user_id], ['%d']);

        delete_user_meta($user_id, self::NOTIFICATIONS_META_KEY);
        delete_user_meta($user_id, self::UPLOAD_BAN_META_KEY);
        delete_user_meta($user_id, 'catgame_city');
        delete_user_meta($user_id, 'catgame_country');
        delete_user_meta($user_id, 'catgame_custom_tags');
        delete_user_meta($user_id, 'catgame_avatar_color');
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
