<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_DB {
    private const SCHEMA_VERSION = '10';
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
        if (class_exists('CatGame_Reports')) {
            CatGame_Reports::deactivate();
        }

        flush_rewrite_rules();
    }

    public static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $submissions = self::table('submissions');
        $votes = self::table('votes');
        $events = self::table('events');
        $reactions = self::table('reactions');
        $reports = self::table('reports');
        $strikes = self::table('strikes');
        $notifications = self::table('notifications');
        $moderation_actions = self::table('moderation_actions');
        $appeals = self::table('appeals');
        $grave_cases = self::table('grave_cases');
        $perma_bans = self::table('perma_bans');
        $bans = self::table('bans');
        $infractions = self::table('infractions');

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
            title VARCHAR(80) NULL,
            attachment_id BIGINT UNSIGNED NOT NULL,
            image_size_bytes BIGINT UNSIGNED NULL,
            is_hidden TINYINT(1) NOT NULL DEFAULT 0,
            hidden_reason VARCHAR(32) NULL,
            hidden_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            score_cached DECIMAL(5,2) NOT NULL DEFAULT 0,
            votes_count INT UNSIGNED NOT NULL DEFAULT 0,
            votes_sum INT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY event_status_score (event_id, status, score_cached),
            KEY geo (country, city),
            KEY user_id (user_id),
            KEY hidden_idx (is_hidden, event_id)
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

        $sql[] = "CREATE TABLE {$reactions} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            reaction_type VARCHAR(20) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY unique_reaction (submission_id, user_id),
            KEY submission_id (submission_id),
            KEY user_id (user_id)
        ) {$charset};";



        $sql[] = "CREATE TABLE {$reports} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id BIGINT UNSIGNED NOT NULL,
            reported_user_id BIGINT UNSIGNED NOT NULL,
            reason VARCHAR(24) NOT NULL,
            detail VARCHAR(250) NULL,
            created_at DATETIME NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            resolved_at DATETIME NULL,
            resolution VARCHAR(20) NULL,
            admin_user_id BIGINT UNSIGNED NULL,
            severity VARCHAR(20) NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_submission_reporter (submission_id, reported_user_id),
            KEY submission_id (submission_id),
            KEY status (status),
            KEY reported_user_id (reported_user_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$strikes} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            kind ENUM('author','reporter') NOT NULL,
            severity ENUM('leve','moderado','grave') NOT NULL,
            reason_code VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            admin_user_id BIGINT UNSIGNED NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY expires_at (expires_at),
            KEY user_expires (user_id, expires_at)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$notifications} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            message VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            read_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY read_at (read_at)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$moderation_actions} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            action VARCHAR(24) NOT NULL,
            severity VARCHAR(24) NOT NULL,
            reason VARCHAR(24) NOT NULL,
            detail VARCHAR(250) NULL,
            decided_by BIGINT UNSIGNED NOT NULL,
            decided_at DATETIME NOT NULL,
            prev_action_id BIGINT UNSIGNED NULL,
            is_current TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY submission_id (submission_id),
            KEY user_id (user_id),
            KEY is_current (is_current)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$appeals} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            submission_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            decided_at DATETIME NULL,
            decided_by BIGINT UNSIGNED NULL,
            admin_note TEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_submission (submission_id),
            KEY submission_id (submission_id),
            KEY user_id (user_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$infractions} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            submission_id BIGINT UNSIGNED NULL,
            severity VARCHAR(20) NOT NULL,
            points INT NOT NULL,
            reason_code VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            decided_by BIGINT UNSIGNED NULL,
            reversed_at DATETIME NULL,
            reverse_reason VARCHAR(64) NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY submission_id (submission_id),
            KEY expires_at (expires_at)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$bans} (
            user_id BIGINT UNSIGNED NOT NULL,
            upload_banned_until DATETIME NULL,
            react_banned_until DATETIME NULL,
            hard_hold_until DATETIME NULL,
            perma_banned TINYINT(1) NOT NULL DEFAULT 0,
            perma_banned_at DATETIME NULL,
            PRIMARY KEY (user_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$perma_bans} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email_hash CHAR(64) NOT NULL,
            created_at DATETIME NOT NULL,
            reason_code VARCHAR(64) NOT NULL,
            note VARCHAR(255) NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email_hash (email_hash)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$grave_cases} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            submission_id BIGINT UNSIGNED NOT NULL,
            decided_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            appeal_status VARCHAR(20) NOT NULL DEFAULT 'none',
            case_status VARCHAR(20) NOT NULL DEFAULT 'open',
            closed_at DATETIME NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY submission_id (submission_id),
            KEY case_status (case_status)
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
