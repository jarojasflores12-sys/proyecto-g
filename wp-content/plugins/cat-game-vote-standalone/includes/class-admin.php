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

        if (!in_array($page, ['catgame-events', 'catgame-settings'], true)) {
            return;
        }

        wp_enqueue_style(
            'catgame-admin',
            CATGAME_PLUGIN_URL . 'assets/admin.css',
            [],
            CATGAME_VERSION
        );

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
        $events = $wpdb->get_results("SELECT * FROM {$table} ORDER BY starts_at DESC", ARRAY_A);
        $selected_event_id = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;

        $event_in_edit = null;
        foreach ($events as $event_item) {
            if ((int) $event_item['id'] === $selected_event_id) {
                $event_in_edit = $event_item;
                break;
            }
        }

        if (!$event_in_edit && !empty($events)) {
            $event_in_edit = $events[0];
        }

        $form_name = $event_in_edit['name'] ?? '';
        $form_starts_at = isset($event_in_edit['starts_at']) ? self::to_datetime_local($event_in_edit['starts_at']) : '';
        $form_ends_at = isset($event_in_edit['ends_at']) ? self::to_datetime_local($event_in_edit['ends_at']) : '';
        $form_rules = $event_in_edit['rules_json'] ?? '{"black_cat":1.0,"night_photo":0.5,"funny_pose":0.5,"weird_place":0.5}';
        $form_is_active = isset($event_in_edit['is_active']) && (int) $event_in_edit['is_active'] === 1;
        $success = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        $now_ts = current_time('timestamp');
        ?>
        <div class="wrap catgame-admin-page">
            <h1>Cat Game - Gestor de eventos</h1>

            <?php if ($success === 'saved'): ?>
                <div class="notice notice-success is-dismissible"><p>Evento guardado correctamente.</p></div>
            <?php elseif ($success === 'activated'): ?>
                <div class="notice notice-success is-dismissible"><p>Evento activo actualizado.</p></div>
            <?php endif; ?>

            <div class="catgame-admin-grid">
                <section class="catgame-panel">
                    <h2>Creación / edición</h2>
                    <p class="description">Completa los campos y guarda. Si elegiste un evento en el listado, se actualizará ese evento.</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('catgame_save_event'); ?>
                        <input type="hidden" name="action" value="catgame_save_event" />
                        <input type="hidden" name="event_id" value="<?php echo (int) ($event_in_edit['id'] ?? 0); ?>" />
                        <table class="form-table" role="presentation">
                            <tr>
                                <th><label for="name">Nombre del evento</label></th>
                                <td><input class="regular-text" type="text" name="name" id="name" value="<?php echo esc_attr($form_name); ?>" required /></td>
                            </tr>
                            <tr>
                                <th><label for="starts_at">Inicio</label></th>
                                <td><input type="datetime-local" name="starts_at" id="starts_at" value="<?php echo esc_attr($form_starts_at); ?>" required /></td>
                            </tr>
                            <tr>
                                <th><label for="ends_at">Fin</label></th>
                                <td><input type="datetime-local" name="ends_at" id="ends_at" value="<?php echo esc_attr($form_ends_at); ?>" required /></td>
                            </tr>
                            <tr>
                                <th><label for="rules_json">Reglas (JSON)</label></th>
                                <td>
                                    <textarea name="rules_json" id="rules_json" rows="6" cols="60"><?php echo esc_textarea($form_rules); ?></textarea>
                                    <p class="description">Ejemplo: {"black_cat":1.0,"night_photo":0.5,"funny_pose":0.5,"weird_place":0.5}</p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="is_active">Evento activo</label></th>
                                <td><label><input type="checkbox" name="is_active" id="is_active" value="1" <?php checked($form_is_active); ?> /> Marcar como activo</label></td>
                            </tr>
                        </table>
                        <div class="catgame-actions-row">
                            <?php submit_button(($event_in_edit ? 'Actualizar evento' : 'Crear evento'), 'primary', 'submit', false); ?>
                            <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=catgame-events')); ?>">Nuevo evento</a>
                        </div>
                    </form>
                </section>

                <section class="catgame-panel">
                    <h2>Detalle</h2>
                    <?php if (!$event_in_edit): ?>
                        <p class="description">Todavía no hay eventos cargados.</p>
                    <?php else: ?>
                        <?php
                        $start_ts = strtotime((string) $event_in_edit['starts_at']);
                        $end_ts = strtotime((string) $event_in_edit['ends_at']);
                        $status_label = self::event_status_label($start_ts, $end_ts, (int) ($event_in_edit['is_active'] ?? 0), $now_ts);
                        ?>
                        <dl class="catgame-detail-list">
                            <dt>ID</dt><dd>#<?php echo (int) $event_in_edit['id']; ?></dd>
                            <dt>Nombre</dt><dd><?php echo esc_html((string) $event_in_edit['name']); ?></dd>
                            <dt>Estado</dt><dd><span class="catgame-pill"><?php echo esc_html($status_label); ?></span></dd>
                            <dt>Inicio</dt><dd><?php echo esc_html((string) $event_in_edit['starts_at']); ?></dd>
                            <dt>Fin</dt><dd><?php echo esc_html((string) $event_in_edit['ends_at']); ?></dd>
                        </dl>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('catgame_save_event'); ?>
                            <input type="hidden" name="action" value="catgame_save_event" />
                            <input type="hidden" name="activate_id" value="<?php echo (int) $event_in_edit['id']; ?>" />
                            <?php submit_button('Marcar como evento activo', 'secondary', 'submit', false); ?>
                        </form>
                    <?php endif; ?>
                </section>
            </div>

            <section class="catgame-panel">
                <h2>Listado de eventos</h2>
                <?php if (empty($events)): ?>
                    <p class="description">No hay eventos creados todavía.</p>
                <?php else: ?>
                    <table class="widefat striped">
                        <thead><tr><th>ID</th><th>Nombre</th><th>Vigencia</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($events as $event): ?>
                            <?php
                            $start_ts = strtotime((string) $event['starts_at']);
                            $end_ts = strtotime((string) $event['ends_at']);
                            $status_label = self::event_status_label($start_ts, $end_ts, (int) $event['is_active'], $now_ts);
                            ?>
                            <tr>
                                <td><?php echo (int) $event['id']; ?></td>
                                <td><?php echo esc_html($event['name']); ?></td>
                                <td><?php echo esc_html($event['starts_at'] . ' - ' . $event['ends_at']); ?></td>
                                <td><span class="catgame-pill"><?php echo esc_html($status_label); ?></span></td>
                                <td class="catgame-table-actions">
                                    <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=catgame-events&event_id=' . (int) $event['id'])); ?>">Editar / ver detalle</a>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <?php wp_nonce_field('catgame_save_event'); ?>
                                        <input type="hidden" name="action" value="catgame_save_event" />
                                        <input type="hidden" name="activate_id" value="<?php echo (int) $event['id']; ?>" />
                                        <?php submit_button('Activar', 'secondary small', 'submit', false); ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <section class="catgame-panel">
                <h2>Calendario de eventos</h2>
                <div class="catgame-calendar-grid">
                    <?php if (empty($events)): ?>
                        <p class="description">No hay datos para mostrar en el calendario.</p>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
                            <?php
                            $start_ts = strtotime((string) $event['starts_at']);
                            $end_ts = strtotime((string) $event['ends_at']);
                            $status_label = self::event_status_label($start_ts, $end_ts, (int) $event['is_active'], $now_ts);
                            ?>
                            <article class="catgame-calendar-item">
                                <strong><?php echo esc_html($event['name']); ?></strong>
                                <span><?php echo esc_html(wp_date('d M Y H:i', $start_ts)); ?> → <?php echo esc_html(wp_date('d M Y H:i', $end_ts)); ?></span>
                                <span class="catgame-pill"><?php echo esc_html($status_label); ?></span>
                            </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
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
            wp_safe_redirect(admin_url('admin.php?page=catgame-events&status=activated'));
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

        $data = [
            'name' => $name,
            'starts_at' => gmdate('Y-m-d H:i:s', strtotime($starts_at)),
            'ends_at' => gmdate('Y-m-d H:i:s', strtotime($ends_at)),
            'is_active' => $is_active,
            'rules_json' => $rules_json,
        ];

        if ($event_id > 0) {
            $wpdb->update(
                $table,
                $data,
                ['id' => $event_id],
                ['%s', '%s', '%s', '%d', '%s'],
                ['%d']
            );
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(
                $table,
                $data,
                ['%s', '%s', '%s', '%d', '%s', '%s']
            );
            $event_id = (int) $wpdb->insert_id;
        }

        if ($is_active && $event_id > 0) {
            CatGame_Events::set_active($event_id);
        }

        CatGame_Submissions::clear_leaderboard_cache();

        wp_safe_redirect(admin_url('admin.php?page=catgame-events&status=saved&event_id=' . $event_id));
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

    private static function to_datetime_local(string $datetime): string {
        return str_replace(' ', 'T', substr($datetime, 0, 16));
    }

    private static function event_status_label(int $start_ts, int $end_ts, int $is_active, int $now_ts): string {
        if ($is_active === 1 && $start_ts <= $now_ts && $end_ts >= $now_ts) {
            return 'Activo';
        }

        if ($end_ts < $now_ts) {
            return 'Finalizado';
        }

        if ($start_ts > $now_ts) {
            return 'Próximo';
        }

        return $is_active === 1 ? 'Marcado como activo' : 'Inactivo';
    }
}
