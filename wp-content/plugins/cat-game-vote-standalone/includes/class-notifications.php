<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Notifications {
    private const META_KEY = 'catgame_notifications';
    private const NONCE_ACTION = 'catgame_notifications';
    private const MAX_STORED = 100;

    public static function init(): void {
        add_action('admin_post_catgame_get_notifications', [__CLASS__, 'handle_get_notifications']);
        add_action('admin_post_catgame_mark_notifications_read', [__CLASS__, 'handle_mark_all_read']);
        add_action('wp_ajax_catgame_get_notifications', [__CLASS__, 'handle_get_notifications']);
        add_action('wp_ajax_catgame_mark_notifications_read', [__CLASS__, 'handle_mark_all_read']);

        add_action('catgame_report_submitted', [__CLASS__, 'notify_report_received'], 10, 1);
    }

    public static function nonce_action(): string {
        return self::NONCE_ACTION;
    }

    public static function add_notification(int $user_id, string $type, string $title, string $message): array {
        if ($user_id <= 0) {
            return [];
        }

        $type = sanitize_key($type);
        $title = sanitize_text_field($title);
        $message = sanitize_textarea_field($message);

        if ($type === '' || $title === '' || $message === '') {
            return [];
        }

        $notifications = self::raw_notifications($user_id);
        $notification = [
            'id' => wp_generate_uuid4(),
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'created_at' => current_time('mysql'),
            'read_at' => null,
        ];

        array_unshift($notifications, $notification);
        if (count($notifications) > self::MAX_STORED) {
            $notifications = array_slice($notifications, 0, self::MAX_STORED);
        }

        update_user_meta($user_id, self::META_KEY, $notifications);

        return $notification;
    }

    public static function get_notifications(int $user_id, int $limit = 50): array {
        if ($user_id <= 0) {
            return [];
        }

        $items = self::raw_notifications($user_id);
        if ($limit > 0) {
            $items = array_slice($items, 0, $limit);
        }

        return $items;
    }

    public static function unread_count(int $user_id): int {
        $items = self::get_notifications($user_id, 100);
        $total = 0;

        foreach ($items as $item) {
            if (empty($item['read_at'])) {
                $total++;
            }
        }

        return $total;
    }

    public static function mark_all_read(int $user_id): int {
        if ($user_id <= 0) {
            return 0;
        }

        $items = self::raw_notifications($user_id);
        $marked = 0;
        $now = current_time('mysql');

        foreach ($items as $idx => $item) {
            if (!empty($item['read_at'])) {
                continue;
            }

            $items[$idx]['read_at'] = $now;
            $marked++;
        }

        if ($marked > 0) {
            update_user_meta($user_id, self::META_KEY, $items);
        }

        return $marked;
    }

    public static function endpoint_get_url(): string {
        return admin_url('admin-post.php?action=catgame_get_notifications');
    }

    public static function endpoint_mark_read_url(): string {
        return admin_url('admin-post.php?action=catgame_mark_notifications_read');
    }

    public static function notify_report_received(int $user_id): void {
        if ($user_id <= 0) {
            return;
        }

        self::add_notification(
            $user_id,
            'report_received',
            'Reporte recibido',
            'Tu reporte fue recibido correctamente. Gracias por ayudarnos a mantener la comunidad segura.'
        );
    }

    public static function handle_get_notifications(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión.'], 401);
        }

        if (!self::verify_nonce()) {
            wp_send_json_error(['message' => 'Solicitud inválida (nonce).'], 403);
        }

        $limit = isset($_REQUEST['limit']) ? (int) $_REQUEST['limit'] : 50;
        $limit = max(1, min(100, $limit));

        $user_id = get_current_user_id();
        $items = self::get_notifications($user_id, $limit);
        wp_send_json_success([
            'items' => $items,
            'unread_count' => self::unread_count($user_id),
        ]);
    }

    public static function handle_mark_all_read(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión.'], 401);
        }

        if (!self::verify_nonce()) {
            wp_send_json_error(['message' => 'Solicitud inválida (nonce).'], 403);
        }

        $user_id = get_current_user_id();
        $marked = self::mark_all_read($user_id);

        wp_send_json_success([
            'marked' => $marked,
            'unread_count' => 0,
        ]);
    }

    private static function verify_nonce(): bool {
        $nonce = wp_unslash($_REQUEST['_wpnonce'] ?? '');
        return is_string($nonce) && wp_verify_nonce($nonce, self::NONCE_ACTION) !== false;
    }

    private static function raw_notifications(int $user_id): array {
        $raw = get_user_meta($user_id, self::META_KEY, true);
        if (!is_array($raw)) {
            return [];
        }

        $items = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }

            $id = sanitize_text_field((string) ($item['id'] ?? ''));
            $type = sanitize_key((string) ($item['type'] ?? 'info'));
            $title = sanitize_text_field((string) ($item['title'] ?? ''));
            $message = sanitize_textarea_field((string) ($item['message'] ?? ''));
            $created_at = sanitize_text_field((string) ($item['created_at'] ?? ''));
            $read_at = $item['read_at'] ?? null;
            $read_at = is_string($read_at) && $read_at !== '' ? sanitize_text_field($read_at) : null;

            if ($id === '' || $title === '' || $message === '') {
                continue;
            }

            $items[] = [
                'id' => $id,
                'type' => $type !== '' ? $type : 'info',
                'title' => $title,
                'message' => $message,
                'created_at' => $created_at !== '' ? $created_at : current_time('mysql'),
                'read_at' => $read_at,
            ];
        }

        usort($items, static function (array $a, array $b): int {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $items;
    }
}
