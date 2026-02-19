<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Admin {
    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_catgame_save_event', [__CLASS__, 'save_event']);
        add_action('admin_post_catgame_toggle_submission', [__CLASS__, 'toggle_submission']);
        add_action('admin_notices', [__CLASS__, 'permalink_notice']);
    }

    public static function menu(): void {
        add_menu_page('Cat Game', 'Cat Game', 'manage_options', 'catgame-events', [__CLASS__, 'events_page'], 'dashicons-pets', 56);
        add_submenu_page('catgame-events', 'Events', 'Events', 'manage_options', 'catgame-events', [__CLASS__, 'events_page']);
        add_submenu_page('catgame-events', 'Moderation', 'Moderation', 'manage_options', 'catgame-moderation', [__CLASS__, 'moderation_page']);
    }

    public static function permalink_notice(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        echo '<div class="notice notice-info"><p>Cat Game: Si las rutas no funcionan, ir a Ajustes &gt; Enlaces permanentes y Guardar.</p></div>';
    }

    public static function events_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        global $wpdb;
        $table = CatGame_DB::table('events');
        $events = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A);
        ?>
        <div class="wrap">
            <h1>Cat Game - Events</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('catgame_save_event'); ?>
                <input type="hidden" name="action" value="catgame_save_event" />
                <table class="form-table">
                    <tr><th><label for="name">Name</label></th><td><input class="regular-text" type="text" name="name" id="name" required /></td></tr>
                    <tr><th><label for="starts_at">Starts At</label></th><td><input type="datetime-local" name="starts_at" id="starts_at" required /></td></tr>
                    <tr><th><label for="ends_at">Ends At</label></th><td><input type="datetime-local" name="ends_at" id="ends_at" required /></td></tr>
                    <tr><th><label for="rules_json">Rules JSON</label></th><td><textarea name="rules_json" id="rules_json" rows="6" cols="60">{"tag_black_cat":1.0,"tag_night_photo":0.5,"tag_funny_pose":0.5,"tag_weird_place":0.5}</textarea></td></tr>
                    <tr><th><label for="is_active">Active</label></th><td><input type="checkbox" name="is_active" id="is_active" value="1" /></td></tr>
                </table>
                <?php submit_button('Save Event'); ?>
            </form>

            <h2>Existing events</h2>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Name</th><th>Range</th><th>Active</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo (int) $event['id']; ?></td>
                        <td><?php echo esc_html($event['name']); ?></td>
                        <td><?php echo esc_html($event['starts_at'] . ' - ' . $event['ends_at']); ?></td>
                        <td><?php echo (int) $event['is_active'] === 1 ? 'Yes' : 'No'; ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <?php wp_nonce_field('catgame_save_event'); ?>
                                <input type="hidden" name="action" value="catgame_save_event" />
                                <input type="hidden" name="activate_id" value="<?php echo (int) $event['id']; ?>" />
                                <?php submit_button('Set active', 'secondary small', 'submit', false); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function save_event(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('catgame_save_event');

        if (!empty($_POST['activate_id'])) {
            CatGame_Events::set_active((int) $_POST['activate_id']);
            wp_safe_redirect(admin_url('admin.php?page=catgame-events'));
            exit;
        }

        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $starts_at = sanitize_text_field(wp_unslash($_POST['starts_at'] ?? ''));
        $ends_at = sanitize_text_field(wp_unslash($_POST['ends_at'] ?? ''));
        $rules_json = wp_kses_post(wp_unslash($_POST['rules_json'] ?? ''));
        $is_active = !empty($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $starts_at === '' || $ends_at === '') {
            wp_safe_redirect(admin_url('admin.php?page=catgame-events'));
            exit;
        }

        global $wpdb;
        $table = CatGame_DB::table('events');

        $wpdb->insert(
            $table,
            [
                'name' => $name,
                'starts_at' => gmdate('Y-m-d H:i:s', strtotime($starts_at)),
                'ends_at' => gmdate('Y-m-d H:i:s', strtotime($ends_at)),
                'is_active' => $is_active,
                'rules_json' => $rules_json,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($is_active) {
            CatGame_Events::set_active((int) $wpdb->insert_id);
        }

        CatGame_Submissions::clear_leaderboard_cache();

        wp_safe_redirect(admin_url('admin.php?page=catgame-events'));
        exit;
    }

    public static function moderation_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        global $wpdb;
        $table = CatGame_DB::table('submissions');
        $items = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 100", ARRAY_A);
        ?>
        <div class="wrap">
            <h1>Cat Game - Moderation</h1>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th>User</th><th>Status</th><th>Score</th><th>Created</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo (int) $item['id']; ?></td>
                        <td><?php echo (int) $item['user_id']; ?></td>
                        <td><?php echo esc_html($item['status']); ?></td>
                        <td><?php echo esc_html($item['score_cached']); ?></td>
                        <td><?php echo esc_html($item['created_at']); ?></td>
                        <td>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                <?php wp_nonce_field('catgame_toggle_submission'); ?>
                                <input type="hidden" name="action" value="catgame_toggle_submission" />
                                <input type="hidden" name="submission_id" value="<?php echo (int) $item['id']; ?>" />
                                <input type="hidden" name="new_status" value="<?php echo $item['status'] === 'disqualified' ? 'active' : 'disqualified'; ?>" />
                                <?php submit_button($item['status'] === 'disqualified' ? 'Restore' : 'Disqualify', 'secondary small', 'submit', false); ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function toggle_submission(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('catgame_toggle_submission');
        $submission_id = isset($_POST['submission_id']) ? (int) $_POST['submission_id'] : 0;
        $new_status = sanitize_text_field(wp_unslash($_POST['new_status'] ?? 'active'));

        if ($submission_id > 0 && in_array($new_status, ['active', 'disqualified'], true)) {
            CatGame_Submissions::set_status($submission_id, $new_status);
        }

        wp_safe_redirect(admin_url('admin.php?page=catgame-moderation'));
        exit;
    }
}
