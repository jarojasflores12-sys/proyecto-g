<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Submissions {
    private const USER_CUSTOM_TAGS_META_KEY = 'catgame_custom_tags';

    public static function init(): void {
        add_action('admin_post_catgame_upload', [__CLASS__, 'handle_upload']);
        add_action('admin_post_catgame_delete_custom_tag', [__CLASS__, 'handle_delete_custom_tag']);
        add_action('admin_post_catgame_delete_submission', [__CLASS__, 'handle_delete_submission']);
        add_action('admin_post_catgame_feed_more', [__CLASS__, 'handle_feed_more']);
        add_action('admin_post_nopriv_catgame_feed_more', [__CLASS__, 'handle_feed_more']);
    }

    public static function predefined_tag_labels(): array {
        return [
            'black_cat' => 'Gato negro',
            'night_photo' => 'Foto nocturna',
            'funny_pose' => 'Pose divertida',
            'weird_place' => 'Lugar raro',
        ];
    }

    public static function predefined_tags(): array {
        return array_keys(self::predefined_tag_labels());
    }

    public static function normalize_tag($raw_tag): string {
        if (!is_scalar($raw_tag)) {
            return '';
        }

        $slug = sanitize_title((string) $raw_tag);
        if ($slug === '') {
            return '';
        }

        $slug = is_string($slug) ? preg_replace('/^(tag[-_\s]+)+/i', '', $slug) : '';
        $slug = is_string($slug) ? trim($slug, '-_ ') : '';
        if ($slug === '') {
            return '';
        }

        return str_replace('-', '_', $slug);
    }

    public static function humanize_tag(string $tag): string {
        $normalized = self::normalize_tag($tag);
        if ($normalized === '') {
            return '';
        }

        return ucwords(str_replace('_', ' ', $normalized));
    }

    private static function clean_tag_label(string $label): string {
        $clean = sanitize_text_field($label);
        $clean = preg_replace('/^(tag[\s:_-]+)+/i', '', $clean);
        $clean = is_string($clean) ? trim($clean) : '';
        return $clean;
    }

    public static function user_custom_tag_map(int $user_id): array {
        $raw = get_user_meta($user_id, self::USER_CUSTOM_TAGS_META_KEY, true);
        if (!is_array($raw)) {
            return [];
        }

        $result = [];
        foreach ($raw as $key => $value) {
            if (is_string($key) && $key !== '') {
                $slug = self::normalize_tag($key);
                $label = self::clean_tag_label((string) $value);
            } else {
                $slug = self::normalize_tag($value);
                $label = '';
            }

            if ($slug === '') {
                continue;
            }

            $result[$slug] = $label !== '' ? $label : self::humanize_tag($slug);
        }

        return $result;
    }

    public static function user_custom_tags(int $user_id): array {
        return array_keys(self::user_custom_tag_map($user_id));
    }

    public static function available_tags_for_user(int $user_id): array {
        return array_values(array_unique(array_merge(self::predefined_tags(), self::user_custom_tags($user_id))));
    }

    public static function label_for_tag(string $tag, int $user_id = 0): string {
        $predefined = self::predefined_tag_labels();
        if (isset($predefined[$tag])) {
            return $predefined[$tag];
        }

        if ($user_id > 0) {
            $custom = self::user_custom_tag_map($user_id);
            if (isset($custom[$tag])) {
                return self::clean_tag_label((string) $custom[$tag]) ?: self::humanize_tag($tag);
            }
        }

        return self::humanize_tag($tag);
    }

    public static function parse_tags_json($tags_json): array {
        $tags = json_decode((string) $tags_json, true);
        if (!is_array($tags)) {
            return [];
        }

        $normalized = [];
        foreach ($tags as $tag) {
            $slug = self::normalize_tag($tag);
            if ($slug !== '') {
                $normalized[] = $slug;
            }
        }

        return array_values(array_unique($normalized));
    }

    public static function parse_tags_text($tags_text): array {
        if (!is_string($tags_text) || trim($tags_text) === '') {
            return [];
        }

        $parts = array_filter(explode(',', $tags_text));
        $normalized = [];
        foreach ($parts as $tag) {
            $slug = self::normalize_tag($tag);
            if ($slug !== '') {
                $normalized[] = $slug;
            }
        }

        return array_values(array_unique($normalized));
    }

    public static function submission_tags(array $submission): array {
        $from_json = self::parse_tags_json($submission['tags_json'] ?? '[]');
        $from_text = self::parse_tags_text($submission['tags_text'] ?? '');
        return array_values(array_unique(array_merge($from_json, $from_text)));
    }

    public static function tags_text_from_list(array $tags): string {
        if (empty($tags)) {
            return '';
        }

        return ',' . implode(',', array_values(array_unique($tags))) . ',';
    }


    public static function title_label(array $submission): string {
        $title = trim((string) ($submission['title'] ?? ''));
        return $title !== '' ? $title : 'Sin título';
    }

    public static function upload_error_message(string $error): string {
        $map = [
            'confirm_required' => 'Debes confirmar que no hay personas en la foto.',
            'missing_location' => 'Completa tu ciudad y país en tu perfil para continuar.',
            'missing_title' => 'El título es obligatorio.',
            'title_too_short' => 'El título debe tener al menos 2 caracteres.',
            'title_too_long' => 'El título no puede superar los 40 caracteres.',
            'no_event' => 'No hay evento activo para recibir publicaciones.',
            'missing_file' => 'Selecciona una imagen para continuar.',
            'file_too_large' => 'La imagen supera el tamaño máximo permitido.',
            'invalid_type' => 'El archivo debe ser una imagen válida.',
            'upload_failed' => 'No se pudo subir la imagen. Intenta nuevamente.',
        ];

        return $map[$error] ?? 'No se pudo completar la publicación.';
    }

    private static function upload_redirect_with_state(string $error, array $state = []): void {
        $query = ['catgame_error' => $error];

        if (array_key_exists('title', $state)) {
            $query['upload_title'] = sanitize_text_field((string) $state['title']);
        }
        if (!empty($state['custom_tags'])) {
            $query['upload_custom_tags'] = sanitize_textarea_field((string) $state['custom_tags']);
        }
        if (!empty($state['tags']) && is_array($state['tags'])) {
            $query['upload_tags'] = implode(',', array_values(array_filter(array_map([__CLASS__, 'normalize_tag'], $state['tags']))));
        }
        if (!empty($state['confirm_no_people'])) {
            $query['upload_confirm_no_people'] = '1';
        }

        wp_safe_redirect(add_query_arg($query, home_url('/catgame/upload')));
        exit;
    }

    public static function handle_upload(): void {
        if (!is_user_logged_in()) {
            wp_die('Debes iniciar sesión para subir fotos.');
        }

        check_admin_referer('catgame_upload');

        $user_id = get_current_user_id();
        $city = sanitize_text_field((string) get_user_meta($user_id, 'catgame_default_city', true));
        $country = sanitize_text_field((string) get_user_meta($user_id, 'catgame_default_country', true));
        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $title = trim($title);
        $title_length = function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
        $selected_tags = wp_unslash($_POST['tags'] ?? []);
        $custom_tags_input = (string) wp_unslash($_POST['custom_tags'] ?? '');
        $confirm_no_people = !empty($_POST['confirm_no_people']);

        $upload_state = [
            'title' => $title,
            'tags' => is_array($selected_tags) ? $selected_tags : [],
            'custom_tags' => $custom_tags_input,
            'confirm_no_people' => $confirm_no_people,
        ];

        if (!$confirm_no_people) {
            self::upload_redirect_with_state('confirm_required', $upload_state);
        }

        if ($city === '' || $country === '') {
            wp_safe_redirect(add_query_arg('complete_profile', '1', home_url('/catgame/profile')));
            exit;
        }

        if ($title === '') {
            self::upload_redirect_with_state('missing_title', $upload_state);
        }

        if ($title_length < 2) {
            self::upload_redirect_with_state('title_too_short', $upload_state);
        }

        if ($title_length > 40) {
            self::upload_redirect_with_state('title_too_long', $upload_state);
        }

        $event = CatGame_Events::get_active_event();
        if (!$event) {
            self::upload_redirect_with_state('no_event', $upload_state);
        }

        if (empty($_FILES['cat_image']['tmp_name'])) {
            self::upload_redirect_with_state('missing_file', $upload_state);
        }

        $file = $_FILES['cat_image'];
        if ((int) $file['size'] > 3 * 1024 * 1024) {
            self::upload_redirect_with_state('file_too_large', $upload_state);
        }

        $type = wp_check_filetype_and_ext($file['tmp_name'], $file['name']);
        if (empty($type['type']) || strpos($type['type'], 'image/') !== 0) {
            self::upload_redirect_with_state('invalid_type', $upload_state);
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment_id = media_handle_upload('cat_image', 0);
        if (is_wp_error($attachment_id)) {
            self::upload_redirect_with_state('upload_failed', $upload_state);
        }

        $final_size = self::compress_uploaded_image_backup((int) $attachment_id);

        $available_tags = self::available_tags_for_user($user_id);

        $new_custom_tag_map = self::parse_custom_tags_input(wp_unslash($_POST['custom_tags'] ?? ''));
        $new_custom_tags = array_keys($new_custom_tag_map);
        if (!empty($new_custom_tag_map)) {
            $available_tags = array_values(array_unique(array_merge($available_tags, $new_custom_tags)));
            self::save_user_custom_tags($user_id, $new_custom_tag_map);
        }

        $filtered_tags = [];
        if (is_array($selected_tags)) {
            foreach ($selected_tags as $tag) {
                $tag = self::normalize_tag($tag);
                if ($tag !== '') {
                    $filtered_tags[] = $tag;
                }
            }
        }

        if (!empty($new_custom_tags)) {
            $filtered_tags = array_merge($filtered_tags, $new_custom_tags);
        }

        $filtered_tags = array_values(array_unique($filtered_tags));

        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'event_id' => (int) $event['id'],
                'city' => $city,
                'country' => $country,
                'tags_json' => wp_json_encode($filtered_tags),
                'tags_text' => self::tags_text_from_list($filtered_tags),
                'title' => $title,
                'attachment_id' => (int) $attachment_id,
                'image_size_bytes' => $final_size > 0 ? $final_size : null,
                'created_at' => current_time('mysql'),
                'status' => 'active',
                'score_cached' => 0,
                'votes_count' => 0,
                'votes_sum' => 0,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%f', '%d', '%d']
        );

        update_post_meta((int) $attachment_id, 'catgv_title', $title);

        self::clear_leaderboard_cache();

        wp_safe_redirect(home_url('/catgame/feed?uploaded=1'));
        exit;
    }


    public static function handle_delete_submission(): void {
        if (!is_user_logged_in()) {
            wp_safe_redirect(home_url('/catgame/profile'));
            exit;
        }

        check_admin_referer('catgame_delete_submission');
        $submission_id = isset($_POST['submission_id']) ? (int) $_POST['submission_id'] : 0;
        if ($submission_id <= 0) {
            wp_safe_redirect(add_query_arg('catgame_error', 'submission_unavailable', home_url('/catgame/feed')));
            exit;
        }

        $submission = self::get_submission($submission_id);
        $current_user = get_current_user_id();
        if (!$submission || (int) ($submission['user_id'] ?? 0) !== $current_user) {
            wp_safe_redirect(add_query_arg('catgame_error', 'submission_unavailable', home_url('/catgame/feed')));
            exit;
        }

        self::delete_submission_permanently($submission);

        $redirect_to = wp_get_referer();
        if (!is_string($redirect_to) || $redirect_to === '') {
            $redirect_to = home_url('/catgame/feed');
        }
        wp_safe_redirect(add_query_arg('deleted', '1', $redirect_to));
        exit;
    }

    private static function delete_submission_permanently(array $submission): void {
        global $wpdb;

        $submission_id = (int) ($submission['id'] ?? 0);
        if ($submission_id <= 0) {
            return;
        }

        $wpdb->delete(CatGame_DB::table('reactions'), ['submission_id' => $submission_id], ['%d']);
        $wpdb->delete(CatGame_DB::table('votes'), ['submission_id' => $submission_id], ['%d']);

        $reports_table = CatGame_DB::table('reports');
        $report_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $reports_table));
        if (is_string($report_exists) && $report_exists === $reports_table) {
            $wpdb->delete($reports_table, ['submission_id' => $submission_id], ['%d']);
        }

        $wpdb->delete(CatGame_DB::table('submissions'), ['id' => $submission_id], ['%d']);

        $attachment_id = (int) ($submission['attachment_id'] ?? 0);
        if ($attachment_id > 0) {
            wp_delete_attachment($attachment_id, true);
        }

        self::clear_leaderboard_cache();
    }

    public static function handle_delete_custom_tag(): void {
        if (!is_user_logged_in()) {
            wp_safe_redirect(home_url('/catgame/profile'));
            exit;
        }

        check_admin_referer('catgame_delete_custom_tag');
        $tag = self::normalize_tag(wp_unslash($_POST['tag'] ?? ''));

        if ($tag !== '' && !in_array($tag, self::predefined_tags(), true)) {
            $user_id = get_current_user_id();
            $custom_map = self::user_custom_tag_map($user_id);
            if (isset($custom_map[$tag])) {
                unset($custom_map[$tag]);
                update_user_meta($user_id, self::USER_CUSTOM_TAGS_META_KEY, $custom_map);
            }
        }

        wp_safe_redirect(add_query_arg('tag_deleted', '1', home_url('/catgame/profile')));
        exit;
    }

    public static function get_submission(int $id): ?array {
        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $id), ARRAY_A);
        return $submission ?: null;
    }

    public static function list_feed(int $event_id, int $limit = 20, int $offset = 0, string $tag = ''): array {
        $result = self::list_feed_paginated($event_id, $limit, $offset, $tag);
        return $result['items'];
    }

    public static function list_feed_paginated(int $event_id, int $per_page = 20, int $offset = 0, string $tag = ''): array {
        global $wpdb;
        $table = CatGame_DB::table('submissions');

        $per_page = max(1, min(50, $per_page));
        $offset = max(0, $offset);

        $where = ['event_id = %d', "status = 'active'"];
        $params = [$event_id];

        if ($tag !== '') {
            $search_tags = self::tag_storage_variants($tag);
            if (!empty($search_tags)) {
                $tag_clauses = [];
                foreach ($search_tags as $search_tag) {
                    $tag_clauses[] = '(tags_text LIKE %s OR tags_json LIKE %s)';
                    $params[] = '%,' . $wpdb->esc_like($search_tag) . ',%';
                    $params[] = '%"' . $wpdb->esc_like($search_tag) . '"%';
                }
                $where[] = '(' . implode(' OR ', $tag_clauses) . ')';
            }
        }

        $limit_plus_one = $per_page + 1;
        $params[] = $limit_plus_one;
        $params[] = $offset;

        $sql = "SELECT * FROM {$table} WHERE " . implode(' AND ', $where) . ' ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d';
        $prepared = $wpdb->prepare($sql, ...$params);
        $rows = $wpdb->get_results($prepared, ARRAY_A);

        $has_more = count($rows) > $per_page;
        if ($has_more) {
            $rows = array_slice($rows, 0, $per_page);
        }

        return [
            'items' => $rows,
            'has_more' => $has_more,
            'next_offset' => $offset + count($rows),
        ];
    }

    public static function handle_feed_more(): void {
        $nonce = wp_unslash($_REQUEST['_wpnonce'] ?? '');
        if (!is_string($nonce) || !wp_verify_nonce($nonce, 'catgame_feed_more')) {
            wp_send_json_error(['message' => 'Solicitud inválida (nonce).'], 403);
        }

        $event = CatGame_Events::get_active_event();
        if (!$event) {
            wp_send_json_success(['html' => '', 'has_more' => false, 'next_offset' => 0]);
        }

        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
        $per_page = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
        $page = self::list_feed_paginated((int) $event['id'], $per_page, $offset);
        $items = $page['items'];

        $current_user_id = is_user_logged_in() ? get_current_user_id() : 0;
        $top_items = self::leaderboard((int) $event['id'], 'global', '', '', 3, []);
        $top3_positions = [];
        foreach ($top_items as $idx => $top_item) {
            $top3_positions[(int) ($top_item['id'] ?? 0)] = $idx + 1;
        }

        $submission_ids = array_values(array_filter(array_map(static function (array $item): int {
            return (int) ($item['id'] ?? 0);
        }, $items)));
        $payload_map = CatGame_Reactions::reaction_payload_map($submission_ids, $current_user_id);

        foreach ($items as &$item) {
            $id = (int) ($item['id'] ?? 0);
            $payload = $payload_map[$id] ?? ['reaction_counts' => array_fill_keys(CatGame_Reactions::allowed_reactions(), 0), 'my_reaction' => null];
            $item['reaction_counts'] = is_array($payload['reaction_counts'] ?? null) ? $payload['reaction_counts'] : array_fill_keys(CatGame_Reactions::allowed_reactions(), 0);
            $item['my_reaction'] = $payload['my_reaction'] ?? null;
        }
        unset($item);

        ob_start();
        foreach ($items as $item) {
            $template_item = $item;
            include CATGAME_PLUGIN_DIR . 'templates/partials/feed-card.php';
        }
        $html = (string) ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'has_more' => (bool) ($page['has_more'] ?? false),
            'next_offset' => (int) ($page['next_offset'] ?? 0),
        ]);
    }

    public static function calculate_score(array $submission, array $rules = []): float {
        $votes_count = (int) ($submission['votes_count'] ?? 0);
        $votes_sum = (int) ($submission['votes_sum'] ?? 0);
        if ($votes_count <= 0) {
            return 0.0;
        }

        $score_base = ($votes_sum / $votes_count) * 2;
        return (float) min(10, $score_base);
    }

    public static function recalculate_score(int $submission_id): void {
        $submission = self::get_submission($submission_id);
        if (!$submission) {
            return;
        }

        $score = self::calculate_score($submission);

        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $wpdb->update($table, ['score_cached' => $score], ['id' => $submission_id], ['%f'], ['%d']);


        self::clear_leaderboard_cache();
    }

    public static function list_user_submissions(int $user_id, int $event_id = 0): array {
        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $reactions_table = CatGame_DB::table('reactions');

        $reaction_agg_sql = "
            SELECT submission_id, COUNT(*) AS total_reactions, MIN(created_at) AS first_reaction_at
            FROM {$reactions_table}
            GROUP BY submission_id
        ";

        if ($event_id > 0) {
            return $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT s.*, COALESCE(r.total_reactions, 0) AS total_reactions, r.first_reaction_at
                    FROM {$table} s
                    LEFT JOIN ({$reaction_agg_sql}) r ON r.submission_id = s.id
                    WHERE s.user_id = %d AND s.event_id = %d
                    ORDER BY s.created_at DESC",
                    $user_id,
                    $event_id
                ),
                ARRAY_A
            );
        }

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*, COALESCE(r.total_reactions, 0) AS total_reactions, r.first_reaction_at
                FROM {$table} s
                LEFT JOIN ({$reaction_agg_sql}) r ON r.submission_id = s.id
                WHERE s.user_id = %d
                ORDER BY s.created_at DESC",
                $user_id
            ),
            ARRAY_A
        );
    }

    public static function user_stats(int $user_id, int $event_id = 0): array {
        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $reactions_table = CatGame_DB::table('reactions');
        $reaction_agg_sql = "
            SELECT submission_id, COUNT(*) AS total_reactions, MIN(created_at) AS first_reaction_at
            FROM {$reactions_table}
            GROUP BY submission_id
        ";

        $where = 's.user_id = %d';
        $params = [$user_id];
        if ($event_id > 0) {
            $where .= ' AND s.event_id = %d';
            $params[] = $event_id;
        }

        $sql = "SELECT COUNT(*) AS total_submissions, COALESCE(SUM(COALESCE(r.total_reactions, 0)), 0) AS total_reactions FROM {$table} s LEFT JOIN ({$reaction_agg_sql}) r ON r.submission_id = s.id WHERE {$where}";
        $row = $wpdb->get_row($wpdb->prepare($sql, ...$params), ARRAY_A);

        $sql_most_reacted = "SELECT s.*, COALESCE(r.total_reactions, 0) AS total_reactions, r.first_reaction_at
            FROM {$table} s
            LEFT JOIN ({$reaction_agg_sql}) r ON r.submission_id = s.id
            WHERE {$where}
            ORDER BY COALESCE(r.total_reactions, 0) DESC, COALESCE(r.first_reaction_at, '9999-12-31 23:59:59') ASC, s.created_at DESC, s.id DESC
            LIMIT 1";
        $most_reacted = $wpdb->get_row($wpdb->prepare($sql_most_reacted, ...$params), ARRAY_A);

        $best_ranked = $most_reacted;

        return [
            'total_submissions' => (int) ($row['total_submissions'] ?? 0),
            'best_score' => 0,
            'avg_score' => 0,
            'total_votes' => 0,
            'total_reactions' => (int) ($row['total_reactions'] ?? 0),
            'most_voted' => is_array($most_reacted) ? $most_reacted : null,
            'best_ranked' => is_array($best_ranked) ? $best_ranked : null,
        ];
    }

    public static function event_tags(int $event_id): array {
        $items = self::list_feed($event_id, 200, 0);
        $tags = [];
        foreach ($items as $item) {
            $tags = array_merge($tags, self::submission_tags($item));
        }

        return array_values(array_unique(array_filter($tags)));
    }

    public static function leaderboard(int $event_id, string $scope, string $country, string $city, int $limit = 20, array $tags = []): array {
        global $wpdb;

        $cache_key = sprintf('catgame_lb_%d_%s_%s_%s_%d_%s', $event_id, $scope, md5($country), md5($city), $limit, md5(implode(',', $tags)));
        $cached = get_transient($cache_key);
        if (is_array($cached)) {
            return $cached;
        }

        $table = CatGame_DB::table('submissions');
        $reactions_table = CatGame_DB::table('reactions');
        $reaction_agg_sql = "
            SELECT submission_id, COUNT(*) AS total_reactions, MIN(created_at) AS first_reaction_at
            FROM {$reactions_table}
            GROUP BY submission_id
        ";
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

        $where_sql = implode(' AND ', array_map(static function ($clause): string {
            return str_replace(['event_id', 'status', 'country', 'city', 'tags_text', 'tags_json'], ['s.event_id', 's.status', 's.country', 's.city', 's.tags_text', 's.tags_json'], $clause);
        }, $where));

        $sql = "SELECT s.*, COALESCE(r.total_reactions, 0) AS total_reactions, r.first_reaction_at
            FROM {$table} s
            LEFT JOIN ({$reaction_agg_sql}) r ON r.submission_id = s.id
            WHERE {$where_sql}
            ORDER BY COALESCE(r.total_reactions, 0) DESC, COALESCE(r.first_reaction_at, '9999-12-31 23:59:59') ASC, s.created_at DESC, s.id DESC
            LIMIT %d";
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
        if (!is_string($raw_input) || trim($raw_input) === '') {
            return [];
        }

        $parts = preg_split('/[\n,]+/', $raw_input) ?: [];
        $parsed = [];
        foreach ($parts as $part) {
            $label = self::clean_tag_label(trim((string) $part));
            $slug = self::normalize_tag($label);
            if ($slug !== '') {
                $parsed[$slug] = $label !== '' ? $label : self::humanize_tag($slug);
            }
        }

        return $parsed;
    }

    private static function tag_storage_variants(string $tag): array {
        $normalized = self::normalize_tag($tag);
        if ($normalized === '') {
            return [];
        }

        return array_values(array_unique([
            $normalized,
            'tag_' . $normalized,
            'tag_tag_' . $normalized,
        ]));
    }

    private static function save_user_custom_tags(int $user_id, array $new_tags): void {
        $current = self::user_custom_tag_map($user_id);
        foreach ($new_tags as $slug => $label) {
            $normalized = self::normalize_tag($slug);
            if ($normalized === '' || in_array($normalized, self::predefined_tags(), true)) {
                continue;
            }

            $safe_label = self::clean_tag_label((string) $label);
            $current[$normalized] = $safe_label !== '' ? $safe_label : self::humanize_tag($normalized);
        }

        update_user_meta($user_id, self::USER_CUSTOM_TAGS_META_KEY, $current);
    }
}