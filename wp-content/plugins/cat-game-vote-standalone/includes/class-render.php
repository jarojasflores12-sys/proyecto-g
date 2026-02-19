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
                return ['page' => $page, 'event' => $event];
            case 'feed':
                return ['page' => $page, 'event' => $event, 'submissions' => $event ? CatGame_Submissions::list_feed((int) $event['id']) : []];
            case 'submission':
                $submission_id = (int) get_query_var('submission_id');
                $submission = CatGame_Submissions::get_submission($submission_id);
                $rules = $submission ? CatGame_Events::decode_rules(CatGame_Events::get_event((int) $submission['event_id'])['rules_json'] ?? null) : [];
                return ['page' => $page, 'event' => $event, 'submission' => $submission, 'rules' => $rules];
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
                if (!is_user_logged_in()) {
                    return ['page' => $page, 'event' => $event, 'requires_login' => true];
                }
                $user_id = get_current_user_id();
                return [
                    'page' => $page,
                    'event' => $event,
                    'requires_login' => false,
                    'items' => CatGame_Submissions::list_user_submissions($user_id),
                    'stats' => CatGame_Submissions::user_stats($user_id),
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
            'Home' => home_url('/catgame/'),
            'Upload' => home_url('/catgame/upload'),
            'Feed' => home_url('/catgame/feed'),
            'Leaderboard' => home_url('/catgame/leaderboard'),
            'Profile' => home_url('/catgame/profile'),
        ];
    }
}
