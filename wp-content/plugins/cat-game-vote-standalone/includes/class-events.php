<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Events {
    public static function init(): void {
    }

    public static function get_active_event(): ?array {
        global $wpdb;
        $table = CatGame_DB::table('events');
        $now = current_time('mysql');

        $event = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE is_active = 1 AND starts_at <= %s AND ends_at >= %s ORDER BY id DESC LIMIT 1",
                $now,
                $now
            ),
            ARRAY_A
        );

        return $event ?: null;
    }

    public static function get_event(int $event_id): ?array {
        global $wpdb;
        $table = CatGame_DB::table('events');
        $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $event_id), ARRAY_A);
        return $event ?: null;
    }

    public static function decode_rules(?string $rules_json): array {
        $default = [
            'tag_black_cat' => 1.0,
            'tag_night_photo' => 0.5,
            'tag_funny_pose' => 0.5,
            'tag_weird_place' => 0.5,
        ];

        if (!$rules_json) {
            return $default;
        }

        $decoded = json_decode($rules_json, true);
        if (!is_array($decoded)) {
            return $default;
        }

        foreach ($default as $key => $value) {
            if (!isset($decoded[$key]) || !is_numeric($decoded[$key])) {
                $decoded[$key] = $value;
            } else {
                $decoded[$key] = (float) $decoded[$key];
            }
        }

        return $decoded;
    }

    public static function set_active(int $event_id): void {
        global $wpdb;
        $table = CatGame_DB::table('events');
        $wpdb->query("UPDATE {$table} SET is_active = 0");
        $wpdb->update($table, ['is_active' => 1], ['id' => $event_id], ['%d'], ['%d']);
        CatGame_Submissions::clear_leaderboard_cache();
    }
}
