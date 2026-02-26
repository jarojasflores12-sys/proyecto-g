<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Reactions {
    private const NONCE_ACTION = 'catgame_reactions';
    private const RATE_LIMIT_MAX_REQUESTS = 20;
    private const RATE_LIMIT_WINDOW_SECONDS = 60;

    public static function init(): void {
        add_action('admin_post_catgame_add_or_update_reaction', [__CLASS__, 'handle_add_or_update_reaction']);
        add_action('admin_post_nopriv_catgame_add_or_update_reaction', [__CLASS__, 'handle_add_or_update_reaction']);
        add_action('admin_post_catgame_get_reaction_counts', [__CLASS__, 'handle_get_reaction_counts']);
        add_action('admin_post_nopriv_catgame_get_reaction_counts', [__CLASS__, 'handle_get_reaction_counts']);

        add_action('wp_ajax_catgame_add_or_update_reaction', [__CLASS__, 'handle_add_or_update_reaction']);
        add_action('wp_ajax_nopriv_catgame_add_or_update_reaction', [__CLASS__, 'handle_add_or_update_reaction']);
        add_action('wp_ajax_nopriv_catgame_get_reaction_counts', [__CLASS__, 'handle_get_reaction_counts']);
        add_action('wp_ajax_catgame_get_reaction_counts', [__CLASS__, 'handle_get_reaction_counts']);
    }

    public static function allowed_reactions(): array {
        return ['adorable', 'funny', 'cute', 'wow', 'epic'];
    }

    public static function nonce_action(): string {
        return self::NONCE_ACTION;
    }

    public static function handle_add_or_update_reaction(): void {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión para reaccionar.'], 401);
        }

        if (!self::verify_nonce()) {
            wp_send_json_error(['message' => 'Solicitud inválida (nonce).'], 403);
        }

        $user_id = get_current_user_id();
        $block_message = '';
        if (class_exists('CatGame_Reports') && !CatGame_Reports::can_user_participate($user_id, $block_message)) {
            wp_send_json_error(['message' => $block_message], 403);
        }
        $retry_after = 0;
        if (!self::within_rate_limit($user_id, $retry_after)) {
            wp_send_json_error([
                'message' => 'Has alcanzado el límite de reacciones. Espera un minuto e intenta nuevamente.',
                'code' => 'rate_limited',
                'retry_after' => $retry_after,
            ], 429);
        }

        $submission_id = isset($_POST['submission_id']) ? (int) $_POST['submission_id'] : 0;
        $reaction_type = sanitize_key(wp_unslash($_POST['reaction_type'] ?? ''));

        if ($submission_id <= 0 || !in_array($reaction_type, self::allowed_reactions(), true)) {
            wp_send_json_error(['message' => 'Datos de reacción inválidos.'], 400);
        }

        $submission = CatGame_Submissions::get_submission($submission_id);
        if (!$submission || !empty($submission['is_hidden'])) {
            wp_send_json_error(['message' => 'La publicación no está disponible.'], 404);
        }

        global $wpdb;
        $table = CatGame_DB::table('reactions');

        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id, reaction_type FROM {$table} WHERE submission_id = %d AND user_id = %d LIMIT 1",
                $submission_id,
                $user_id
            ),
            ARRAY_A
        );

        $existing_id = (int) ($existing['id'] ?? 0);
        $old_type = sanitize_key((string) ($existing['reaction_type'] ?? ''));
        if (!in_array($old_type, self::allowed_reactions(), true)) {
            $old_type = null;
        }

        if ($existing_id > 0) {
            $updated = $wpdb->update(
                $table,
                [
                    'reaction_type' => $reaction_type,
                ],
                ['id' => $existing_id],
                ['%s'],
                ['%d']
            );

            if ($updated === false) {
                wp_send_json_error(['message' => 'No se pudo actualizar la reacción.'], 500);
            }
        } else {
            $inserted = $wpdb->insert(
                $table,
                [
                    'submission_id' => $submission_id,
                    'user_id' => $user_id,
                    'reaction_type' => $reaction_type,
                    'created_at' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s']
            );

            if ($inserted === false) {
                wp_send_json_error(['message' => 'No se pudo registrar la reacción.'], 500);
            }
        }

        $counts = self::reaction_counts($submission_id, $user_id);
        $counts['old_type'] = $old_type;
        $counts['new_type'] = $reaction_type;

        wp_send_json_success($counts);
    }

    public static function handle_get_reaction_counts(): void {
        if (!self::verify_nonce()) {
            wp_send_json_error(['message' => 'Solicitud inválida (nonce).'], 403);
        }

        $submission_id = isset($_GET['submission_id']) ? (int) $_GET['submission_id'] : 0;
        if ($submission_id <= 0) {
            wp_send_json_error(['message' => 'submission_id inválido.'], 400);
        }

        $user_id = is_user_logged_in() ? get_current_user_id() : 0;
        wp_send_json_success(self::reaction_counts($submission_id, $user_id));
    }

    public static function reaction_counts(int $submission_id, int $user_id = 0): array {
        $counts = array_fill_keys(self::allowed_reactions(), 0);

        global $wpdb;
        $table = CatGame_DB::table('reactions');
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT reaction_type, COUNT(*) AS total FROM {$table} WHERE submission_id = %d GROUP BY reaction_type",
                $submission_id
            ),
            ARRAY_A
        );

        $total = 0;
        foreach ($rows as $row) {
            $type = sanitize_key((string) ($row['reaction_type'] ?? ''));
            if (!array_key_exists($type, $counts)) {
                continue;
            }
            $value = (int) ($row['total'] ?? 0);
            $counts[$type] = $value;
            $total += $value;
        }

        $user_reaction = null;
        if ($user_id > 0) {
            $user_reaction_raw = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT reaction_type FROM {$table} WHERE submission_id = %d AND user_id = %d LIMIT 1",
                    $submission_id,
                    $user_id
                )
            );
            if (is_string($user_reaction_raw) && in_array($user_reaction_raw, self::allowed_reactions(), true)) {
                $user_reaction = $user_reaction_raw;
            }
        }

        return $counts + [
            'total' => $total,
            'user_reaction' => $user_reaction,
        ];
    }



    public static function reaction_payload_map(array $submission_ids, int $user_id = 0): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $submission_ids), static function (int $id): bool {
            return $id > 0;
        })));

        if (empty($ids)) {
            return [];
        }

        $base_counts = array_fill_keys(self::allowed_reactions(), 0);
        $result = [];
        foreach ($ids as $id) {
            $result[$id] = [
                'reaction_counts' => $base_counts,
                'my_reaction' => null,
            ];
        }

        global $wpdb;
        $table = CatGame_DB::table('reactions');
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT submission_id, reaction_type, COUNT(*) AS total FROM {$table} WHERE submission_id IN ({$placeholders}) GROUP BY submission_id, reaction_type",
                ...$ids
            ),
            ARRAY_A
        );

        foreach ($rows as $row) {
            $submission_id = (int) ($row['submission_id'] ?? 0);
            $reaction_type = sanitize_key((string) ($row['reaction_type'] ?? ''));
            if (!isset($result[$submission_id]['reaction_counts'][$reaction_type])) {
                continue;
            }
            $result[$submission_id]['reaction_counts'][$reaction_type] = (int) ($row['total'] ?? 0);
        }

        if ($user_id > 0) {
            $user_rows = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT submission_id, reaction_type FROM {$table} WHERE user_id = %d AND submission_id IN ({$placeholders})",
                    $user_id,
                    ...$ids
                ),
                ARRAY_A
            );

            foreach ($user_rows as $row) {
                $submission_id = (int) ($row['submission_id'] ?? 0);
                $reaction_type = sanitize_key((string) ($row['reaction_type'] ?? ''));
                if (!isset($result[$submission_id])) {
                    continue;
                }
                if (in_array($reaction_type, self::allowed_reactions(), true)) {
                    $result[$submission_id]['my_reaction'] = $reaction_type;
                }
            }
        }

        return $result;
    }

    public static function endpoint_add_or_update_url(): string {
        return admin_url('admin-post.php?action=catgame_add_or_update_reaction');
    }

    public static function endpoint_get_counts_url(): string {
        return admin_url('admin-post.php?action=catgame_get_reaction_counts');
    }

    public static function reaction_labels(): array {
        return [
            'adorable' => ['emoji' => '😺', 'label' => 'Adorable'],
            'funny' => ['emoji' => '😂', 'label' => 'Me hizo reír'],
            'cute' => ['emoji' => '🥰', 'label' => 'Tierno'],
            'wow' => ['emoji' => '🤩', 'label' => 'Impresionante'],
            'epic' => ['emoji' => '🔥', 'label' => 'Épico'],
        ];
    }

    public static function render_widget(int $submission_id, bool $is_logged_in, array $reaction_payload = [], array $options = []): void {
        if ($submission_id <= 0) {
            return;
        }

        $labels = self::reaction_labels();
        $counts = array_fill_keys(self::allowed_reactions(), 0);
        $incoming_counts = $reaction_payload['reaction_counts'] ?? [];
        if (is_array($incoming_counts)) {
            foreach ($counts as $key => $value) {
                $counts[$key] = isset($incoming_counts[$key]) ? (int) $incoming_counts[$key] : 0;
            }
        }

        $my_reaction_raw = $reaction_payload['my_reaction'] ?? null;
        $my_reaction = is_string($my_reaction_raw) && in_array($my_reaction_raw, self::allowed_reactions(), true) ? $my_reaction_raw : null;
        $is_readonly = !empty($options['readonly']);
        $readonly_reason = sanitize_text_field((string) ($options['readonly_reason'] ?? ''));
        $readonly_message = $readonly_reason !== '' ? $readonly_reason : (!$is_logged_in ? 'Inicia sesión para reaccionar' : 'No disponible');
        ?>
        <div class="cg-reactions" data-submission-id="<?php echo (int) $submission_id; ?>" data-logged-in="<?php echo $is_logged_in ? '1' : '0'; ?>" data-readonly="<?php echo $is_readonly ? '1' : '0'; ?>" data-readonly-message="<?php echo esc_attr($readonly_message); ?>" data-my-reaction="<?php echo esc_attr($my_reaction ?? ''); ?>" data-reaction-counts="<?php echo esc_attr(wp_json_encode($counts)); ?>">
            <div class="cg-reaction-buttons" role="group" aria-label="Reacciones de la publicación">
                <?php foreach ($labels as $slug => $meta): ?>
                    <?php $is_selected = $my_reaction === $slug; ?>
                    <button type="button" class="cg-reaction-btn <?php echo $is_selected ? 'is-active is-selected' : ''; ?>" data-reaction="<?php echo esc_attr($slug); ?>" data-label="<?php echo esc_attr($meta['label']); ?>" <?php echo $is_readonly ? 'disabled title="' . esc_attr($readonly_message) . '"' : ''; ?>>
                        <span class="emoji"><?php echo esc_html($meta['emoji']); ?></span>
                        <span class="count"><?php echo (int) ($counts[$slug] ?? 0); ?></span>
                        <span class="catgv-tooltip"><?php echo esc_html($meta['label']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php if ($is_readonly): ?><small class="cg-reaction-help"><?php echo esc_html($readonly_message); ?></small><?php elseif (!$is_logged_in): ?><small class="cg-reaction-help">Inicia sesión para reaccionar.</small><?php endif; ?>
        </div>
        <?php
    }


    private static function within_rate_limit(int $user_id, int &$retry_after = 0): bool {
        if ($user_id <= 0) {
            return false;
        }

        $key = 'catgame_reaction_rl_' . $user_id;
        $now = time();
        $window = self::RATE_LIMIT_WINDOW_SECONDS;
        $max = self::RATE_LIMIT_MAX_REQUESTS;

        $bucket = get_transient($key);
        if (!is_array($bucket)) {
            $bucket = [
                'count' => 0,
                'reset_at' => $now + $window,
            ];
        }

        $count = isset($bucket['count']) ? (int) $bucket['count'] : 0;
        $reset_at = isset($bucket['reset_at']) ? (int) $bucket['reset_at'] : ($now + $window);

        if ($reset_at <= $now) {
            $count = 0;
            $reset_at = $now + $window;
        }

        if ($count >= $max) {
            $retry_after = max(1, $reset_at - $now);
            return false;
        }

        $count++;
        $bucket['count'] = $count;
        $bucket['reset_at'] = $reset_at;
        set_transient($key, $bucket, max(1, $reset_at - $now));

        return true;
    }

    private static function verify_nonce(): bool {
        $nonce = wp_unslash($_REQUEST['_wpnonce'] ?? '');
        return is_string($nonce) && wp_verify_nonce($nonce, self::NONCE_ACTION) !== false;
    }
}
