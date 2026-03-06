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
