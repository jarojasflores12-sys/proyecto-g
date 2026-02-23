<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Reactions {
    private const NONCE_ACTION = 'catgame_reactions';

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

        $submission_id = isset($_POST['submission_id']) ? (int) $_POST['submission_id'] : 0;
        $reaction_type = sanitize_key(wp_unslash($_POST['reaction_type'] ?? ''));

        if ($submission_id <= 0 || !in_array($reaction_type, self::allowed_reactions(), true)) {
            wp_send_json_error(['message' => 'Datos de reacción inválidos.'], 400);
        }

        $submission = CatGame_Submissions::get_submission($submission_id);
        if (!$submission) {
            wp_send_json_error(['message' => 'La publicación no existe.'], 404);
        }

        global $wpdb;
        $table = CatGame_DB::table('reactions');
        $user_id = get_current_user_id();

        $existing_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE submission_id = %d AND user_id = %d LIMIT 1",
                $submission_id,
                $user_id
            )
        );

        if ($existing_id > 0) {
            $wpdb->update(
                $table,
                ['reaction_type' => $reaction_type],
                ['id' => $existing_id],
                ['%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table,
                [
                    'submission_id' => $submission_id,
                    'user_id' => $user_id,
                    'reaction_type' => $reaction_type,
                    'created_at' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s']
            );
        }

        wp_send_json_success(self::reaction_counts($submission_id, $user_id));
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


    public static function endpoint_add_or_update_url(): string {
        return admin_url('admin-post.php?action=catgame_add_or_update_reaction');
    }

    public static function endpoint_get_counts_url(): string {
        return admin_url('admin-post.php?action=catgame_get_reaction_counts');
    }

    public static function reaction_labels(): array {
        return [
            'adorable' => ['emoji' => '😻', 'label' => 'Adorable'],
            'funny' => ['emoji' => '😂', 'label' => 'Me hizo reír'],
            'cute' => ['emoji' => '🥰', 'label' => 'Tierno'],
            'wow' => ['emoji' => '🤩', 'label' => 'Impresionante'],
            'epic' => ['emoji' => '🔥', 'label' => 'Épico'],
        ];
    }

    public static function render_widget(int $submission_id, bool $is_logged_in): void {
        if ($submission_id <= 0) {
            return;
        }

        $labels = self::reaction_labels();
        ?>
        <div class="cg-reactions" data-submission-id="<?php echo (int) $submission_id; ?>" data-logged-in="<?php echo $is_logged_in ? '1' : '0'; ?>">
            <div class="cg-reaction-buttons" role="group" aria-label="Reacciones de la publicación">
                <?php foreach ($labels as $slug => $meta): ?>
                    <button type="button" class="cg-reaction-btn" data-reaction="<?php echo esc_attr($slug); ?>">
                        <?php echo esc_html($meta['emoji'] . ' ' . $meta['label']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php if (!$is_logged_in): ?><small class="cg-reaction-help">Inicia sesión para reaccionar.</small><?php endif; ?>
        </div>
        <?php
    }

    private static function verify_nonce(): bool {
        $nonce = wp_unslash($_REQUEST['_wpnonce'] ?? '');
        return is_string($nonce) && wp_verify_nonce($nonce, self::NONCE_ACTION) !== false;
    }
}
