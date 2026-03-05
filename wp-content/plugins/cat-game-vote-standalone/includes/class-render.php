<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Render {
    public static function init(): void {
    }

    public static function render_layout(string $page): void {
        $data = self::page_data($page);
        $title = 'Cat Game';
        $settings = CatGame_Admin::get_settings();
        $background_url = '';

        if (!empty($settings['background_image_url']) && is_string($settings['background_image_url'])) {
            $background_url = esc_url_raw($settings['background_image_url']);
        }

        include CATGAME_PLUGIN_DIR . 'templates/layout.php';
    }

    private static function public_profile_url(string $username): string {
        $safe = sanitize_user($username, true);
        if ($safe === '') {
            return home_url('/catgame/feed');
        }

        return home_url('/catgame/user/' . rawurlencode($safe));
    }

    private static function top3_positions(?array $event): array {
        if (!$event) {
            return [];
        }

        $top_items = CatGame_Submissions::leaderboard((int) $event['id'], 'global', '', '', 3);
        $positions = [];
        foreach ($top_items as $idx => $item) {
            $positions[(int) ($item['id'] ?? 0)] = (int) $idx + 1;
        }

        return $positions;
    }


    private static function with_reaction_payload(array $items, int $user_id): array {
        if (empty($items)) {
            return [];
        }

        $submission_ids = [];
        foreach ($items as $item) {
            $submission_ids[] = (int) ($item['id'] ?? 0);
        }

        $payload_map = CatGame_Reactions::reaction_payload_map($submission_ids, $user_id);
        foreach ($items as &$item) {
            $id = (int) ($item['id'] ?? 0);
            $payload = $payload_map[$id] ?? ['reaction_counts' => array_fill_keys(CatGame_Reactions::allowed_reactions(), 0), 'my_reaction' => null];
            $item['reaction_counts'] = is_array($payload['reaction_counts'] ?? null) ? $payload['reaction_counts'] : array_fill_keys(CatGame_Reactions::allowed_reactions(), 0);
            $item['my_reaction'] = $payload['my_reaction'] ?? null;
        }
        unset($item);

        return $items;
    }

    private static function with_reaction_payload_single(?array $item, int $user_id): ?array {
        if (!is_array($item)) {
            return null;
        }

        $list = self::with_reaction_payload([$item], $user_id);
        return !empty($list[0]) ? $list[0] : $item;
    }

    private static function page_data(string $page): array {

        $event = CatGame_Events::get_active_event();
        $top3_positions = self::top3_positions($event);
        $current_user_id = is_user_logged_in() ? get_current_user_id() : 0;

        switch ($page) {
            case 'upload':
                $user_tags = is_user_logged_in() ? CatGame_Submissions::available_tags_for_user(get_current_user_id()) : [];
                $upload_defaults = [
                    'default_city' => '',
                    'default_country' => '',
                ];
                $requires_location = false;
                if (is_user_logged_in()) {
                    $uid = get_current_user_id();
                    $location = CatGame_Auth::get_user_default_location($uid);
                    $upload_defaults['default_city'] = $location['city'];
                    $upload_defaults['default_country'] = $location['country'];
                    $requires_location = !CatGame_Auth::has_user_default_location($uid);
                }

                $selected_tags_raw = sanitize_text_field(wp_unslash($_GET['upload_tags'] ?? ''));
                $selected_tags = [];
                if ($selected_tags_raw !== '') {
                    foreach (explode(',', $selected_tags_raw) as $raw_tag) {
                        $tag = CatGame_Submissions::normalize_tag($raw_tag);
                        if ($tag !== '') {
                            $selected_tags[] = $tag;
                        }
                    }
                }

                $upload_state = [
                    'title' => sanitize_text_field(wp_unslash($_GET['upload_title'] ?? '')),
                    'custom_tags' => sanitize_textarea_field(wp_unslash($_GET['upload_custom_tags'] ?? '')),
                    'selected_tags' => array_values(array_unique($selected_tags)),
                    'confirm_no_people' => (int) ($_GET['upload_confirm_no_people'] ?? 0) === 1,
                ];

                $upload_error_key = sanitize_key(wp_unslash($_GET['catgame_error'] ?? ''));
                $upload_error = $upload_error_key !== '' ? CatGame_Submissions::upload_error_message($upload_error_key) : '';
                $upload_ban_until = sanitize_text_field(wp_unslash($_GET['upload_ban_until'] ?? ''));
                if ($upload_error_key === 'upload_banned' && $upload_ban_until !== '') {
                    $upload_error = 'Tienes restringida la subida de publicaciones hasta ' . $upload_ban_until . '. Puedes seguir reaccionando.';
                }

                $upload_restriction = [
                    'upload_banned' => false,
                    'upload_banned_until' => null,
                ];
                if (is_user_logged_in() && class_exists('CatGame_Reports')) {
                    $upload_ban_until_ts = CatGame_Reports::get_upload_ban_until(get_current_user_id());
                    $upload_restriction['upload_banned'] = $upload_ban_until_ts > time();
                    if ($upload_ban_until_ts > 0) {
                        $upload_restriction['upload_banned_until'] = gmdate('c', $upload_ban_until_ts);
                    }
                }

                return [
                    'page' => $page,
                    'event' => $event,
                    'user_tags' => $user_tags,
                    'upload_defaults' => $upload_defaults,
                    'upload_state' => $upload_state,
                    'upload_error' => $upload_error,
                    'requires_location' => $requires_location,
                    'upload_restriction' => $upload_restriction,
                ];
            case 'feed':
                $feed_per_page = 20;
                $feed_page = $event ? CatGame_Submissions::list_feed_paginated((int) $event['id'], $feed_per_page, 0) : ['items' => [], 'has_more' => false, 'next_offset' => 0];

                return [
                    'page' => $page,
                    'event' => $event,
                    'submissions' => $event ? self::with_reaction_payload((array) ($feed_page['items'] ?? []), $current_user_id) : [],
                    'feed_per_page' => $feed_per_page,
                    'feed_has_more' => !empty($feed_page['has_more']),
                    'feed_next_offset' => (int) ($feed_page['next_offset'] ?? 0),
                    'top3_positions' => $top3_positions,
                    'current_user_id' => $current_user_id,
                ];
            case 'submission':
                $submission_id = (int) get_query_var('submission_id');
                $submission = self::with_reaction_payload_single(CatGame_Submissions::get_submission($submission_id), $current_user_id);
                $already_voted = false;
                if ($submission && is_user_logged_in()) {
                    $already_voted = CatGame_Votes::has_user_voted((int) $submission['id'], get_current_user_id());
                }

                return [
                    'page' => $page,
                    'event' => $event,
                    'submission' => $submission,
                    'already_voted' => $already_voted,
                    'top3_positions' => $top3_positions,
                    'current_user_id' => $current_user_id,
                ];
            case 'leaderboard':
                $scope = sanitize_text_field($_GET['scope'] ?? 'global');
                $country = sanitize_text_field(wp_unslash($_GET['country'] ?? ''));
                $city = sanitize_text_field(wp_unslash($_GET['city'] ?? ''));

                if (!in_array($scope, ['global', 'country', 'city'], true)) {
                    $scope = 'global';
                }

                $location_catalog = $event ? CatGame_Submissions::leaderboard_location_catalog((int) $event['id']) : [
                    'countries' => [],
                    'cities_by_country' => [],
                ];

                $countries = is_array($location_catalog['countries'] ?? null) ? $location_catalog['countries'] : [];
                $cities_by_country = is_array($location_catalog['cities_by_country'] ?? null) ? $location_catalog['cities_by_country'] : [];

                if ($country !== '' && !in_array($country, $countries, true)) {
                    $country = '';
                }

                $city_options = ($country !== '' && isset($cities_by_country[$country]) && is_array($cities_by_country[$country]))
                    ? $cities_by_country[$country]
                    : [];

                if ($city !== '' && !in_array($city, $city_options, true)) {
                    $city = '';
                }

                $items = $event ? self::with_reaction_payload(CatGame_Submissions::leaderboard((int) $event['id'], $scope, $country, $city, 20, []), $current_user_id) : [];
                return [
                    'page' => $page,
                    'event' => $event,
                    'scope' => $scope,
                    'country' => $country,
                    'city' => $city,
                    'countries' => $countries,
                    'cities_by_country' => $cities_by_country,
                    'items' => $items,
                    'top3_positions' => $top3_positions,
                    'current_user_id' => $current_user_id,
                ];
            case 'profile':
                $auth_view = sanitize_key(wp_unslash($_GET['auth'] ?? 'login'));
                if (!in_array($auth_view, ['login', 'register', 'forgot', 'reset'], true)) {
                    $auth_view = 'login';
                }

                $register_error = sanitize_text_field(wp_unslash($_GET['register_error'] ?? ''));
                $login_error = sanitize_text_field(wp_unslash($_GET['login_error'] ?? ''));
                $lost_error = sanitize_text_field(wp_unslash($_GET['lost_error'] ?? ''));
                $reset_error = sanitize_text_field(wp_unslash($_GET['reset_error'] ?? ''));
                $registered = isset($_GET['registered']) ? (int) $_GET['registered'] : 0;
                $lost_sent = isset($_GET['lost_sent']) ? (int) $_GET['lost_sent'] : 0;
                $password_reset = isset($_GET['password_reset']) ? (int) $_GET['password_reset'] : 0;
                $tag_deleted = isset($_GET['tag_deleted']) ? (int) $_GET['tag_deleted'] : 0;
                $profile_saved = isset($_GET['profile_saved']) ? (int) $_GET['profile_saved'] : 0;
                $complete_profile = isset($_GET['complete_profile']) ? (int) $_GET['complete_profile'] : 0;
                $profile_error = sanitize_key(wp_unslash($_GET['profile_error'] ?? ''));
                $profile_city = sanitize_text_field(wp_unslash($_GET['profile_city'] ?? ''));
                $profile_country = sanitize_text_field(wp_unslash($_GET['profile_country'] ?? ''));
                $profile_avatar = sanitize_key(wp_unslash($_GET['profile_avatar'] ?? ''));
                $login_identifier = sanitize_text_field(wp_unslash($_GET['login_identifier'] ?? ''));
                $reg_username = sanitize_user(wp_unslash($_GET['reg_username'] ?? ''), true);
                $reg_email = sanitize_email(wp_unslash($_GET['reg_email'] ?? ''));
                $lost_identifier = sanitize_text_field(wp_unslash($_GET['lost_identifier'] ?? ''));
                $rp_login = sanitize_text_field(wp_unslash($_GET['rp_login'] ?? ''));
                $rp_key = sanitize_text_field(wp_unslash($_GET['key'] ?? ''));
                $reset_user = null;
                if ($auth_view === 'reset' && $rp_login !== '' && $rp_key !== '') {
                    $candidate_user = check_password_reset_key($rp_key, $rp_login);
                    if ($candidate_user instanceof WP_User) {
                        $reset_user = $candidate_user;
                    }
                }

                $scope = sanitize_key(wp_unslash($_GET['scope'] ?? 'event'));
                if (!in_array($scope, ['event', 'global'], true)) {
                    $scope = 'event';
                }

                if (!is_user_logged_in()) {
                    return [
                        'page' => $page,
                        'event' => $event,
                        'requires_login' => true,
                        'auth_view' => $auth_view,
                        'register_error' => $register_error,
                        'login_error' => $login_error,
                        'lost_error' => $lost_error,
                        'reset_error' => $reset_error,
                        'registered' => $registered,
                        'lost_sent' => $lost_sent,
                        'password_reset' => $password_reset,
                        'login_identifier' => $login_identifier,
                        'reg_username' => $reg_username,
                        'reg_email' => $reg_email,
                        'lost_identifier' => $lost_identifier,
                        'rp_login' => $rp_login,
                        'rp_key' => $rp_key,
                        'has_valid_reset_key' => $reset_user instanceof WP_User,
                    ];
                }

                $user_id = get_current_user_id();
                $event_id = ($scope === 'event' && $event) ? (int) $event['id'] : 0;
                $items = self::with_reaction_payload(CatGame_Submissions::list_user_submissions($user_id, $event_id), $current_user_id);
                $stats = CatGame_Submissions::user_stats($user_id, $event_id);

                $best_photo = null;
                if ($event) {
                    $active_items = CatGame_Submissions::list_user_submissions($user_id, (int) $event['id']);
                    usort($active_items, static function (array $a, array $b): int {
                        $a_total = (int) ($a['total_reactions'] ?? 0);
                        $b_total = (int) ($b['total_reactions'] ?? 0);
                        if ($a_total !== $b_total) {
                            return $b_total <=> $a_total;
                        }

                        $a_first = (string) ($a['first_reaction_at'] ?? '9999-12-31 23:59:59');
                        $b_first = (string) ($b['first_reaction_at'] ?? '9999-12-31 23:59:59');
                        if ($a_first !== $b_first) {
                            return strcmp($a_first, $b_first);
                        }

                        return (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0);
                    });

                    if (!empty($active_items) && (int) ($active_items[0]['total_reactions'] ?? 0) > 0) {
                        $best_photo = self::with_reaction_payload_single($active_items[0], $current_user_id);
                    }
                }

                $top_position_for_user = null;
                if (!empty($top3_positions) && $event) {
                    $top_items = CatGame_Submissions::leaderboard((int) $event['id'], 'global', '', '', 3);
                    foreach ($top_items as $idx => $top_item) {
                        if ((int) ($top_item['user_id'] ?? 0) === $user_id) {
                            $top_position_for_user = (int) $idx + 1;
                            break;
                        }
                    }
                }

                $location = CatGame_Auth::get_user_default_location($user_id);
                $account_status = [
                    'strikes' => [
                        'author_active' => 0,
                        'reporter_active' => 0,
                        'threshold' => 3,
                        'resets' => '1 año',
                    ],
                    'bans' => [
                        'upload_banned_until' => null,
                        'upload_banned' => false,
                    ],
                ];
                if (class_exists('CatGame_Reports')) {
                    $account_status['strikes']['author_active'] = CatGame_Reports::active_strikes_count_by_kind($user_id, 'author');
                    $account_status['strikes']['reporter_active'] = CatGame_Reports::active_strikes_count_by_kind($user_id, 'reporter');
                    $ban_until_ts = CatGame_Reports::get_upload_ban_until($user_id);
                    $account_status['bans']['upload_banned'] = $ban_until_ts > time();
                    if ($ban_until_ts > 0) {
                        $account_status['bans']['upload_banned_until'] = gmdate('c', $ban_until_ts);
                    }
                }
                $avatar_color_pref = (string) get_user_meta($user_id, 'catgame_avatar_color', true);

                return [
                    'page' => $page,
                    'event' => $event,
                    'requires_login' => false,
                    'register_error' => $register_error,
                    'registered' => $registered,
                    'tag_deleted' => $tag_deleted,
                    'profile_saved' => $profile_saved,
                    'complete_profile' => $complete_profile,
                    'profile_error' => $profile_error,
                    'scope' => $scope,
                    'items' => $items,
                    'stats' => $stats,
                    'custom_tags' => CatGame_Submissions::user_custom_tag_map($user_id),
                    'top3_positions' => $top3_positions,
                    'current_user_id' => $current_user_id,
                    'top_position_for_user' => $top_position_for_user,
                    'best_photo' => $best_photo,
                    'notifications' => class_exists('CatGame_Reports') ? CatGame_Reports::list_notifications($user_id, 15) : [],
                    'account_status' => $account_status,
                    'profile_prefs' => [
                        'display_name' => (string) get_user_meta($user_id, 'catgame_display_name', true),
                        'avatar_color' => $profile_error !== '' && $profile_avatar !== '' ? $profile_avatar : $avatar_color_pref,
                        'default_city' => $profile_error !== '' ? $profile_city : $location['city'],
                        'default_country' => $profile_error !== '' ? $profile_country : $location['country'],
                        'language' => (string) get_user_meta($user_id, 'catgame_language', true),
                    ],
                ];
            case 'user':
                $username = sanitize_user((string) get_query_var('catgame_username', ''), true);
                if ($username === '') {
                    $username = sanitize_user((string) wp_unslash($_GET['u'] ?? ''), true);
                }

                $public_user = $username !== '' ? get_user_by('login', $username) : false;
                if (!$public_user instanceof WP_User) {
                    return [
                        'page' => 'user',
                        'event' => $event,
                        'user_not_found' => true,
                        'public_profile_username' => $username,
                        'active_items' => [],
                        'recent_items' => [],
                        'public_profile_url' => home_url('/catgame/feed'),
                    ];
                }

                $public_user_id = (int) $public_user->ID;
                $location = CatGame_Auth::get_user_default_location($public_user_id);
                if ($location['city'] === '' || $location['country'] === '') {
                    $latest_location = CatGame_Submissions::latest_submission_location($public_user_id);
                    if ($location['city'] === '') {
                        $location['city'] = $latest_location['city'] ?? '';
                    }
                    if ($location['country'] === '') {
                        $location['country'] = $latest_location['country'] ?? '';
                    }
                }

                $active_items = [];
                if ($event) {
                    $active_items = CatGame_Submissions::list_user_submissions((int) $public_user_id, (int) $event['id'], 30);
                }
                $recent_items = CatGame_Submissions::list_user_recent_closed_submissions((int) $public_user_id, 30, 30);

                $active_items = self::with_reaction_payload($active_items, $current_user_id);
                $recent_items = self::with_reaction_payload($recent_items, $current_user_id);

                return [
                    'page' => 'user',
                    'event' => $event,
                    'user_not_found' => false,
                    'public_user_id' => $public_user_id,
                    'public_profile_username' => (string) $public_user->user_login,
                    'public_profile_location' => $location,
                    'active_items' => $active_items,
                    'recent_items' => $recent_items,
                    'viewer_logged_in' => is_user_logged_in(),
                    'viewer_user_id' => $current_user_id,
                    'public_profile_url' => self::public_profile_url((string) $public_user->user_login),
                ];

            case 'home':
                $top_items = $event ? self::with_reaction_payload(CatGame_Submissions::leaderboard((int) $event['id'], 'global', '', '', 3), $current_user_id) : [];
                $latest_items = $event ? self::with_reaction_payload(CatGame_Submissions::list_feed((int) $event['id'], 5, 0), $current_user_id) : [];
                return [
                    'page' => 'home',
                    'event' => $event,
                    'top_items' => $top_items,
                    'latest_items' => $latest_items,
                    'top3_positions' => $top3_positions,
                    'current_user_id' => $current_user_id,
                ];
            default:
                return ['page' => 'home', 'event' => $event];
        }
    }

    public static function render_page(string $page, array $data): void {
        $file = CATGAME_PLUGIN_DIR . 'templates/pages/' . $page . '.php';
        if (file_exists($file)) {
            include $file;
            return;
        }

        echo '<p>Página no encontrada.</p>';
    }

    public static function nav_items(): array {
        return [
            ['label' => 'Inicio', 'url' => home_url('/catgame/'), 'page' => 'home'],
            ['label' => 'Subir', 'url' => home_url('/catgame/upload'), 'page' => 'upload'],
            ['label' => 'Publicaciones', 'url' => home_url('/catgame/feed'), 'page' => 'feed'],
            ['label' => 'Clasificación', 'url' => home_url('/catgame/leaderboard'), 'page' => 'leaderboard'],
            ['label' => 'Mi perfil', 'url' => home_url('/catgame/profile'), 'page' => 'profile'],
        ];
    }
}
