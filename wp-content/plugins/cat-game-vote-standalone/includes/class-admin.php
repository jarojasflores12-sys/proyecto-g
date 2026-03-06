<?php

if (!defined('ABSPATH')) {
    exit;
}

class CatGame_Admin {
    private const SETTINGS_OPTION_KEY = 'catgame_settings';

    public static function init(): void {
        add_action('admin_menu', [__CLASS__, 'menu']);
        add_action('admin_post_catgame_save_event', [__CLASS__, 'save_event']);
        add_action('admin_post_catgame_duplicate_event', [__CLASS__, 'duplicate_event']);
        add_action('admin_post_catgame_toggle_submission', [__CLASS__, 'toggle_submission']);
        add_action('admin_post_catgame_moderate_report', ['CatGame_Reports', 'handle_moderate_report']);
        add_action('admin_post_catgame_update_moderation_action', [__CLASS__, 'update_moderation_action']);
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
        $has_event_id_param = isset($_GET['event_id']);
        $mode = isset($_GET['mode']) ? sanitize_key(wp_unslash($_GET['mode'])) : '';
        $is_create_mode = $mode === 'create' || ($has_event_id_param && $selected_event_id === 0);

        $event_in_edit = null;
        if (!$is_create_mode && $selected_event_id > 0) {
            foreach ($events as $event_item) {
                if ((int) $event_item['id'] === $selected_event_id) {
                    $event_in_edit = $event_item;
                    break;
                }
            }
        }

        if (!$is_create_mode && !$has_event_id_param && !$event_in_edit && !empty($events)) {
            $event_in_edit = $events[0];
        }

        $form_name = $event_in_edit['name'] ?? '';
        $form_event_type = sanitize_key((string) ($event_in_edit['event_type'] ?? 'competitive'));
        if (!in_array($form_event_type, ['competitive', 'thematic'], true)) {
            $form_event_type = 'competitive';
        }
        $form_starts_at = isset($event_in_edit['starts_at']) ? self::to_datetime_local($event_in_edit['starts_at']) : '';
        $form_ends_at = isset($event_in_edit['ends_at']) ? self::to_datetime_local($event_in_edit['ends_at']) : '';
        $form_rules_data = self::parse_rules_for_admin($event_in_edit['rules_json'] ?? null);
        $form_is_active = isset($event_in_edit['is_active']) && (int) $event_in_edit['is_active'] === 1;
        $success = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        $now_ts = current_time('timestamp');
        ?>
        <div class="wrap catgame-admin-page">
            <h1>Cat Game - Gestor de eventos</h1>

            <?php if ($success === 'created'): ?>
                <div class="notice notice-success is-dismissible"><p>Evento creado correctamente.</p></div>
            <?php elseif ($success === 'updated'): ?>
                <div class="notice notice-success is-dismissible"><p>Evento actualizado correctamente.</p></div>
            <?php elseif ($success === 'duplicated'): ?>
                <div class="notice notice-success is-dismissible"><p>Evento duplicado correctamente.</p></div>
            <?php elseif ($success === 'saved'): ?>
                <div class="notice notice-success is-dismissible"><p>Evento guardado correctamente.</p></div>
            <?php elseif ($success === 'activated'): ?>
                <div class="notice notice-success is-dismissible"><p>Evento activo actualizado.</p></div>
            <?php endif; ?>

            <div class="catgame-admin-grid">
                <section class="catgame-panel">
                    <h2>Creación / edición</h2>
                    <p class="description">Usa "Crear evento" para uno nuevo o "Actualizar evento" cuando estés editando desde el listado.</p>
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
                                <th><label for="event_type">Tipo de evento</label></th>
                                <td>
                                    <select name="event_type" id="event_type">
                                        <option value="competitive" <?php selected($form_event_type, 'competitive'); ?>>Competitivo</option>
                                        <option value="thematic" <?php selected($form_event_type, 'thematic'); ?>>Temático</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="ends_at">Fin</label></th>
                                <td><input type="datetime-local" name="ends_at" id="ends_at" value="<?php echo esc_attr($form_ends_at); ?>" required /></td>
                            </tr>
                            <tr>
                                <th>Reglas del evento (opcional)</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="rules_disabled" value="1" <?php checked(!$form_rules_data['uses_rules']); ?> data-rules-disabled-toggle="1" />
                                        Este evento no usa reglas
                                    </label>

                                    <div class="catgame-rules-repeater" data-rules-repeater="1" <?php echo !$form_rules_data['uses_rules'] ? 'hidden' : ''; ?>>
                                        <div class="catgame-rules-list" data-rules-list="1">
                                            <?php foreach ($form_rules_data['items'] as $index => $rule_item): ?>
                                                <?php self::render_rule_row($index, $rule_item); ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <p>
                                            <button type="button" class="button" data-rules-add="1">Agregar regla</button>
                                        </p>
                                        <p class="description">Cada regla permite título, tipo, valor (si aplica) y descripción corta.</p>
                                    </div>

                                    <template id="catgame-rule-row-template">
                                        <?php self::render_rule_row('__INDEX__', ['title' => '', 'type' => 'tema', 'value' => '', 'desc' => '']); ?>
                                    </template>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="is_active">Evento activo</label></th>
                                <td><label><input type="checkbox" name="is_active" id="is_active" value="1" <?php checked($form_is_active); ?> /> Marcar como activo</label></td>
                            </tr>
                        </table>
                        <div class="catgame-actions-row">
                            <?php submit_button(($event_in_edit ? 'Actualizar evento' : 'Crear evento'), 'primary', 'submit', false); ?>
                            <a class="button button-secondary" href="<?php echo esc_url(admin_url('admin.php?page=catgame-events&mode=create&event_id=0')); ?>">Nuevo evento</a>
                        </div>
                    </form>
                </section>

                <section class="catgame-panel">
                    <h2>Detalle</h2>
                    <?php if (!$event_in_edit): ?>
                        <p class="description">Modo crear activo. Completa el formulario para crear un evento nuevo.</p>
                    <?php else: ?>
                        <?php
                        $start_ts = strtotime((string) $event_in_edit['starts_at']);
                        $end_ts = strtotime((string) $event_in_edit['ends_at']);
                        $status_label = self::event_status_label($start_ts, $end_ts, (int) ($event_in_edit['is_active'] ?? 0), $now_ts);
                        ?>
                        <dl class="catgame-detail-list">
                            <dt>ID</dt><dd>#<?php echo (int) $event_in_edit['id']; ?></dd>
                            <dt>Nombre</dt><dd><?php echo esc_html((string) $event_in_edit['name']); ?></dd>
                            <dt>Tipo</dt><dd><span class="catgame-pill"><?php echo esc_html((($event_in_edit['event_type'] ?? 'competitive') === 'thematic') ? 'Temático' : 'Competitivo'); ?></span></dd>
                            <dt>Estado</dt><dd><span class="catgame-pill"><?php echo esc_html($status_label); ?></span></dd>
                            <dt>Inicio</dt><dd><?php echo esc_html((string) $event_in_edit['starts_at']); ?></dd>
                            <dt>Fin</dt><dd><?php echo esc_html((string) $event_in_edit['ends_at']); ?></dd>
                        </dl>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('catgame_save_event'); ?>
                            <input type="hidden" name="action" value="catgame_save_event" />
                            <input type="hidden" name="activate_id" value="<?php echo (int) $event_in_edit['id']; ?>" />
                            <p><strong>Evento activo:</strong> usa este botón para dejar este evento como vigente en la app.</p>
                            <?php submit_button('Marcar como evento activo', 'primary', 'submit', false); ?>
                        </form>

                        <?php self::render_event_preview($event_in_edit, $now_ts); ?>
                    <?php endif; ?>
                </section>
            </div>

            <section class="catgame-panel">
                <h2>Listado de eventos</h2>
                <?php if (empty($events)): ?>
                    <p class="description">No hay eventos creados todavía.</p>
                <?php else: ?>
                    <table class="widefat striped">
                        <thead><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Vigencia</th><th>Estado</th><th>Acciones</th></tr></thead>
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
                                <td><span class="catgame-pill"><?php echo esc_html((($event['event_type'] ?? 'competitive') === 'thematic') ? 'Temático' : 'Competitivo'); ?></span></td>
                                <td><?php echo esc_html($event['starts_at'] . ' - ' . $event['ends_at']); ?></td>
                                <td><span class="catgame-pill"><?php echo esc_html($status_label); ?></span></td>
                                <td class="catgame-table-actions">
                                    <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=catgame-events&event_id=' . (int) $event['id'])); ?>">Editar / ver detalle</a>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <?php wp_nonce_field('catgame_duplicate_event'); ?>
                                        <input type="hidden" name="action" value="catgame_duplicate_event" />
                                        <input type="hidden" name="event_id" value="<?php echo (int) $event['id']; ?>" />
                                        <?php submit_button('Duplicar', 'secondary small', 'submit', false); ?>
                                    </form>
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
        <script>
            (function () {
                const panel = document.querySelector('[data-rules-repeater="1"]');
                const toggle = document.querySelector('[data-rules-disabled-toggle="1"]');
                const list = document.querySelector('[data-rules-list="1"]');
                const addBtn = document.querySelector('[data-rules-add="1"]');
                const tpl = document.getElementById('catgame-rule-row-template');
                if (!panel || !toggle || !list || !addBtn || !tpl) {
                    return;
                }

                const updateVisibility = () => {
                    panel.hidden = !!toggle.checked;
                };

                const normalizeIndexes = () => {
                    const rows = Array.from(list.querySelectorAll('[data-rule-row="1"]'));
                    rows.forEach((row, index) => {
                        row.querySelectorAll('[data-rule-field]').forEach((field) => {
                            const key = field.getAttribute('data-rule-field');
                            field.setAttribute('name', `rules_items[${index}][${key}]`);
                        });
                    });
                };

                const bindRow = (row) => {
                    const typeSelect = row.querySelector('[data-rule-field="type"]');
                    const valueInput = row.querySelector('[data-rule-field="value"]');
                    const removeBtn = row.querySelector('[data-rules-remove="1"]');
                    const onTypeChange = () => {
                        const isTema = (typeSelect?.value || '') === 'tema';
                        if (valueInput) {
                            valueInput.disabled = isTema;
                            valueInput.closest('.catgame-rule-col').hidden = isTema;
                            if (isTema) {
                                valueInput.value = '';
                            }
                        }
                    };

                    if (typeSelect) {
                        typeSelect.addEventListener('change', onTypeChange);
                    }

                    if (removeBtn) {
                        removeBtn.addEventListener('click', () => {
                            row.remove();
                            normalizeIndexes();
                        });
                    }

                    onTypeChange();
                };

                addBtn.addEventListener('click', () => {
                    const html = tpl.innerHTML.replace(/__INDEX__/g, String(list.querySelectorAll('[data-rule-row="1"]').length));
                    const wrapper = document.createElement('div');
                    wrapper.innerHTML = html.trim();
                    const row = wrapper.firstElementChild;
                    if (!row) {
                        return;
                    }
                    list.appendChild(row);
                    bindRow(row);
                    normalizeIndexes();
                });

                list.querySelectorAll('[data-rule-row="1"]').forEach((row) => bindRow(row));
                toggle.addEventListener('change', updateVisibility);
                normalizeIndexes();
                updateVisibility();
            })();
        </script>
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
        $event_type = sanitize_key(wp_unslash($_POST['event_type'] ?? 'competitive'));
        if (!in_array($event_type, ['competitive', 'thematic'], true)) {
            $event_type = 'competitive';
        }
        $rules_json = self::build_rules_json_from_post($_POST);
        $is_active = !empty($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $starts_at === '' || $ends_at === '') {
            wp_safe_redirect(admin_url('admin.php?page=catgame-events'));
            exit;
        }

        global $wpdb;
        $table = CatGame_DB::table('events');

        $data = [
            'name' => $name,
            'event_type' => $event_type,
            'starts_at' => gmdate('Y-m-d H:i:s', strtotime($starts_at)),
            'ends_at' => gmdate('Y-m-d H:i:s', strtotime($ends_at)),
            'is_active' => $is_active,
            'rules_json' => $rules_json,
        ];

        $saved_status = 'updated';

        if ($event_id > 0) {
            $wpdb->update(
                $table,
                $data,
                ['id' => $event_id],
                ['%s', '%s', '%s', '%s', '%d', '%s'],
                ['%d']
            );
        } else {
            $saved_status = 'created';
            $data['created_at'] = current_time('mysql');
            $wpdb->insert(
                $table,
                $data,
                ['%s', '%s', '%s', '%s', '%d', '%s', '%s']
            );
            $event_id = (int) $wpdb->insert_id;
        }

        if ($is_active && $event_id > 0) {
            CatGame_Events::set_active($event_id);
        }

        CatGame_Submissions::clear_leaderboard_cache();

        wp_safe_redirect(admin_url('admin.php?page=catgame-events&status=' . $saved_status . '&event_id=' . $event_id));
        exit;
    }

    public static function duplicate_event(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('catgame_duplicate_event');

        $event_id = isset($_POST['event_id']) ? (int) $_POST['event_id'] : 0;
        if ($event_id <= 0) {
            wp_safe_redirect(admin_url('admin.php?page=catgame-events'));
            exit;
        }

        $event = CatGame_Events::get_event($event_id);
        if (!$event) {
            wp_safe_redirect(admin_url('admin.php?page=catgame-events'));
            exit;
        }

        global $wpdb;
        $table = CatGame_DB::table('events');
        $duplicate_event_type = sanitize_key((string) ($event['event_type'] ?? 'competitive'));
        if (!in_array($duplicate_event_type, ['competitive', 'thematic'], true)) {
            $duplicate_event_type = 'competitive';
        }

        $wpdb->insert(
            $table,
            [
                'name' => 'Copia de ' . sanitize_text_field((string) ($event['name'] ?? 'Evento')),
                'event_type' => $duplicate_event_type,
                'starts_at' => (string) ($event['starts_at'] ?? current_time('mysql')),
                'ends_at' => (string) ($event['ends_at'] ?? current_time('mysql')),
                'is_active' => 0,
                'rules_json' => (string) ($event['rules_json'] ?? ''),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        $new_event_id = (int) $wpdb->insert_id;
        if ($new_event_id <= 0) {
            wp_safe_redirect(admin_url('admin.php?page=catgame-events'));
            exit;
        }

        wp_safe_redirect(admin_url('admin.php?page=catgame-events&event_id=' . $new_event_id . '&status=duplicated'));
        exit;
    }

    public static function moderation_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        global $wpdb;
        $reports_table = CatGame_DB::table('reports');
        $subs_table = CatGame_DB::table('submissions');
        $actions_table = CatGame_DB::table('moderation_actions');
        $status_filter = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'pending';
        if (!in_array($status_filter, ['pending', 'resolved'], true)) {
            $status_filter = 'pending';
        }

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.*, s.title, s.attachment_id, s.user_id AS author_id, s.is_hidden,
                    ma.id AS action_id, ma.action AS action_name, ma.severity AS action_severity, ma.reason AS action_reason, ma.detail AS action_detail
                FROM {$reports_table} r
                INNER JOIN {$subs_table} s ON s.id = r.submission_id
                LEFT JOIN {$actions_table} ma ON ma.submission_id = r.submission_id AND ma.is_current = 1
                WHERE r.status = %s
                ORDER BY r.created_at DESC
                LIMIT 200",
                $status_filter
            ),
            ARRAY_A
        );

        $notice = sanitize_key((string) wp_unslash($_GET['mod_notice'] ?? ''));
        $appeal_notice = sanitize_key((string) wp_unslash($_GET['appeal_notice'] ?? ''));
        $grave_enforced = isset($_GET['grave_enforced']) ? (int) $_GET['grave_enforced'] : 0;
        $grave_enforced_count = isset($_GET['grave_enforced_count']) ? (int) $_GET['grave_enforced_count'] : 0;
        $appeals = class_exists('CatGame_Reports') ? CatGame_Reports::list_pending_appeals(200) : [];
        $last_grave_run = class_exists('CatGame_Reports') ? CatGame_Reports::get_last_grave_enforcement_run() : [];
        $grave_run_history = class_exists('CatGame_Reports') ? CatGame_Reports::get_grave_enforcement_history() : [];
        $grave_history_source_filter = sanitize_key((string) wp_unslash($_GET['grave_history_source'] ?? 'all'));
        $grave_history_status_filter = sanitize_key((string) wp_unslash($_GET['grave_history_status'] ?? 'all'));

        if (!in_array($grave_history_source_filter, ['all', 'runtime', 'manual', 'cli'], true)) {
            $grave_history_source_filter = 'all';
        }
        if (!in_array($grave_history_status_filter, ['all', 'ok', 'error'], true)) {
            $grave_history_status_filter = 'all';
        }

        $grave_run_history = array_values(array_filter($grave_run_history, static function (array $run) use ($grave_history_source_filter, $grave_history_status_filter): bool {
            $source_ok = $grave_history_source_filter === 'all' || sanitize_key((string) ($run['source'] ?? '')) === $grave_history_source_filter;
            $status_ok = $grave_history_status_filter === 'all' || sanitize_key((string) ($run['status'] ?? '')) === $grave_history_status_filter;
            return $source_ok && $status_ok;
        }));
        ?>
        <div class="wrap catgame-admin-page">
            <h1>Cat Game - Moderación</h1>
            <?php if ($appeal_notice === 'accepted'): ?>
                <div class="notice notice-success is-dismissible"><p>Apelación aceptada y publicación restaurada.</p></div>
            <?php elseif ($appeal_notice === 'rejected'): ?>
                <div class="notice notice-warning is-dismissible"><p>Apelación rechazada.</p></div>
            <?php elseif ($appeal_notice === 'invalid'): ?>
                <div class="notice notice-error is-dismissible"><p>No se pudo procesar la apelación.</p></div>
            <?php endif; ?>
            <?php if ($grave_enforced === 1): ?>
                <div class="notice notice-success is-dismissible"><p>Enforcement manual ejecutado. Casos graves procesados: <?php echo (int) $grave_enforced_count; ?>.</p></div>
            <?php endif; ?>
            <?php if ($notice === 'updated'): ?>
                <div class="notice notice-success is-dismissible"><p>Acción de moderación actualizada.</p></div>
            <?php elseif ($notice === 'unchanged'): ?>
                <div class="notice notice-info is-dismissible"><p>Sin cambios: la acción ya coincide con la configuración actual.</p></div>
            <?php elseif ($notice === 'blocked_delete_account'): ?>
                <div class="notice notice-warning is-dismissible"><p>No se puede editar: la acción previa fue eliminación de cuenta.</p></div>
            <?php elseif ($notice === 'invalid'): ?>
                <div class="notice notice-error is-dismissible"><p>No se pudo actualizar la acción (datos inválidos).</p></div>
            <?php endif; ?>
            <section class="catgame-panel" style="margin-bottom:16px;">
                <h2>Enforcement casos graves</h2>
                <?php
                $last_run_at = sanitize_text_field((string) ($last_grave_run['ran_at'] ?? ''));
                $last_run_processed = (int) ($last_grave_run['processed'] ?? 0);
                ?>
                <p class="description">
                    Última ejecución: <strong><?php echo $last_run_at !== '' ? esc_html($last_run_at) : 'sin registros'; ?></strong>
                    <?php if ($last_run_at !== ''): ?>
                        · Casos procesados: <strong><?php echo (int) $last_run_processed; ?></strong>
                    <?php endif; ?>
                </p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('catgame_run_grave_enforcement'); ?>
                    <input type="hidden" name="action" value="catgame_run_grave_enforcement" />
                    <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>" />
                    <input type="hidden" name="grave_history_source" value="<?php echo esc_attr($grave_history_source_filter); ?>" />
                    <input type="hidden" name="grave_history_status" value="<?php echo esc_attr($grave_history_status_filter); ?>" />
                    <button type="submit" class="button">Ejecutar enforcement ahora</button>
                </form>
                <h3 style="margin-top:12px;">Historial corto</h3>
                <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="margin:8px 0 10px; display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                    <input type="hidden" name="page" value="catgame-moderation" />
                    <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>" />
                    <label>Origen
                        <select name="grave_history_source">
                            <option value="all" <?php selected($grave_history_source_filter, 'all'); ?>>Todos</option>
                            <option value="runtime" <?php selected($grave_history_source_filter, 'runtime'); ?>>runtime</option>
                            <option value="manual" <?php selected($grave_history_source_filter, 'manual'); ?>>manual</option>
                            <option value="cli" <?php selected($grave_history_source_filter, 'cli'); ?>>cli</option>
                        </select>
                    </label>
                    <label>Estado
                        <select name="grave_history_status">
                            <option value="all" <?php selected($grave_history_status_filter, 'all'); ?>>Todos</option>
                            <option value="ok" <?php selected($grave_history_status_filter, 'ok'); ?>>ok</option>
                            <option value="error" <?php selected($grave_history_status_filter, 'error'); ?>>error</option>
                        </select>
                    </label>
                    <button type="submit" class="button">Filtrar</button>
                </form>
                <p>
                    <button type="button" class="button" id="catgame-copy-grave-history">Copiar diagnóstico (JSON)</button>
                    <span id="catgame-copy-grave-history-status" style="margin-left:8px;"></span>
                </p>
                <textarea id="catgame-grave-history-json" readonly style="position:absolute; left:-9999px;"><?php echo esc_textarea(wp_json_encode($grave_run_history, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?></textarea>
                <table class="widefat striped" style="max-width:780px;">
                    <thead><tr><th>Fecha</th><th>Procesados</th><th>Origen</th><th>Duración</th><th>Estado</th></tr></thead>
                    <tbody>
                    <?php if (empty($grave_run_history)): ?>
                        <tr><td colspan="5">Sin historial para el filtro actual.</td></tr>
                    <?php else: ?>
                        <?php foreach ($grave_run_history as $run): ?>
                            <tr>
                                <td><?php echo esc_html((string) ($run['ran_at'] ?? '')); ?></td>
                                <td><?php echo (int) ($run['processed'] ?? 0); ?></td>
                                <td><?php echo esc_html((string) ($run['source'] ?? 'runtime')); ?></td>
                                <td><?php echo (int) ($run['duration_ms'] ?? 0); ?> ms</td>
                                <td><?php echo esc_html((string) ($run['status'] ?? 'ok')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <section class="catgame-panel" style="margin-bottom:16px;">
                <h2>Apelaciones pendientes</h2>
                <table class="widefat striped">
                    <thead><tr><th>Miniatura</th><th>Publicación</th><th>Usuario</th><th>Moderación actual</th><th>Fecha</th><th>Mensaje</th><th>Decisión</th></tr></thead>
                    <tbody>
                    <?php if (empty($appeals)): ?>
                        <tr><td colspan="7">No hay apelaciones pendientes.</td></tr>
                    <?php else: ?>
                        <?php foreach ($appeals as $appeal): ?>
                            <?php $appeal_user = get_userdata((int) ($appeal['user_id'] ?? 0)); ?>
                            <tr>
                                <td><?php echo wp_get_attachment_image((int) ($appeal['attachment_id'] ?? 0), [70, 70]); ?></td>
                                <td><strong><?php echo esc_html(CatGame_Submissions::title_label((array) $appeal)); ?></strong><br><small>#<?php echo (int) ($appeal['submission_id'] ?? 0); ?></small></td>
                                <td>@<?php echo esc_html($appeal_user ? (string) $appeal_user->user_login : 'usuario'); ?></td>
                                <td><small><?php echo esc_html((string) ($appeal['current_action'] ?? 'n/d')); ?><?php if (!empty($appeal['current_severity'])): ?> (<?php echo esc_html((string) $appeal['current_severity']); ?>)<?php endif; ?></small></td>
                                <td><?php echo esc_html((string) ($appeal['created_at'] ?? '')); ?></td>
                                <td><?php echo esc_html((string) ($appeal['message'] ?? '')); ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom:6px;">
                                        <?php wp_nonce_field('catgame_decide_appeal'); ?>
                                        <input type="hidden" name="action" value="catgame_decide_appeal" />
                                        <input type="hidden" name="appeal_id" value="<?php echo (int) ($appeal['id'] ?? 0); ?>" />
                                        <input type="hidden" name="decision" value="accepted" />
                                        <button type="submit" class="button button-small button-primary">Aceptar</button>
                                    </form>
                                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                        <?php wp_nonce_field('catgame_decide_appeal'); ?>
                                        <input type="hidden" name="action" value="catgame_decide_appeal" />
                                        <input type="hidden" name="appeal_id" value="<?php echo (int) ($appeal['id'] ?? 0); ?>" />
                                        <input type="hidden" name="decision" value="rejected" />
                                        <input type="text" name="admin_note" maxlength="500" placeholder="Nota admin (opcional)" style="width:100%; margin-bottom:4px;" />
                                        <button type="submit" class="button button-small">Rechazar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </section>

            <p>
                <a class="button <?php echo $status_filter === 'pending' ? 'button-primary' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'catgame-moderation', 'status' => 'pending', 'grave_history_source' => $grave_history_source_filter, 'grave_history_status' => $grave_history_status_filter], admin_url('admin.php'))); ?>">Pendientes</a>
                <a class="button <?php echo $status_filter === 'resolved' ? 'button-primary' : ''; ?>" href="<?php echo esc_url(add_query_arg(['page' => 'catgame-moderation', 'status' => 'resolved', 'grave_history_source' => $grave_history_source_filter, 'grave_history_status' => $grave_history_status_filter], admin_url('admin.php'))); ?>">Resueltos</a>
            </p>
            <details id="catgame-moderation-guide" style="margin:12px 0;">
                <summary><strong>Guía rápida: gravedad y acción</strong></summary>
                <ul style="margin-left:18px;">
                    <li><strong>restore</strong>: restaura visibilidad de la publicación.</li>
                    <li><strong>delete</strong>: oculta publicación reportada.</li>
                    <li><strong>strike</strong>: registra sanción sin borrar cuenta.</li>
                    <li><strong>suspend_3d</strong>: restricción temporal de subida por 3 días.</li>
                    <li><strong>delete_account</strong>: irreversible, bloquea edición posterior.</li>
                </ul>
            </details>
            <table class="widefat striped">
                <thead><tr><th>Miniatura</th><th>Publicación</th><th>Autor</th><th>Reportado por</th><th>Motivo</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8">No hay reportes para este filtro.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row): ?>
                    <?php
                    $author = get_userdata((int) ($row['author_id'] ?? 0));
                    $author_name = $author ? (string) $author->user_login : 'usuario';
                    $reporter = get_userdata((int) ($row['reported_user_id'] ?? 0));
                    $reporter_name = $reporter ? (string) $reporter->user_login : 'usuario';
                    $reason_label_map = [
                        'not_pet' => 'No es una mascota',
                        'human' => 'Aparece una persona',
                        'inappropriate' => 'Contenido inapropiado',
                        'other' => 'Otro',
                    ];
                    $reason = (string) ($row['reason'] ?? 'other');
                    $reason_label = $reason_label_map[$reason] ?? 'Otro';
                    ?>
                    <tr>
                        <td><?php echo wp_get_attachment_image((int) ($row['attachment_id'] ?? 0), [70, 70]); ?></td>
                        <td>
                            <strong><?php echo esc_html(CatGame_Submissions::title_label((array) $row)); ?></strong><br>
                            <small>#<?php echo (int) ($row['submission_id'] ?? 0); ?></small>
                        </td>
                        <td>@<?php echo esc_html($author_name); ?></td>
                        <td>@<?php echo esc_html($reporter_name); ?></td>
                        <td><?php echo esc_html($reason_label); ?><?php if (!empty($row['detail'])): ?><br><small><?php echo esc_html((string) $row['detail']); ?></small><?php endif; ?></td>
                        <td><?php echo esc_html((string) ($row['created_at'] ?? '')); ?></td>
                        <td><?php echo esc_html($status_filter === 'pending' ? 'Pendiente' : 'Resuelto'); ?></td>
                        <td>
                            <?php if ($status_filter === 'pending'): ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-right:6px;">
                                    <?php wp_nonce_field('catgame_moderate_report'); ?>
                                    <input type="hidden" name="action" value="catgame_moderate_report" />
                                    <input type="hidden" name="report_id" value="<?php echo (int) ($row['id'] ?? 0); ?>" />
                                    <input type="hidden" name="resolution" value="restored" />
                                    <button type="submit" class="button button-small">Restaurar</button>
                                </form>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-right:6px;">
                                    <?php wp_nonce_field('catgame_moderate_report'); ?>
                                    <input type="hidden" name="action" value="catgame_moderate_report" />
                                    <input type="hidden" name="report_id" value="<?php echo (int) ($row['id'] ?? 0); ?>" />
                                    <input type="hidden" name="resolution" value="removed" />
                                    <select name="severity" required>
                                        <option value="leve">Leve</option>
                                        <option value="moderado">Moderado</option>
                                        <option value="grave">Grave</option>
                                    </select>
                                    <button type="submit" class="button button-small button-primary">Eliminar</button>
                                </form>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                                    <?php wp_nonce_field('catgame_moderate_report'); ?>
                                    <input type="hidden" name="action" value="catgame_moderate_report" />
                                    <input type="hidden" name="report_id" value="<?php echo (int) ($row['id'] ?? 0); ?>" />
                                    <input type="hidden" name="resolution" value="false_report" />
                                    <button type="submit" class="button button-small">Reporte falso</button>
                                </form>
                            <?php else: ?>
                                <small><?php echo esc_html((string) ($row['resolution'] ?? '')); ?><?php if (!empty($row['severity'])): ?> (<?php echo esc_html((string) $row['severity']); ?>)<?php endif; ?></small>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:8px; padding:8px; border:1px solid #ccd0d4;">
                                    <?php wp_nonce_field('catgame_update_moderation_action'); ?>
                                    <input type="hidden" name="action" value="catgame_update_moderation_action" />
                                    <input type="hidden" name="submission_id" value="<?php echo (int) ($row['submission_id'] ?? 0); ?>" />
                                    <p style="margin:0 0 8px;"><strong>Editar acción</strong></p>
                                    <p style="margin:0 0 6px;">
                                        <select name="moderation_action" required>
                                            <?php $selected_action = (string) ($row['action_name'] ?? 'delete'); ?>
                                            <?php foreach (['restore', 'delete', 'strike', 'suspend_3d', 'delete_account'] as $opt): ?>
                                                <option value="<?php echo esc_attr($opt); ?>" <?php selected($selected_action, $opt); ?>><?php echo esc_html($opt); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </p>
                                    <p style="margin:0 0 6px;">
                                        <select name="severity" required>
                                            <?php $selected_severity = (string) ($row['action_severity'] ?? 'leve'); ?>
                                            <?php foreach (['leve', 'moderada', 'grave'] as $opt): ?>
                                                <option value="<?php echo esc_attr($opt); ?>" <?php selected($selected_severity, $opt); ?>><?php echo esc_html(ucfirst($opt)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </p>
                                    <p style="margin:0 0 6px;">
                                        <select name="reason" required>
                                            <?php $selected_reason = (string) ($row['action_reason'] ?? ($row['reason'] ?? 'other')); ?>
                                            <?php foreach (['not_pet', 'human', 'inappropriate', 'other'] as $opt): ?>
                                                <option value="<?php echo esc_attr($opt); ?>" <?php selected($selected_reason, $opt); ?>><?php echo esc_html($reason_label_map[$opt] ?? $opt); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </p>
                                    <p style="margin:0 0 6px;">
                                        <input type="text" name="detail" maxlength="250" value="<?php echo esc_attr((string) ($row['action_detail'] ?? '')); ?>" placeholder="Detalle (opcional)" style="width:100%;" />
                                    </p>
                                    <button type="submit" class="button button-small">Guardar edición</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
            (function () {
                var key = 'catgameModerationGuideOpen';
                var guide = document.getElementById('catgame-moderation-guide');
                if (!guide) {
                    return;
                }

                try {
                    if (window.localStorage.getItem(key) === '1') {
                        guide.open = true;
                    }
                } catch (e) {
                    // no-op
                }

                guide.addEventListener('toggle', function () {
                    try {
                        window.localStorage.setItem(key, guide.open ? '1' : '0');
                    } catch (e) {
                        // no-op
                    }
                });

                var copyBtn = document.getElementById('catgame-copy-grave-history');
                var copyStatus = document.getElementById('catgame-copy-grave-history-status');
                var copyText = document.getElementById('catgame-grave-history-json');
                if (copyBtn && copyText) {
                    copyBtn.addEventListener('click', function () {
                        var value = copyText.value || '[]';
                        var onDone = function (ok) {
                            if (!copyStatus) {
                                return;
                            }
                            copyStatus.textContent = ok ? 'Copiado' : 'No se pudo copiar';
                        };

                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(value).then(function () { onDone(true); }).catch(function () { onDone(false); });
                            return;
                        }

                        copyText.style.position = 'fixed';
                        copyText.style.left = '0';
                        copyText.style.top = '0';
                        copyText.select();
                        var ok = false;
                        try {
                            ok = document.execCommand('copy');
                        } catch (e) {
                            ok = false;
                        }
                        copyText.style.position = 'absolute';
                        copyText.style.left = '-9999px';
                        onDone(ok);
                    });
                }
            })();
        </script>
        <?php
    }

    public static function update_moderation_action(): void {
        if (!current_user_can('manage_options')) {
            wp_die('No autorizado');
        }

        check_admin_referer('catgame_update_moderation_action');

        $submission_id = (int) ($_POST['submission_id'] ?? 0);
        $action = sanitize_key((string) wp_unslash($_POST['moderation_action'] ?? ''));
        $severity = sanitize_key((string) wp_unslash($_POST['severity'] ?? ''));
        $reason = sanitize_key((string) wp_unslash($_POST['reason'] ?? ''));
        $detail = sanitize_textarea_field((string) wp_unslash($_POST['detail'] ?? ''));
        if (function_exists('mb_substr')) {
            $detail = mb_substr($detail, 0, 250);
        } else {
            $detail = substr($detail, 0, 250);
        }

        if ($submission_id <= 0 || !in_array($action, ['restore', 'delete', 'strike', 'suspend_3d', 'delete_account'], true) || !in_array($severity, ['leve', 'moderada', 'grave'], true) || !in_array($reason, ['not_pet', 'human', 'inappropriate', 'other'], true)) {
            wp_safe_redirect(admin_url('admin.php?page=catgame-moderation&status=resolved&mod_notice=invalid'));
            exit;
        }

        $submission = CatGame_Submissions::get_submission($submission_id);
        $user_id = (int) ($submission['user_id'] ?? 0);
        if (!$submission || $user_id <= 0) {
            wp_safe_redirect(admin_url('admin.php?page=catgame-moderation&status=resolved&mod_notice=invalid'));
            exit;
        }

        $current = self::get_current_moderation_action($submission_id);
        if (is_array($current) && (($current['action'] ?? '') === 'delete_account')) {
            wp_safe_redirect(admin_url('admin.php?page=catgame-moderation&status=resolved&mod_notice=blocked_delete_account'));
            exit;
        }

        if (self::is_same_moderation_action($current, $action, $severity, $reason, $detail)) {
            wp_safe_redirect(admin_url('admin.php?page=catgame-moderation&status=resolved&mod_notice=unchanged'));
            exit;
        }

        if ($current) {
            self::revert_moderation($submission_id, (string) ($current['action'] ?? ''));
        }

        $new_id = self::insert_moderation_action($submission_id, $user_id, $action, $severity, $reason, $detail, (int) ($current['id'] ?? 0));
        self::apply_moderation($submission_id, $user_id, $action, $severity);

        $old_label = $current ? (string) ($current['action'] ?? 'sin acción') : 'sin acción';
        CatGame_Reports::add_notification(
            $user_id,
            'moderation',
            'Acción de moderación actualizada',
            'Antes: ' . $old_label . ' / Ahora: ' . $action . '.',
            'moderation_update:' . $submission_id . ':' . $new_id
        );

        wp_safe_redirect(admin_url('admin.php?page=catgame-moderation&status=resolved&mod_notice=updated'));
        exit;
    }

    private static function get_current_moderation_action(int $submission_id): ?array {
        if ($submission_id <= 0) {
            return null;
        }

        global $wpdb;
        $table = CatGame_DB::table('moderation_actions');
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE submission_id = %d AND is_current = 1 ORDER BY id DESC LIMIT 1", $submission_id), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    private static function is_same_moderation_action(?array $current, string $action, string $severity, string $reason, string $detail): bool {
        if (!$current) {
            return false;
        }

        return (string) ($current['action'] ?? '') === $action
            && (string) ($current['severity'] ?? '') === $severity
            && (string) ($current['reason'] ?? '') === $reason
            && trim((string) ($current['detail'] ?? '')) === trim($detail);
    }

    private static function insert_moderation_action(int $submission_id, int $user_id, string $action, string $severity, string $reason, string $detail, int $prev_action_id = 0): int {
        global $wpdb;
        $table = CatGame_DB::table('moderation_actions');

        if ($prev_action_id > 0) {
            $wpdb->update(
                $table,
                ['is_current' => 0],
                ['id' => $prev_action_id],
                ['%d'],
                ['%d']
            );
        }

        $wpdb->insert(
            $table,
            [
                'submission_id' => $submission_id,
                'user_id' => $user_id,
                'action' => $action,
                'severity' => $severity,
                'reason' => $reason,
                'detail' => $detail,
                'decided_by' => get_current_user_id(),
                'decided_at' => current_time('mysql'),
                'prev_action_id' => $prev_action_id > 0 ? $prev_action_id : null,
                'is_current' => 1,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d']
        );

        return (int) $wpdb->insert_id;
    }

    private static function revert_moderation(int $submission_id, string $action): void {
        if ($submission_id <= 0) {
            return;
        }

        if (in_array($action, ['delete', 'delete_account'], true)) {
            CatGame_Submissions::unhide_submission($submission_id);
        }

        $submission = CatGame_Submissions::get_submission($submission_id);
        $user_id = (int) ($submission['user_id'] ?? 0);
        if ($user_id > 0 && in_array($action, ['suspend_3d', 'delete_account'], true)) {
            CatGame_Reports::set_upload_ban_until($user_id, 0);
        }
    }

    private static function apply_moderation(int $submission_id, int $user_id, string $action, string $severity): void {
        if ($submission_id <= 0 || $user_id <= 0) {
            return;
        }

        if ($action === 'restore') {
            CatGame_Submissions::unhide_submission($submission_id);
            return;
        }

        if ($action === 'delete') {
            CatGame_Submissions::hide_submission($submission_id, 'removed');
            return;
        }

        if ($action === 'strike') {
            CatGame_Reports::add_strike($user_id, 'author', $severity === 'moderada' ? 'moderado' : $severity, 'moderation_action_update', get_current_user_id());
            return;
        }

        if ($action === 'suspend_3d') {
            CatGame_Reports::set_upload_ban_until($user_id, time() + (3 * DAY_IN_SECONDS));
            return;
        }

        if ($action === 'delete_account') {
            CatGame_Submissions::hide_user_submissions($user_id, 'removed');
            CatGame_Reports::set_upload_ban_until($user_id, time() + YEAR_IN_SECONDS);
        }
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

    private static function build_rules_json_from_post(array $post_data): string {
        if (!empty($post_data['rules_disabled'])) {
            return wp_json_encode([
                'mode' => 'none',
                'items' => [],
            ]);
        }

        $posted_items = isset($post_data['rules_items']) && is_array($post_data['rules_items']) ? $post_data['rules_items'] : [];
        $items = [];

        foreach ($posted_items as $raw_item) {
            if (!is_array($raw_item)) {
                continue;
            }

            $title = sanitize_text_field(wp_unslash($raw_item['title'] ?? ''));
            $type = sanitize_key(wp_unslash($raw_item['type'] ?? 'tema'));
            $desc = sanitize_text_field(wp_unslash($raw_item['desc'] ?? ''));
            if ($title === '') {
                continue;
            }

            if (!in_array($type, ['tema', 'bonus', 'penalizacion'], true)) {
                $type = 'tema';
            }

            $value = null;
            if ($type !== 'tema') {
                $raw_value = is_scalar($raw_item['value'] ?? null) ? (string) $raw_item['value'] : '';
                $raw_value = str_replace(',', '.', $raw_value);
                if ($raw_value !== '' && is_numeric($raw_value)) {
                    $value = round((float) $raw_value, 2);
                }
            }

            $items[] = [
                'title' => $title,
                'type' => $type,
                'value' => $value,
                'desc' => $desc,
            ];
        }

        return wp_json_encode([
            'mode' => empty($items) ? 'none' : 'mixed',
            'items' => $items,
        ]);
    }

    private static function parse_rules_for_admin(?string $rules_json): array {
        $normalized = CatGame_Events::normalize_rules_payload($rules_json);
        $items = [];

        foreach ((array) ($normalized['items'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = [
                'title' => sanitize_text_field((string) ($item['title'] ?? '')),
                'type' => in_array(sanitize_key((string) ($item['type'] ?? 'tema')), ['tema', 'bonus', 'penalizacion'], true) ? sanitize_key((string) ($item['type'] ?? 'tema')) : 'tema',
                'value' => is_numeric($item['value'] ?? null) ? (string) round((float) $item['value'], 2) : '',
                'desc' => sanitize_text_field((string) ($item['desc'] ?? '')),
            ];
        }

        if (empty($items)) {
            $items[] = ['title' => '', 'type' => 'tema', 'value' => '', 'desc' => ''];
        }

        return [
            'uses_rules' => (($normalized['mode'] ?? 'none') !== 'none'),
            'items' => $items,
        ];
    }

    private static function render_rule_row($index, array $rule_item): void {
        $title = (string) ($rule_item['title'] ?? '');
        $type = sanitize_key((string) ($rule_item['type'] ?? 'tema'));
        if (!in_array($type, ['tema', 'bonus', 'penalizacion'], true)) {
            $type = 'tema';
        }
        $value = (string) ($rule_item['value'] ?? '');
        $desc = (string) ($rule_item['desc'] ?? '');
        ?>
        <div class="catgame-rule-row" data-rule-row="1">
            <div class="catgame-rule-col">
                <label>Título
                    <input type="text" data-rule-field="title" name="rules_items[<?php echo esc_attr((string) $index); ?>][title]" value="<?php echo esc_attr($title); ?>" maxlength="80" />
                </label>
            </div>
            <div class="catgame-rule-col">
                <label>Tipo
                    <select data-rule-field="type" name="rules_items[<?php echo esc_attr((string) $index); ?>][type]">
                        <option value="tema" <?php selected($type, 'tema'); ?>>Tema</option>
                        <option value="bonus" <?php selected($type, 'bonus'); ?>>Bonus</option>
                        <option value="penalizacion" <?php selected($type, 'penalizacion'); ?>>Penalización</option>
                    </select>
                </label>
            </div>
            <div class="catgame-rule-col" <?php echo $type === 'tema' ? 'hidden' : ''; ?>>
                <label>Valor
                    <input type="number" step="0.1" data-rule-field="value" name="rules_items[<?php echo esc_attr((string) $index); ?>][value]" value="<?php echo esc_attr($value); ?>" <?php echo $type === 'tema' ? 'disabled' : ''; ?> />
                </label>
            </div>
            <div class="catgame-rule-col">
                <label>Descripción (opcional)
                    <input type="text" data-rule-field="desc" name="rules_items[<?php echo esc_attr((string) $index); ?>][desc]" value="<?php echo esc_attr($desc); ?>" maxlength="120" />
                </label>
            </div>
            <div class="catgame-rule-col catgame-rule-col--actions">
                <button type="button" class="button button-small" data-rules-remove="1">Eliminar</button>
            </div>
        </div>
        <?php
    }

    private static function render_event_preview(array $event, int $now_ts): void {
        $start_ts = strtotime((string) ($event['starts_at'] ?? ''));
        $end_ts = strtotime((string) ($event['ends_at'] ?? ''));
        $status_label = self::event_status_label($start_ts, $end_ts, (int) ($event['is_active'] ?? 0), $now_ts);

        $popup_view = CatGame_Events::build_rules_popup_view($event);
        $event_type = sanitize_key((string) ($popup_view['event_type'] ?? 'competitive'));
        $mode = sanitize_key((string) ($popup_view['mode'] ?? 'none'));
        $items = isset($popup_view['items']) && is_array($popup_view['items']) ? $popup_view['items'] : [];
        $general = isset($popup_view['general_summary']) && is_array($popup_view['general_summary']) ? $popup_view['general_summary'] : [];
        ?>
        <section class="catgame-event-preview">
            <h3>Previsualización (1:1 juego)</h3>
            <p><strong>Nombre:</strong> <?php echo esc_html((string) ($popup_view['name'] ?? 'Evento vigente')); ?></p>
            <p><strong>Tipo:</strong> <span class="catgame-pill"><?php echo esc_html((($popup_view['event_type'] ?? 'competitive') === 'thematic') ? 'Temático' : 'Competitivo'); ?></span></p>
            <p><strong>Estado:</strong> <?php echo esc_html($status_label); ?></p>
            <p><strong>Vigencia:</strong> <?php echo esc_html((string) ($popup_view['date_range'] ?? '')); ?></p>

            <?php if ($event_type === 'thematic'): ?>
                <p><strong>Este evento es temático.</strong> Las publicaciones relacionadas no compiten en ranking.</p>
                <p><strong>Reglas generales (resumen):</strong></p>
                <ul class="catgame-preview-rules">
                    <?php foreach ($general as $line): ?>
                        <li>• <?php echo esc_html((string) $line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif ($mode === 'none'): ?>
                <p><strong>Reglas generales (resumen):</strong></p>
                <ul class="catgame-preview-rules">
                    <?php foreach ($general as $line): ?>
                        <li>• <?php echo esc_html((string) $line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p><strong>Reglas del evento:</strong></p>
                <ul class="catgame-preview-rules">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $type = sanitize_key((string) ($item['type'] ?? 'tema'));
                        $title = (string) ($item['title'] ?? 'Regla');
                        $value = is_numeric($item['value'] ?? null) ? (float) $item['value'] : null;
                        $desc = trim((string) ($item['desc'] ?? ''));
                        ?>
                        <li>
                            <span><?php echo esc_html($title); ?><?php if ($desc !== ''): ?> — <?php echo esc_html($desc); ?><?php endif; ?></span>
                            <?php if ($type === 'bonus' && $value !== null): ?><strong>+<?php echo esc_html(number_format_i18n($value, 1)); ?></strong><?php endif; ?>
                            <?php if ($type === 'penalizacion' && $value !== null): ?><strong>-<?php echo esc_html(number_format_i18n($value, 1)); ?></strong><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p><strong>Reglas generales (resumen):</strong></p>
                <ul class="catgame-preview-rules">
                    <?php foreach ($general as $line): ?>
                        <li>• <?php echo esc_html((string) $line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
        <?php
    }

    private static function legacy_rule_labels(): array {
        return [
            'black_cat' => 'Gato negro',
            'night_photo' => 'Foto nocturna',
            'funny_pose' => 'Pose divertida',
            'weird_place' => 'Lugar raro',
        ];
    }

    private static function humanize_rule_key(string $key): string {
        return ucwords(str_replace(['_', '-'], ' ', $key));
    }

    private static function rule_fields(): array {
        return [
            'black_cat' => [
                'label' => 'Gato negro',
                'help' => 'Bono para fotos con gatos negros.',
                'default' => 1.0,
            ],
            'night_photo' => [
                'label' => 'Foto nocturna',
                'help' => 'Bono para capturas de noche.',
                'default' => 0.5,
            ],
            'funny_pose' => [
                'label' => 'Pose divertida',
                'help' => 'Bono para poses graciosas.',
                'default' => 0.5,
            ],
            'weird_place' => [
                'label' => 'Lugar raro',
                'help' => 'Bono para ubicaciones inesperadas.',
                'default' => 0.5,
            ],
        ];
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
