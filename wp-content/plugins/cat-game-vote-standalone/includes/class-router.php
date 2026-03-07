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
        add_rewrite_rule('^catgame/about/?$', 'index.php?catgame_page=about', 'top');
        add_rewrite_rule('^catgame/user/([^/]+)/?$', 'index.php?catgame_page=user&catgame_username=$matches[1]', 'top');
        add_rewrite_rule('^catgame/submission/([0-9]+)/?$', 'index.php?catgame_page=submission&submission_id=$matches[1]', 'top');
    }

    public static function query_vars(array $vars): array {
        $vars[] = 'catgame_page';
        $vars[] = 'submission_id';
        $vars[] = 'scope';
        $vars[] = 'country';
        $vars[] = 'city';
        $vars[] = 'catgame_username';
        return $vars;
    }

    public static function template_redirect(): void {
        $page = get_query_var('catgame_page');
        if (!$page) {
            $page = self::resolve_page_from_request_uri();
        }

        if (!$page) {
            return;
        }


        set_query_var('catgame_page', $page);
        status_header(200);
        CatGame_Render::render_layout($page);
        exit;
    }

    private static function resolve_page_from_request_uri(): string {
        $request_uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if ($request_uri === '') {
            return '';
        }

        $path = wp_parse_url($request_uri, PHP_URL_PATH);
        if (!is_string($path)) {
            return '';
        }

        $base_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
        if (!is_string($base_path)) {
            $base_path = '/';
        }

        $normalized_base = trim($base_path, '/');
        $normalized_path = trim($path, '/');

        if ($normalized_base !== '' && strpos($normalized_path, $normalized_base . '/') === 0) {
            $normalized_path = substr($normalized_path, strlen($normalized_base) + 1);
        } elseif ($normalized_base !== '' && $normalized_path === $normalized_base) {
            $normalized_path = '';
        }

        if ($normalized_path === 'catgame') {
            return 'home';
        }

        if ($normalized_path === 'catgame/upload') {
            return 'upload';
        }

        if ($normalized_path === 'catgame/feed') {
            return 'feed';
        }

        if ($normalized_path === 'catgame/leaderboard') {
            return 'leaderboard';
        }

        if ($normalized_path === 'catgame/profile') {
            return 'profile';
        }

        if ($normalized_path === 'catgame/about') {
            return 'about';
        }

        if (preg_match('#^catgame/user/([^/]+)$#', $normalized_path, $matches)) {
            set_query_var('catgame_username', sanitize_user(rawurldecode((string) $matches[1]), true));
            return 'user';
        }

        if (preg_match('#^catgame/submission/(\d+)$#', $normalized_path, $matches)) {
            set_query_var('submission_id', (int) $matches[1]);
            return 'submission';
        }

        return '';
    }
}
