<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Submissions {
    private const USER_CUSTOM_TAGS_META_KEY = 'catgame_custom_tags';

    public static function init(): void {
        add_action('admin_post_catgame_upload', [__CLASS__, 'handle_upload']);
    }

    public static function predefined_tags(): array {
        return ['tag_black_cat', 'tag_night_photo', 'tag_funny_pose', 'tag_weird_place'];
    }

    public static function user_custom_tags(int $user_id): array {
        $tags = get_user_meta($user_id, self::USER_CUSTOM_TAGS_META_KEY, true);
        if (!is_array($tags)) {
            return [];
        }

        $sanitized = [];
        foreach ($tags as $tag) {
            $clean = self::sanitize_tag($tag);
            if ($clean !== '') {
                $sanitized[] = $clean;
            }
        }

        return array_values(array_unique($sanitized));
    }

    public static function available_tags_for_user(int $user_id): array {
        return array_values(array_unique(array_merge(self::predefined_tags(), self::user_custom_tags($user_id))));
    }

    public static function handle_upload(): void {
        if (!is_user_logged_in()) {
            wp_die('Debes iniciar sesión para subir fotos.');
        }

        check_admin_referer('catgame_upload');

        if (empty($_POST['confirm_no_people'])) {
            wp_safe_redirect(add_query_arg('catgame_error', 'confirm_required', home_url('/catgame/upload')));
            exit;
        }

        $city = sanitize_text_field(wp_unslash($_POST['city'] ?? ''));
        $country = sanitize_text_field(wp_unslash($_POST['country'] ?? ''));
        if ($city === '' || $country === '') {
            wp_safe_redirect(add_query_arg('catgame_error', 'missing_location', home_url('/catgame/upload')));
            exit;
        }

        $event = CatGame_Events::get_active_event();
        if (!$event) {
            wp_safe_redirect(add_query_arg('catgame_error', 'no_event', home_url('/catgame/upload')));
            exit;
        }

        if (empty($_FILES['cat_image']['tmp_name'])) {
            wp_safe_redirect(add_query_arg('catgame_error', 'missing_file', home_url('/catgame/upload')));
            exit;
        }

        $file = $_FILES['cat_image'];
        if ((int) $file['size'] > 3 * 1024 * 1024) {
            wp_safe_redirect(add_query_arg('catgame_error', 'file_too_large', home_url('/catgame/upload')));
            exit;
        }

        $type = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        if (empty($type['type']) || strpos($type['type'], 'image/') !== 0) {
            wp_safe_redirect(add_query_arg('catgame_error', 'invalid_type', home_url('/catgame/upload')));
            exit;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload('cat_image', 0);
        if (is_wp_error($attachment_id)) {
            wp_safe_redirect(add_query_arg('catgame_error', 'upload_failed', home_url('/catgame/upload')));
            exit;
        }

        $final_size = self::compress_uploaded_image_backup((int) $attachment_id);

        $user_id = get_current_user_id();
        $selected_tags = wp_unslash($_POST['tags'] ?? []);
        $available_tags = self::available_tags_for_user($user_id);
        $new_custom_tags = self::parse_custom_tags_input(wp_unslash($_POST['custom_tags'] ?? ''));
        if (!empty($new_custom_tags)) {
            $available_tags = array_values(array_unique(array_merge($available_tags, $new_custom_tags)));
            self::save_user_custom_tags($user_id, $new_custom_tags);
        }

        $filtered_tags = [];
        if (is_array($selected_tags)) {
            foreach ($selected_tags as $tag) {
                $tag = self::sanitize_tag($tag);
                if ($tag !== '' && in_array($tag, $available_tags, true)) {
                    $filtered_tags[] = $tag;
                }
            }
        }

        if (!empty($new_custom_tags)) {
            $filtered_tags = array_merge($filtered_tags, $new_custom_tags);
        }

        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'event_id' => (int) $event['id'],
                'city' => $city,
                'country' => $country,
                'tags_json' => wp_json_encode(array_values(array_unique($filtered_tags))),
                'attachment_id' => (int) $attachment_id,
                'image_size_bytes' => $final_size > 0 ? $final_size : null,
                'created_at' => current_time('mysql'),
                'status' => 'active',
                'score_cached' => 0,
                'votes_count' => 0,
                'votes_sum' => 0,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%f', '%d', '%d']
        );

        self::clear_leaderboard_cache();

        wp_safe_redirect(home_url('/catgame/feed?uploaded=1'));
        exit;
    }

    public static function get_submission(int $id): ?array {
        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        return $submission ?: null;
    }

    public static function list_feed(int $event_id, int $limit = 20, int $offset = 0): array {
        global $wpdb;
        $table = CatGame_DB::table('submissions');
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE event_id = %d AND status = 'active' ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $event_id,
                $limit,
                $offset
            ),
            ARRAY_A
        );
    }

    public static function calculate_score(array $submission, array $rules): float {
        $votes_count = (int) ($submission['votes_count'] ?? 0);
        $votes_sum = (int) ($submission['votes_sum'] ?? 0);
        if ($votes_count <= 0) {
            return 0.0;
        }

        $score_base = ($votes_sum / $votes_count) * 2;
        $bonuses = 0.0;
        $tags = json_decode((string) ($submission['tags_json'] ?? '[]'), true);
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                if (isset($rules[$tag])) {
                    $bonuses += (float) $rules[$tag];
                }
            }
        }

        return (float) min(10, $score_base + $bonuses);
    }

    public static function recalculate_score(int $submission_id): void {
        $submission = self::get_submission($submission_id);
        if (!$submission) {
            return;
        }

        $event = CatGame_Events::get_event((int) $submission['event_id']);
        $rules = CatGame_Events::decode_rules($event['rules_json'] ?? null);
        $score = self::calculate_score($submission, $rules);

        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $wpdb->update($table, ['score_cached' => $score], ['id' => $submission_id], ['%f'], ['%d']);

        self::clear_leaderboard_cache();
    }

    public static function list_user_submissions(int $user_id): array {
        global $wpdb;
        $table = CatGame_DB::table('submissions');
        return $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC", $user_id),
            ARRAY_A
        );
    }

    public static function user_stats(int $user_id): array {
        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT COUNT(*) AS total_submissions, MAX(score_cached) AS best_score, AVG(score_cached) AS avg_score FROM {$table} WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );

        return [
            'total_submissions' => (int) ($row['total_submissions'] ?? 0),
            'best_score' => round((float) ($row['best_score'] ?? 0), 2),
            'avg_score' => round((float) ($row['avg_score'] ?? 0), 2),
        ];
    }

    public static function leaderboard(int $event_id, string $scope, string $country, string $city, int $limit = 20): array {
        global $wpdb;

        $cache_key = sprintf('catgame_lb_%d_%s_%s_%s_%d', $event_id, $scope, md5($country), md5($city), $limit);
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $table = CatGame_DB::table('submissions');
        $where = ['event_id = %d', "status = 'active'"];
        $params = [$event_id];

        if ($scope === 'country' && $country !== '') {
            $where[] = 'country = %s';
            $params[] = $country;
        }

        if ($scope === 'city' && $country !== '' && $city !== '') {
            $where[] = 'country = %s';
            $where[] = 'city = %s';
            $params[] = $country;
            $params[] = $city;
        }

        $params[] = $limit;

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . " ORDER BY score_cached DESC, votes_count DESC, created_at ASC LIMIT %d";
        $prepared = $wpdb->prepare($sql, ...$params);
        $results = $wpdb->get_results($prepared, ARRAY_A);

        set_transient($cache_key, $results, 60);
        self::remember_cache_key($cache_key);

        return $results;
    }

    public static function set_status(int $submission_id, string $status): void {
        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $wpdb->update($table, ['status' => $status], ['id' => $submission_id], ['%s'], ['%d']);
        self::clear_leaderboard_cache();
    }

    private static function remember_cache_key(string $key): void {
        $keys = get_option('catgame_leaderboard_cache_keys', []);
        if (!is_array($keys)) {
            $keys = [];
        }
        $keys[$key] = time();
        update_option('catgame_leaderboard_cache_keys', $keys, false);
    }

    public static function clear_leaderboard_cache(): void {
        $keys = get_option('catgame_leaderboard_cache_keys', []);
        if (is_array($keys)) {
            foreach (array_keys($keys) as $key) {
                delete_transient($key);
            }
        }
        update_option('catgame_leaderboard_cache_keys', [], false);
    }

    private static function compress_uploaded_image_backup(int $attachment_id): int {
        $file = get_attached_file($attachment_id);
        if (!$file || !file_exists($file)) {
            return 0;
        }

        $current_size = (int) filesize($file);
        if ($current_size <= 2 * 1024 * 1024) {
            return $current_size;
        }

        $editor = wp_get_image_editor($file);
        if (is_wp_error($editor)) {
            return $current_size;
        }

        $editor->resize(1280, 1280, false);
        if (method_exists($editor, 'set_quality')) {
            $editor->set_quality(82);
        }

        $prefer_webp = self::is_editor_mime_supported($editor, 'image/webp');
        $target_mime = $prefer_webp ? 'image/webp' : 'image/jpeg';
        $target_extension = $prefer_webp ? 'webp' : 'jpg';
        $target_file = preg_replace('/\.[^.]+$/', '.' . $target_extension, $file);
        if (!is_string($target_file) || $target_file === '') {
            $target_file = $file;
        }

        $saved = $editor->save($target_file, $target_mime);
        if (is_wp_error($saved)) {
            return $current_size;
        }

        $saved_path = is_array($saved) && !empty($saved['path']) ? (string) $saved['path'] : $file;
        if ($saved_path !== $file && file_exists($file)) {
            @unlink($file);
        }

        update_attached_file($attachment_id, $saved_path);
        $mime = is_array($saved) && !empty($saved['mime-type']) ? (string) $saved['mime-type'] : $target_mime;
        wp_update_post([
            'ID' => $attachment_id,
            'post_mime_type' => $mime,
        ]);

        $metadata = wp_generate_attachment_metadata($attachment_id, $saved_path);
        if (!is_wp_error($metadata) && is_array($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        if (!file_exists($saved_path)) {
            return 0;
        }

        return (int) filesize($saved_path);
    }

    private static function is_editor_mime_supported($editor, string $mime): bool {
        if (!is_object($editor)) {
            return false;
        }

        if (method_exists($editor, 'supports_mime_type')) {
            return (bool) $editor->supports_mime_type($mime);
        }

        return false;
    }

    private static function parse_custom_tags_input($raw_input): array {
        if (!is_string($raw_input) || $raw_input === '') {
            return [];
        }

        $parts = preg_split('/[\n,]+/', $raw_input) ?: [];
        $parsed = [];
        foreach ($parts as $part) {
            $tag = self::sanitize_tag($part);
            if ($tag !== '') {
                $parsed[] = $tag;
            }
        }

        return array_values(array_unique($parsed));
    }

    private static function sanitize_tag($raw_tag): string {
        if (!is_scalar($raw_tag)) {
            return '';
        }

        $tag = sanitize_title((string) $raw_tag);
        return $tag !== '' ? 'tag_' . $tag : '';
    }

    private static function save_user_custom_tags(int $user_id, array $new_tags): void {
        $merged = array_values(array_unique(array_merge(self::user_custom_tags($user_id), $new_tags)));
        update_user_meta($user_id, self::USER_CUSTOM_TAGS_META_KEY, $merged);
    }
}
