<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_DB {
    private const SCHEMA_VERSION = '3';
    private const SCHEMA_OPTION_KEY = 'catgame_schema_version';

    public static function init(): void {
        self::maybe_upgrade();
    }

    public static function table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . 'catgame_' . $name;
    }

    public static function activate(): void {
        self::create_tables();
        update_option(self::SCHEMA_OPTION_KEY, self::SCHEMA_VERSION, false);
        CatGame_Router::add_rewrite_rules();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $submissions = self::table('submissions');
        $votes = self::table('votes');
        $events = self::table('events');

        $sql = [];
        $sql[] = "CREATE TABLE {$events} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(200) NOT NULL,
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 0,
            rules_json LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY is_active (is_active)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$submissions} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            event_id BIGINT UNSIGNED NOT NULL,
            city VARCHAR(120) NOT NULL,
            country VARCHAR(120) NOT NULL,
            tags_json LONGTEXT NULL,
            tags_text LONGTEXT NULL,
            attachment_id BIGINT UNSIGNED NOT NULL,
            image_size_bytes BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            score_cached DECIMAL(5,2) NOT NULL DEFAULT 0,
            votes_count INT UNSIGNED NOT NULL DEFAULT 0,
            votes_sum INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY event_status_score (event_id, status, score_cached),
            KEY geo (country, city),
            KEY user_id (user_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$votes} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_vote (submission_id, user_id),
            KEY submission_id (submission_id),
            KEY user_id (user_id)
        ) {$charset};";

        foreach ($sql as $statement) {
            dbDelta($statement);
        }
    }

    private static function maybe_upgrade(): void {
        $installed = (string) get_option(self::SCHEMA_OPTION_KEY, '0');
        if ($installed === self::SCHEMA_VERSION) {
            return;
        }

        self::create_tables();
        update_option(self::SCHEMA_OPTION_KEY, self::SCHEMA_VERSION, false);
    }
}
