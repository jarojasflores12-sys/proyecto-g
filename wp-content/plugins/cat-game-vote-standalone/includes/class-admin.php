<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Admin {
    private const SETTINGS_OPTION_KEY = 'catgame_settings';

    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_catgame_save_event', [__CLASS__, 'save_event']);
        add_action('admin_post_catgame_toggle_submission', [__CLASS__, 'toggle_submission']);
        add_action('admin_post_catgame_save_settings', [__CLASS__, 'save_settings']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_admin_assets']);
        add_action('admin_notices', [__CLASS__, 'permalink_notice']);
    }

    public static function menu(): void {
        add_menu_page('Cat Game', 'Cat Game', 'manage_options', 'catgame-events', [__CLASS__, 'events_page'], 'dashicons-pets', 56);
        add_submenu_page('catgame-events', 'Events', 'Events', 'manage_options', 'catgame-events', [__CLASS__, 'events_page']);
        add_submenu_page('catgame-events', 'Moderation', 'Moderation', 'manage_options', 'catgame-moderation', [__CLASS__, 'moderation_page']);
        add_submenu_page('catgame-events', 'Ajustes', 'Ajustes', 'manage_options', 'catgame-settings', [__CLASS__, 'settings_page']);
    }

    public static function enqueue_admin_assets(): void {
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if ($page !== 'catgame-settings') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'catgame-admin-settings',
            CATGAME_PLUGIN_URL . 'assets/admin-settings.js',
            ['jquery'],
            CATGAME_VERSION,
            true
        );
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

        $selected_event_id = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
        $event_in_edit = null;

        if ($selected_event_id > 0) {
            foreach ($events as $event) {
                if ((int) $event['id'] === $selected_event_id) {
                    $event_in_edit = $event;
                    break;
                }
            }
        }

        $show_event_not_found_notice = $selected_event_id > 0 && $event_in_edit === null;
        $default_rules_json = '{"black_cat":1.0,"night_photo":0.5,"funny_pose":0.5,"weird_place":0.5}';
        $name_value = (string) ($event_in_edit['name'] ?? '');
        $starts_at_value = $event_in_edit !== null ? gmdate('Y-m-d\TH:i', strtotime((string) $event_in_edit['starts_at'])) : '';
        $ends_at_value = $event_in_edit !== null ? gmdate('Y-m-d\TH:i', strtotime((string) $event_in_edit['ends_at'])) : '';
        $rules_json_value = (string) ($event_in_edit['rules_json'] ?? $default_rules_json);
        $is_active_checked = $event_in_edit !== null && (int) ($event_in_edit['is_active'] ?? 0) === 1;
        ?>
        <div class="wrap">
            <h1>Cat Game - Events</h1>
            <p><a class="button" href="<?php echo esc_url(admin_url('admin.php?page=catgame-events&event_id=0')); ?>">Nuevo evento</a></p>
            <?php if ($show_event_not_found_notice): ?>
                <div class="notice notice-warning"><p><?php echo esc_html__('El evento solicitado no existe. Puedes crear uno nuevo.', 'cat-game-vote-standalone'); ?></p></div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('catgame_save_event'); ?>
                <input type="hidden" name="action" value="catgame_save_event" />
                <input type="hidden" name="event_id" value="<?php echo (int) ($event_in_edit['id'] ?? 0); ?>" />
                <table class="form-table">
                    <tr><th><label for="name">Name</label></th><td><input class="regular-text" type="text" name="name" id="name" value="<?php echo esc_attr($name_value); ?>" required /></td></tr>
                    <tr><th><label for="starts_at">Starts At</label></th><td><input type="datetime-local" name="starts_at" id="starts_at" value="<?php echo esc_attr($starts_at_value); ?>" required /></td></tr>
                    <tr><th><label for="ends_at">Ends At</label></th><td><input type="datetime-local" name="ends_at" id="ends_at" value="<?php echo esc_attr($ends_at_value); ?>" required /></td></tr>
                    <tr><th><label for="rules_json">Rules JSON</label></th><td><textarea name="rules_json" id="rules_json" rows="6" cols="60"><?php echo esc_textarea($rules_json_value); ?></textarea></td></tr>
                    <tr><th><label for="is_active">Active</label></th><td><input type="checkbox" name="is_active" id="is_active" value="1" <?php checked($is_active_checked); ?> /></td></tr>
                </table>
                <?php submit_button($event_in_edit === null ? 'Create Event' : 'Update Event'); ?>
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
                            <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=catgame-events&event_id=' . (int) $event['id'])); ?>">Edit</a>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-left:6px;">
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

        $event_id = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
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

        if ($event_id > 0) {
            $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE id = %d", $event_id));
            if ($exists > 0) {
                $wpdb->update(
                    $table,
                    [
                        'name' => $name,
                        'starts_at' => gmdate('Y-m-d H:i:s', strtotime($starts_at)),
                        'ends_at' => gmdate('Y-m-d H:i:s', strtotime($ends_at)),
                        'is_active' => $is_active,
                        'rules_json' => $rules_json,
                    ],
                    ['id' => $event_id],
                    ['%s', '%s', '%s', '%d', '%s'],
                    ['%d']
                );
            } else {
                wp_safe_redirect(admin_url('admin.php?page=catgame-events&event_id=0'));
                exit;
            }
        } else {
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
            $event_id = (int) $wpdb->insert_id;
        }

        if ($is_active && $event_id > 0) {
            CatGame_Events::set_active($event_id);
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

    public static function settings_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        $settings = self::get_settings();
        $background_id = (int) ($settings['background_image_id'] ?? 0);
        $background_url = (string) ($settings['background_image_url'] ?? '');
        ?>
        <div class="wrap">
            <h1>Cat Game - Ajustes</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('catgame_save_settings'); ?>
                <input type="hidden" name="action" value="catgame_save_settings" />
                <table class="form-table">
                    <tr>
                        <th><label for="catgame_background_image_url">Imagen de fondo</label></th>
                        <td>
                            <input type="hidden" name="background_image_id" id="catgame_background_image_id" value="<?php echo esc_attr((string) $background_id); ?>" />
                            <input class="regular-text" type="url" name="background_image_url" id="catgame_background_image_url" value="<?php echo esc_url($background_url); ?>" placeholder="https://..." />
                            <p>
                                <button type="button" class="button" id="catgame_select_background">Seleccionar desde biblioteca</button>
                                <button type="button" class="button" id="catgame_clear_background">Quitar fondo</button>
                            </p>
                            <p class="description">Se aplica como fondo del sitio en las páginas públicas de Cat Game.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Guardar ajustes'); ?>
            </form>
        </div>
        <?php
    }

    public static function save_settings(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('catgame_save_settings');

        $background_id = isset($_POST['background_image_id']) ? (int) $_POST['background_image_id'] : 0;
        $background_url = esc_url_raw(wp_unslash($_POST['background_image_url'] ?? ''));

        if ($background_id > 0) {
            $attachment_url = wp_get_attachment_image_url($background_id, 'full');
            if (is_string($attachment_url) && $attachment_url !== '') {
                $background_url = $attachment_url;
            }
        }

        update_option(
            self::SETTINGS_OPTION_KEY,
            [
                'background_image_id' => $background_id,
                'background_image_url' => $background_url,
            ],
            false
        );

        wp_safe_redirect(admin_url('admin.php?page=catgame-settings'));
        exit;
    }

    public static function get_settings(): array {
        $settings = get_option(self::SETTINGS_OPTION_KEY, []);

        return is_array($settings) ? $settings : [];
    }
}
