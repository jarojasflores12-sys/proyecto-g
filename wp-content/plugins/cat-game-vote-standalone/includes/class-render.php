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
        include CATGAME_PLUGIN_DIR . 'templates/layout.php';
    }

    private static function page_data(string $page): array {
        $event = CatGame_Events::get_active_event();

        switch ($page) {
            case 'upload':
                $user_tags = is_user_logged_in() ? CatGame_Submissions::available_tags_for_user(get_current_user_id()) : CatGame_Submissions::predefined_tags();
                return ['page' => $page, 'event' => $event, 'user_tags' => $user_tags];
            case 'feed':
                $filter_tag = CatGame_Submissions::normalize_tag(wp_unslash($_GET['tag'] ?? ''));
                $feed_tags = is_user_logged_in()
                    ? CatGame_Submissions::available_tags_for_user(get_current_user_id())
                    : CatGame_Submissions::predefined_tags();

                return [
                    'page' => $page,
                    'event' => $event,
                    'selected_tag' => $filter_tag,
                    'feed_tags' => $feed_tags,
                    'submissions' => $event ? CatGame_Submissions::list_feed((int) $event['id'], 20, 0, $filter_tag) : [],
                ];
            case 'submission':
                $submission_id = (int) get_query_var('submission_id');
                $submission = CatGame_Submissions::get_submission($submission_id);
                $already_voted = false;
                if ($submission && is_user_logged_in()) {
                    $already_voted = CatGame_Votes::has_user_voted((int) $submission['id'], get_current_user_id());
                }

                return [
                    'page' => $page,
                    'event' => $event,
                    'submission' => $submission,
                    'already_voted' => $already_voted,
                ];
            case 'leaderboard':
                $scope = sanitize_text_field($_GET['scope'] ?? 'global');
                $country = sanitize_text_field(wp_unslash($_GET['country'] ?? ''));
                $city = sanitize_text_field(wp_unslash($_GET['city'] ?? ''));
                if (!in_array($scope, ['global', 'country', 'city'], true)) {
                    $scope = 'global';
                }
                $items = $event ? CatGame_Submissions::leaderboard((int) $event['id'], $scope, $country, $city, 20) : [];
                return ['page' => $page, 'event' => $event, 'scope' => $scope, 'country' => $country, 'city' => $city, 'items' => $items];
            case 'profile':
                $register_error = sanitize_text_field(wp_unslash($_GET['register_error'] ?? ''));
                $registered = isset($_GET['registered']) ? (int) $_GET['registered'] : 0;
                $tag_deleted = isset($_GET['tag_deleted']) ? (int) $_GET['tag_deleted'] : 0;

                if (!is_user_logged_in()) {
                    return [
                        'page' => $page,
                        'event' => $event,
                        'requires_login' => true,
                        'register_error' => $register_error,
                        'registered' => $registered,
                    ];
                }

                $user_id = get_current_user_id();
                return [
                    'page' => $page,
                    'event' => $event,
                    'requires_login' => false,
                    'register_error' => $register_error,
                    'registered' => $registered,
                    'tag_deleted' => $tag_deleted,
                    'items' => CatGame_Submissions::list_user_submissions($user_id),
                    'stats' => CatGame_Submissions::user_stats($user_id),
                    'custom_tags' => CatGame_Submissions::user_custom_tag_map($user_id),
                ];
            case 'home':
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
