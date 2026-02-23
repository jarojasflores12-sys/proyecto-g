<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Votes {
    private const DAILY_LIMIT = 50;

    public static function init(): void {
        add_action('admin_post_catgame_vote', [__CLASS__, 'handle_vote']);
    }

    public static function has_user_voted(int $submission_id, int $user_id): bool {
        if ($submission_id <= 0 || $user_id <= 0) {
            return false;
        }

        global $wpdb;
        $table = CatGame_DB::table('votes');
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE submission_id = %d AND user_id = %d LIMIT 1",
                $submission_id,
                $user_id
            )
        );

        return !empty($existing);
    }

    public static function handle_vote(): void {
        if (!is_user_logged_in()) {
            wp_die('Debes iniciar sesión para votar.');
        }

        check_admin_referer('catgame_vote');

        $submission_id = isset($_POST['submission_id']) ? (int) $_POST['submission_id'] : 0;
        $rating = isset($_POST['rating']) ? (int) $_POST['rating'] : 0;

        if ($submission_id <= 0 || $rating < 1 || $rating > 5) {
            wp_safe_redirect(add_query_arg('catgame_error', 'invalid_vote', wp_get_referer() ?: home_url('/catgame/feed')));
            exit;
        }

        $current_user = get_current_user_id();
        if (!self::within_daily_limit($current_user)) {
            wp_safe_redirect(add_query_arg('catgame_error', 'vote_limit', wp_get_referer() ?: home_url('/catgame/feed')));
            exit;
        }

        $submission = CatGame_Submissions::get_submission($submission_id);
        if (!$submission || $submission['status'] !== 'active') {
            wp_safe_redirect(add_query_arg('catgame_error', 'submission_unavailable', home_url('/catgame/feed')));
            exit;
        }

        if (self::has_user_voted($submission_id, $current_user)) {
            wp_safe_redirect(add_query_arg('catgame_error', 'duplicate_vote', home_url('/catgame/feed')));
            exit;
        }

        global $wpdb;
        $table = CatGame_DB::table('votes');

        $inserted = $wpdb->insert(
            $table,
            [
                'submission_id' => $submission_id,
                'user_id' => $current_user,
                'rating' => $rating,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s']
        );

        if (!$inserted) {
            wp_safe_redirect(add_query_arg('catgame_error', 'vote_failed', home_url('/catgame/feed')));
            exit;
        }

        self::increment_daily_count($current_user);

        $subs_table = CatGame_DB::table('submissions');
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$subs_table} SET votes_count = votes_count + 1, votes_sum = votes_sum + %d WHERE id = %d",
                $rating,
                $submission_id
            )
        );

        CatGame_Submissions::recalculate_score($submission_id);

        wp_safe_redirect(add_query_arg('voted', '1', home_url('/catgame/feed')));
        exit;
    }

    private static function daily_key(int $user_id): string {
        return 'catgame_votes_' . $user_id . '_' . gmdate('Ymd');
    }

    public static function within_daily_limit(int $user_id): bool {
        $count = (int) get_user_meta($user_id, self::daily_key($user_id), true);
        return $count < self::DAILY_LIMIT;
    }

    private static function increment_daily_count(int $user_id): void {
        $key = self::daily_key($user_id);
        $count = (int) get_user_meta($user_id, $key, true);
        update_user_meta($user_id, $key, $count + 1);
    }
}
