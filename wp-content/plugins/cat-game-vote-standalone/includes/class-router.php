<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Router {
    public static function init(): void {
        add_action('init', [__CLASS__, 'add_rewrite_rules']);
        add_filter('query_vars', [__CLASS__, 'query_vars']);
        add_action('template_redirect', [__CLASS__, 'template_redirect']);
    }

    public static function add_rewrite_rules(): void {
        add_rewrite_rule('^catgame/?$', 'index.php?catgame_page=home', 'top');
        add_rewrite_rule('^catgame/upload/?$', 'index.php?catgame_page=upload', 'top');
        add_rewrite_rule('^catgame/feed/?$', 'index.php?catgame_page=feed', 'top');
        add_rewrite_rule('^catgame/leaderboard/?$', 'index.php?catgame_page=leaderboard', 'top');
        add_rewrite_rule('^catgame/profile/?$', 'index.php?catgame_page=profile', 'top');
        add_rewrite_rule('^catgame/submission/([0-9]+)/?$', 'index.php?catgame_page=submission&submission_id=$matches[1]', 'top');
    }

    public static function query_vars(array $vars): array {
        $vars[] = 'catgame_page';
        $vars[] = 'submission_id';
        $vars[] = 'scope';
        $vars[] = 'country';
        $vars[] = 'city';
        return $vars;
    }

    public static function template_redirect(): void {
        $page = get_query_var('catgame_page');
        if (!$page) {
            return;
        }

        status_header(200);
        CatGame_Render::render_layout($page);
        exit;
    }
}
