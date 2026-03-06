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

        if (!$event) {
            return null;
        }

        return self::hydrate_event_payload($event);
    }

    public static function get_active_competitive_event(): ?array {
        $event = self::get_active_event();
        if (!$event) {
            return null;
        }

        return (($event['event_type'] ?? 'competitive') === 'competitive') ? $event : null;
    }

    public static function get_event(int $event_id): ?array {
        global $wpdb;
        $table = CatGame_DB::table('events');
        $event = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $event_id), ARRAY_A);
        if (!$event) {
            return null;
        }

        return self::hydrate_event_payload($event);
    }

    public static function get_event_winners(int $event_id): ?array {
        if ($event_id <= 0) {
            return null;
        }

        global $wpdb;
        $table = CatGame_DB::table('event_winners');
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE event_id = %d LIMIT 1", $event_id),
            ARRAY_A
        );

        return is_array($row) ? $row : null;
    }

    public static function finalize_competitive_event_winners(int $event_id): void {
        if ($event_id <= 0) {
            return;
        }

        $event = self::get_event($event_id);
        if (!$event || (($event['event_type'] ?? 'competitive') !== 'competitive')) {
            return;
        }

        if (self::get_event_winners($event_id)) {
            return;
        }

        $top = class_exists('CatGame_Submissions')
            ? CatGame_Submissions::leaderboard($event_id, 'global', '', '', 3, [])
            : [];

        global $wpdb;
        $table = CatGame_DB::table('event_winners');
        $wpdb->insert(
            $table,
            [
                'event_id' => $event_id,
                'first_place_submission_id' => isset($top[0]['id']) ? (int) $top[0]['id'] : null,
                'second_place_submission_id' => isset($top[1]['id']) ? (int) $top[1]['id'] : null,
                'third_place_submission_id' => isset($top[2]['id']) ? (int) $top[2]['id'] : null,
                'finalized_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%d', '%s']
        );
    }

    public static function finalize_ended_competitive_events(): void {
        global $wpdb;
        $events_table = CatGame_DB::table('events');
        $winners_table = CatGame_DB::table('event_winners');
        $now = current_time('mysql');

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.id
                 FROM {$events_table} e
                 LEFT JOIN {$winners_table} w ON w.event_id = e.id
                 WHERE e.event_type = %s AND e.ends_at < %s AND w.id IS NULL",
                'competitive',
                $now
            ),
            ARRAY_A
        );

        foreach ($rows as $row) {
            self::finalize_competitive_event_winners((int) ($row['id'] ?? 0));
        }
    }

    public static function list_historical_winners(int $limit = 20): array {
        global $wpdb;

        $limit = max(1, min(100, $limit));
        $events_table = CatGame_DB::table('events');
        $winners_table = CatGame_DB::table('event_winners');
        $submissions_table = CatGame_DB::table('submissions');

        $sql = "SELECT
                e.id AS event_id,
                e.name AS event_name,
                e.starts_at,
                e.ends_at,
                w.finalized_at,
                w.first_place_submission_id,
                w.second_place_submission_id,
                w.third_place_submission_id,
                s1.title AS first_title,
                s1.attachment_id AS first_attachment_id,
                s1.user_id AS first_user_id,
                s2.title AS second_title,
                s2.attachment_id AS second_attachment_id,
                s2.user_id AS second_user_id,
                s3.title AS third_title,
                s3.attachment_id AS third_attachment_id,
                s3.user_id AS third_user_id
            FROM {$winners_table} w
            INNER JOIN {$events_table} e ON e.id = w.event_id
            LEFT JOIN {$submissions_table} s1 ON s1.id = w.first_place_submission_id
            LEFT JOIN {$submissions_table} s2 ON s2.id = w.second_place_submission_id
            LEFT JOIN {$submissions_table} s3 ON s3.id = w.third_place_submission_id
            WHERE e.event_type = 'competitive'
              AND e.ends_at < %s
            ORDER BY w.finalized_at DESC, e.ends_at DESC, e.id DESC
            LIMIT %d";

        $rows = $wpdb->get_results($wpdb->prepare($sql, current_time('mysql'), $limit), ARRAY_A);
        if (!is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'event_id' => (int) ($row['event_id'] ?? 0),
                'event_name' => sanitize_text_field((string) ($row['event_name'] ?? 'Evento')),
                'starts_at' => sanitize_text_field((string) ($row['starts_at'] ?? '')),
                'ends_at' => sanitize_text_field((string) ($row['ends_at'] ?? '')),
                'finalized_at' => sanitize_text_field((string) ($row['finalized_at'] ?? '')),
                'winners' => [
                    'first' => [
                        'submission_id' => (int) ($row['first_place_submission_id'] ?? 0),
                        'title' => sanitize_text_field((string) ($row['first_title'] ?? '')),
                        'attachment_id' => (int) ($row['first_attachment_id'] ?? 0),
                        'user_id' => (int) ($row['first_user_id'] ?? 0),
                    ],
                    'second' => [
                        'submission_id' => (int) ($row['second_place_submission_id'] ?? 0),
                        'title' => sanitize_text_field((string) ($row['second_title'] ?? '')),
                        'attachment_id' => (int) ($row['second_attachment_id'] ?? 0),
                        'user_id' => (int) ($row['second_user_id'] ?? 0),
                    ],
                    'third' => [
                        'submission_id' => (int) ($row['third_place_submission_id'] ?? 0),
                        'title' => sanitize_text_field((string) ($row['third_title'] ?? '')),
                        'attachment_id' => (int) ($row['third_attachment_id'] ?? 0),
                        'user_id' => (int) ($row['third_user_id'] ?? 0),
                    ],
                ],
            ];
        }

        return $result;
    }


    public static function normalize_rules_payload(?string $rules_json): array {
        $normalized_items = [];
        $decoded = null;
        if (is_string($rules_json) && trim($rules_json) !== '') {
            $decoded = json_decode($rules_json, true);
        }

        if (is_array($decoded) && isset($decoded['mode'])) {
            $mode = sanitize_key((string) ($decoded['mode'] ?? 'mixed'));
            if ($mode === 'none') {
                return ['mode' => 'none', 'items' => []];
            }

            if (isset($decoded['items']) && is_array($decoded['items'])) {
                foreach ($decoded['items'] as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $title = sanitize_text_field((string) ($item['title'] ?? ''));
                    $type = sanitize_key((string) ($item['type'] ?? 'tema'));
                    $desc = sanitize_text_field((string) ($item['desc'] ?? ''));
                    if ($title === '') {
                        continue;
                    }
                    if (!in_array($type, ['tema', 'bonus', 'penalizacion'], true)) {
                        $type = 'tema';
                    }

                    $value = null;
                    if ($type !== 'tema' && is_numeric($item['value'] ?? null)) {
                        $value = round((float) $item['value'], 2);
                    }

                    $normalized_items[] = [
                        'title' => $title,
                        'type' => $type,
                        'value' => $value,
                        'desc' => $desc,
                    ];
                }
            }

            return [
                'mode' => empty($normalized_items) ? 'none' : 'mixed',
                'items' => $normalized_items,
            ];
        }

        if (!is_string($rules_json) || trim($rules_json) === '') {
            return ['mode' => 'none', 'items' => []];
        }

        $legacy_items = [];
        $legacy_rules = self::decode_rules($rules_json);
        foreach ($legacy_rules as $key => $value) {
            if (!is_numeric($value)) {
                continue;
            }
            $legacy_items[] = [
                'title' => self::legacy_rule_label((string) $key),
                'type' => 'bonus',
                'value' => round((float) $value, 2),
                'desc' => '',
            ];
        }

        return [
            'mode' => empty($legacy_items) ? 'none' : 'legacy',
            'items' => $legacy_items,
        ];
    }

    public static function general_rules_summary(): array {
        return [
            'Solo mascotas domésticas.',
            'Sin personas visibles en la foto.',
            'Sin contenido explícito, sexual o violento.',
            'Respeto a la comunidad y reportes de buena fe.',
            'Sanciones: se elimina publicación; moderada bloquea subir 3 días; grave bloquea subir/reaccionar y puede terminar en ban permanente.',
        ];
    }

    public static function build_rules_popup_view(array $event): array {
        $name = sanitize_text_field((string) ($event['name'] ?? 'Evento vigente'));
        $start_ts = isset($event['starts_at']) ? strtotime((string) $event['starts_at']) : false;
        $end_ts = isset($event['ends_at']) ? strtotime((string) $event['ends_at']) : false;
        $date_range = '';
        if ($start_ts && $end_ts) {
            $date_range = wp_date('d/m/Y H:i', $start_ts) . ' - ' . wp_date('d/m/Y H:i', $end_ts);
        }

        $rules = is_array($event['rules'] ?? null)
            ? $event['rules']
            : self::normalize_rules_payload(isset($event['rules_json']) ? (string) $event['rules_json'] : null);
        $mode = sanitize_key((string) ($rules['mode'] ?? 'none'));
        $items = isset($rules['items']) && is_array($rules['items']) ? $rules['items'] : [];

        return [
            'name' => $name !== '' ? $name : 'Evento vigente',
            'date_range' => $date_range,
            'event_type' => ($event['event_type'] ?? 'competitive') === 'thematic' ? 'thematic' : 'competitive',
            'mode' => $mode === 'none' ? 'none' : $mode,
            'items' => $items,
            'general_summary' => self::general_rules_summary(),
        ];
    }

    private static function hydrate_event_payload(array $event): array {
        $rules = self::normalize_rules_payload(isset($event['rules_json']) ? (string) $event['rules_json'] : null);
        $event_type = sanitize_key((string) ($event['event_type'] ?? 'competitive'));
        if (!in_array($event_type, ['competitive', 'thematic'], true)) {
            $event_type = 'competitive';
        }
        $event['event_type'] = $event_type;
        $event['rules'] = $rules;
        $event['no_rules'] = ($rules['mode'] ?? 'none') === 'none';
        $event['event_updated_at'] = (string) ($event['created_at'] ?? '');

        return $event;
    }

    private static function legacy_rule_label(string $key): string {
        $labels = [
            'black_cat' => 'Gato negro',
            'night_photo' => 'Foto nocturna',
            'funny_pose' => 'Pose divertida',
            'weird_place' => 'Lugar raro',
        ];

        return $labels[$key] ?? ucwords(str_replace(['_', '-'], ' ', $key));
    }

    public static function decode_rules(?string $rules_json): array {
        $default = [
            'black_cat' => 1.0,
            'night_photo' => 0.5,
            'funny_pose' => 0.5,
            'weird_place' => 0.5,
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
