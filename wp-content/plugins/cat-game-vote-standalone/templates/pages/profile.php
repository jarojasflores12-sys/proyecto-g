<?php
$register_error = !empty($data['register_error']) ? CatGame_Auth::registration_error_message((string) $data['register_error']) : '';
$login_error = !empty($data['login_error']) ? CatGame_Auth::login_error_message((string) $data['login_error']) : '';
$lost_error = !empty($data['lost_error']) ? CatGame_Auth::lost_password_message((string) $data['lost_error']) : '';
$reset_error = !empty($data['reset_error']) ? CatGame_Auth::reset_password_message((string) $data['reset_error']) : '';
$registered = !empty($data['registered']);
$tag_deleted = !empty($data['tag_deleted']);
$profile_saved = !empty($data['profile_saved']);
$complete_profile = !empty($data['complete_profile']);
$profile_error = sanitize_key((string) ($data['profile_error'] ?? ''));
$lost_sent = !empty($data['lost_sent']);
$password_reset = !empty($data['password_reset']);
$auth_view = (string) ($data['auth_view'] ?? 'login');
$login_identifier = (string) ($data['login_identifier'] ?? '');
$reg_username = (string) ($data['reg_username'] ?? '');
$reg_email = (string) ($data['reg_email'] ?? '');
$lost_identifier = (string) ($data['lost_identifier'] ?? '');
$rp_login = (string) ($data['rp_login'] ?? '');
$rp_key = (string) ($data['rp_key'] ?? '');
$has_valid_reset_key = !empty($data['has_valid_reset_key']);

if (!empty($data['requires_login'])): ?>
<section class="cg-auth-shell">
    <h2>Acceso</h2>
    <p>Inicia sesión o crea tu cuenta para participar, subir fotos y votar.</p>

    <?php if ($registered): ?>
        <p class="cg-alert cg-alert-success">¡Cuenta creada! Ya puedes iniciar sesión.</p>
    <?php endif; ?>
    <?php if ($password_reset): ?>
        <p class="cg-alert cg-alert-success">Contraseña actualizada. Ya puedes iniciar sesión.</p>
    <?php endif; ?>

    <div class="cg-auth-tabs" role="tablist" aria-label="Acciones de acceso">
        <button type="button" class="cg-auth-tab <?php echo $auth_view === 'login' ? 'is-active' : ''; ?>" data-auth-tab="login" role="tab" aria-selected="<?php echo $auth_view === 'login' ? 'true' : 'false'; ?>">Iniciar sesión</button>
        <button type="button" class="cg-auth-tab <?php echo $auth_view === 'register' ? 'is-active' : ''; ?>" data-auth-tab="register" role="tab" aria-selected="<?php echo $auth_view === 'register' ? 'true' : 'false'; ?>">Crear cuenta</button>
        <button type="button" class="cg-auth-tab <?php echo $auth_view === 'forgot' ? 'is-active' : ''; ?>" data-auth-tab="forgot" role="tab" aria-selected="<?php echo $auth_view === 'forgot' ? 'true' : 'false'; ?>">Olvidé mi contraseña</button>
    </div>

    <section class="cg-auth-panel <?php echo $auth_view === 'login' ? 'is-active' : ''; ?>" data-auth-panel="login">
        <?php if ($login_error): ?><p class="cg-alert cg-alert-error"><?php echo esc_html($login_error); ?></p><?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-form cg-auth-form" autocomplete="on">
            <?php wp_nonce_field('catgame_login'); ?>
            <input type="hidden" name="action" value="catgame_login">
            <label>Usuario o correo
                <input type="text" name="login_identifier" required value="<?php echo esc_attr($login_identifier); ?>" autocomplete="username">
            </label>
            <label>Contraseña
                <div class="cg-password-wrap">
                    <input type="password" name="password" required minlength="8" autocomplete="current-password">
                    <button type="button" class="cg-password-toggle" data-target="password" aria-label="Mostrar u ocultar contraseña">👁</button>
                </div>
            </label>
            <button type="submit">Iniciar sesión</button>
        </form>
    </section>

    <section class="cg-auth-panel <?php echo $auth_view === 'register' ? 'is-active' : ''; ?>" data-auth-panel="register">
        <?php if ($register_error): ?><p class="cg-alert cg-alert-error"><?php echo esc_html($register_error); ?></p><?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-form cg-auth-form" autocomplete="on">
            <?php wp_nonce_field('catgame_register'); ?>
            <input type="hidden" name="action" value="catgame_register">
            <label>Correo
                <input type="email" name="email" required value="<?php echo esc_attr($reg_email); ?>" autocomplete="email">
            </label>
            <label>Nombre de usuario
                <input type="text" name="username" required maxlength="60" value="<?php echo esc_attr($reg_username); ?>" autocomplete="username">
            </label>
            <label>Contraseña
                <div class="cg-password-wrap">
                    <input type="password" name="password" required minlength="8" autocomplete="new-password">
                    <button type="button" class="cg-password-toggle" data-target="password" aria-label="Mostrar u ocultar contraseña">👁</button>
                </div>
            </label>
            <label>Repetir contraseña
                <div class="cg-password-wrap">
                    <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
                    <button type="button" class="cg-password-toggle" data-target="password_confirm" aria-label="Mostrar u ocultar contraseña">👁</button>
                </div>
            </label>
            <button type="submit">Crear cuenta</button>
        </form>
    </section>

    <section class="cg-auth-panel <?php echo $auth_view === 'forgot' ? 'is-active' : ''; ?>" data-auth-panel="forgot">
        <?php if ($lost_error): ?><p class="cg-alert cg-alert-error"><?php echo esc_html($lost_error); ?></p><?php endif; ?>
        <?php if ($lost_sent): ?><p class="cg-alert cg-alert-success">Si existe una cuenta asociada, te enviamos un correo con instrucciones para recuperar tu contraseña.</p><?php endif; ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-form cg-auth-form" autocomplete="on">
            <?php wp_nonce_field('catgame_lost_password'); ?>
            <input type="hidden" name="action" value="catgame_lost_password">
            <label>Usuario o correo
                <input type="text" name="lost_identifier" required value="<?php echo esc_attr($lost_identifier); ?>" autocomplete="username email">
            </label>
            <button type="submit">Enviar enlace de recuperación</button>
        </form>
    </section>

    <?php if ($auth_view === 'reset'): ?>
        <section class="cg-auth-panel is-active" data-auth-panel="reset">
            <h3>Restablecer contraseña</h3>
            <?php if (!$has_valid_reset_key): ?>
                <p class="cg-alert cg-alert-error">El enlace de recuperación no es válido o ya expiró.</p>
            <?php else: ?>
                <?php if ($reset_error): ?><p class="cg-alert cg-alert-error"><?php echo esc_html($reset_error); ?></p><?php endif; ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-form cg-auth-form" autocomplete="off">
                    <?php wp_nonce_field('catgame_reset_password'); ?>
                    <input type="hidden" name="action" value="catgame_reset_password">
                    <input type="hidden" name="rp_login" value="<?php echo esc_attr($rp_login); ?>">
                    <input type="hidden" name="rp_key" value="<?php echo esc_attr($rp_key); ?>">
                    <label>Nueva contraseña
                        <div class="cg-password-wrap">
                            <input type="password" name="password" required minlength="8" autocomplete="new-password">
                            <button type="button" class="cg-password-toggle" data-target="password" aria-label="Mostrar u ocultar contraseña">👁</button>
                        </div>
                    </label>
                    <label>Repetir nueva contraseña
                        <div class="cg-password-wrap">
                            <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
                            <button type="button" class="cg-password-toggle" data-target="password_confirm" aria-label="Mostrar u ocultar contraseña">👁</button>
                        </div>
                    </label>
                    <button type="submit">Restablecer contraseña</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>


</section>
<?php return; endif;

$stats = $data['stats'] ?? ['total_submissions' => 0, 'total_reactions' => 0, 'most_voted' => null, 'best_ranked' => null];
$items = $data['items'] ?? [];
$notifications = $data['notifications'] ?? [];
$custom_tags = $data['custom_tags'] ?? [];
$top_position_for_user = $data['top_position_for_user'] ?? null;
$scope = $data['scope'] ?? 'event';
$prefs = $data['profile_prefs'] ?? [];

$current_user = wp_get_current_user();
$username = $current_user && !empty($current_user->user_login) ? (string) $current_user->user_login : 'usuario';
$initial = strtoupper(substr($username, 0, 1));
$avatar_color = sanitize_key((string) ($prefs['avatar_color'] ?? 'rose'));
$allowed_colors = ['rose' => '#F7B7C3', 'mint' => '#B5EAD7', 'lavender' => '#C7CEEA', 'yellow' => '#FFF5BA', 'sky' => '#BFE3FF'];
if (!isset($allowed_colors[$avatar_color])) {
    $avatar_color = 'rose';
}
$most_voted = is_array($stats['most_voted'] ?? null) ? $stats['most_voted'] : null;
$best_ranked = is_array($stats['best_ranked'] ?? null) ? $stats['best_ranked'] : null;
$best_photo = is_array($data['best_photo'] ?? null) ? $data['best_photo'] : null;

$profile_link = home_url('/catgame/profile');
$best_photo_link = $best_photo ? home_url('/catgame/feed') : '';
$default_city = trim((string) ($prefs['default_city'] ?? ''));
$default_country = trim((string) ($prefs['default_country'] ?? ''));
$location_missing = $default_city === '' || $default_country === '';

$account_status = (array) ($data['account_status'] ?? []);
$strikes_status = (array) ($account_status['strikes'] ?? []);
$bans_status = (array) ($account_status['bans'] ?? []);
$author_active = (int) ($strikes_status['author_active'] ?? 0);
$reporter_active = (int) ($strikes_status['reporter_active'] ?? 0);
$strikes_threshold = max(1, (int) ($strikes_status['threshold'] ?? 3));
$strikes_resets = (string) ($strikes_status['resets'] ?? '1 año');
$upload_banned = !empty($bans_status['upload_banned']);
$upload_banned_until_iso = (string) ($bans_status['upload_banned_until'] ?? '');
$upload_banned_until_label = '';
if ($upload_banned_until_iso !== '') {
    $upload_banned_until_ts = strtotime($upload_banned_until_iso);
    if ($upload_banned_until_ts) {
        $upload_banned_until_label = wp_date('d/m/Y H:i', $upload_banned_until_ts);
    }
}
?>
<section>
    <div class="cg-profile-topbar">
        <h2>Mi perfil</h2>
        <div class="cg-profile-topbar-actions">
            <button type="button" class="secondary cg-notif-bell" id="catgame-notif-bell" aria-label="Notificaciones" aria-haspopup="dialog" aria-controls="catgame-notifications-modal">
                🔔
                <span class="cg-notif-badge" id="catgame-notif-badge" hidden>0</span>
            </button>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-logout-form cg-logout-form-top">
                <?php wp_nonce_field('catgame_logout'); ?>
                <input type="hidden" name="action" value="catgame_logout">
                <button type="submit" class="secondary cg-logout-btn" aria-label="Cerrar sesión">⎋ <span>Cerrar sesión</span></button>
            </form>
        </div>
    </div>

    <?php if ($registered): ?>
        <p class="cg-alert cg-alert-success">¡Cuenta creada! Ya estás dentro.</p>
    <?php endif; ?>
    <?php if ($complete_profile): ?>
        <p class="cg-alert cg-alert-error">Completa tu ciudad y país para continuar.</p>
    <?php endif; ?>
    <?php if ($location_missing): ?>
        <p class="cg-alert cg-alert-error">Completa tu ciudad y país para poder subir fotos.</p>
    <?php endif; ?>
    <?php if ($tag_deleted): ?>
        <p class="cg-alert cg-alert-success">Etiqueta eliminada del catálogo personal.</p>
    <?php endif; ?>
    <?php if ($profile_saved): ?>
        <p class="cg-alert cg-alert-success">Ubicación guardada.</p>
    <?php endif; ?>
    <?php if ($profile_error === 'missing_location'): ?>
        <p class="cg-alert cg-alert-error">Debes completar ciudad y país para guardar.</p>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-form" id="catgame-profile-form">
        <?php wp_nonce_field('catgame_profile_update'); ?>
        <input type="hidden" name="action" value="catgame_profile_update">

        <div class="cg-profile-user">
            <div class="cg-avatar cg-avatar-<?php echo esc_attr($avatar_color); ?>" aria-hidden="true"><?php echo esc_html($initial); ?></div>
            <div class="cg-profile-user-meta">
                <p>Usuario: @<?php echo esc_html($username); ?></p>
            </div>
            <div class="cg-profile-user-actions">
                <button type="button" class="secondary js-avatar-color-toggle" aria-expanded="false" aria-controls="cg-avatar-colors">Cambiar color</button>
                <button type="submit" class="js-profile-save is-hidden">Guardar cambios</button>
            </div>
        </div>

        <fieldset class="cg-avatar-colors" id="cg-avatar-colors" hidden>
            <legend>Color de avatar</legend>
            <?php foreach ($allowed_colors as $slug => $hex): ?>
                <label class="cg-color-swatch <?php echo $avatar_color === $slug ? 'is-selected' : ''; ?>" style="--cg-swatch: <?php echo esc_attr($hex); ?>;">
                    <input type="radio" name="avatar_color" value="<?php echo esc_attr($slug); ?>" <?php checked($avatar_color, $slug); ?>>
                    <span aria-hidden="true"></span>
                </label>
            <?php endforeach; ?>
        </fieldset>


        <div class="cg-profile-location-fields">
            <label>Ciudad
                <input type="text" name="default_city" value="<?php echo esc_attr($default_city); ?>" placeholder="Ej: Talca" required>
            </label>
            <label>País
                <input type="text" name="default_country" value="<?php echo esc_attr($default_country); ?>" placeholder="Ej: Chile" required>
            </label>
        </div>
    </form>


    <article class="cg-card cg-account-status-card">
        <h3>Estado de tu cuenta</h3>
        <p><strong>Strikes:</strong> <?php echo (int) $author_active; ?>/<?php echo (int) $strikes_threshold; ?></p>
        <p><strong>Reportes falsos:</strong> <?php echo (int) $reporter_active; ?>/<?php echo (int) $strikes_threshold; ?></p>
        <?php if ($upload_banned): ?>
            <p><strong>Subida restringida hasta:</strong> <?php echo esc_html($upload_banned_until_label !== '' ? $upload_banned_until_label : 'fecha por confirmar'); ?></p>
            <small>Puedes seguir reaccionando.</small>
        <?php else: ?>
            <p>Puedes subir y reaccionar con normalidad.</p>
        <?php endif; ?>
        <small>Los strikes expiran en <?php echo esc_html($strikes_resets); ?>.</small>
    </article>

    <?php if (!empty($top_position_for_user)): ?>
        <p class="cg-alert cg-alert-success">🏆 Estás en el Top 3 del evento: #<?php echo (int) $top_position_for_user; ?>. <a href="<?php echo esc_url(home_url('/catgame/leaderboard')); ?>">Ver ranking</a></p>
    <?php endif; ?>

    <form method="get" action="<?php echo esc_url(home_url('/catgame/profile')); ?>" class="cg-form-inline">
        <label>Alcance
            <select name="scope">
                <option value="event" <?php selected($scope, 'event'); ?>>Evento activo</option>
                <option value="global" <?php selected($scope, 'global'); ?>>Global</option>
            </select>
        </label>
        <button type="submit">Aplicar</button>
    </form>

    <h3>Resumen</h3>
    <div class="cg-profile-stats-grid cg-profile-summary-grid">
        <article class="cg-card">
            <strong>Publicaciones totales</strong>
            <p><?php echo (int) ($stats['total_submissions'] ?? 0); ?></p>
        </article>
        <article class="cg-card">
            <strong>Publicación con más reacciones</strong>
            <?php if ($most_voted): ?>
                <?php $mv_title = CatGame_Submissions::title_label($most_voted); ?>
                <p><?php echo esc_html($mv_title); ?></p>
            <?php else: ?>
                <p>Aún no tienes publicaciones.</p>
            <?php endif; ?>
        </article>
        <article class="cg-card">
            <strong>Mejor posicionada por reacciones</strong>
            <?php if ($best_ranked): ?>
                <?php $br_title = CatGame_Submissions::title_label($best_ranked); ?>
                <p><?php echo esc_html($br_title); ?></p>
            <?php else: ?>
                <p>Sin datos aún.</p>
            <?php endif; ?>
        </article>
        <article class="cg-card">
            <strong>Reacciones totales recibidas</strong>
            <p><?php echo (int) ($stats['total_reactions'] ?? 0); ?></p>
        </article>
        <article class="cg-card">
            <strong>Publicación destacada</strong>
            <?php if ($best_photo): ?>
                <?php $bf_title = CatGame_Submissions::title_label($best_photo); ?>
                <p><?php echo esc_html($bf_title); ?></p>
            <?php else: ?>
                <p>Sin datos aún.</p>
            <?php endif; ?>
        </article>
    </div>

    <h3>Tu publicación destacada</h3>
    <?php if ($best_photo): ?>
        <?php
        $best_title = CatGame_Submissions::title_label($best_photo);
        ?>
        <article class="cg-card cg-profile-best-photo">
            <?php echo wp_get_attachment_image((int) $best_photo['attachment_id'], 'medium_large', false, ['loading' => 'lazy', 'class' => 'cg-profile-thumb']); ?>
            <span class="cg-badge">#<?php echo (int) ($best_photo['id'] ?? 0); ?></span><strong><?php echo esc_html($best_title); ?></strong>
            <?php CatGame_Reactions::render_widget((int) ($best_photo['id'] ?? 0), is_user_logged_in(), ['reaction_counts' => (array) ($best_photo['reaction_counts'] ?? []), 'my_reaction' => ($best_photo['my_reaction'] ?? null)]); ?>
        </article>
    <?php else: ?>
        <p>Aún no tienes una publicación con reacciones.</p>
    <?php endif; ?>

    <div class="cg-profile-share">
        <button type="button" class="secondary js-share-profile" data-url="<?php echo esc_url($profile_link); ?>">Compartir mi perfil</button>
        <button type="button" class="secondary js-share-best" data-url="<?php echo esc_url($best_photo_link ?: $profile_link); ?>">Compartir mi publicación destacada</button>
    </div>

    <section class="cg-card">
        <h3>Comunidad</h3>
        <p>¿Ideas para eventos futuros? Escríbenos en Instagram.</p>
        <a class="cg-cta" href="<?php echo esc_url(CATGAME_INSTAGRAM_URL); ?>" target="_blank" rel="noopener noreferrer">Ir a Instagram</a>
    </section>

    <h3>Mis etiquetas personalizadas</h3>
    <?php if (empty($custom_tags)): ?>
        <p>No tienes etiquetas personalizadas.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($custom_tags as $tag => $label): ?>
                <li class="cg-tag-item">
                    <strong><?php echo esc_html(CatGame_Submissions::label_for_tag($tag, get_current_user_id())); ?></strong>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-tag-delete-form" data-cg-confirm="1" data-cg-confirm-title="Eliminar etiqueta" data-cg-confirm-text="Esta acción no se puede deshacer. ¿Eliminar esta etiqueta?">
                        <?php wp_nonce_field('catgame_delete_custom_tag'); ?>
                        <input type="hidden" name="action" value="catgame_delete_custom_tag">
                        <input type="hidden" name="tag" value="<?php echo esc_attr($tag); ?>">
                        <button type="submit" class="cg-tag-delete cg-tag-remove" aria-label="Eliminar etiqueta <?php echo esc_attr(CatGame_Submissions::label_for_tag($tag, get_current_user_id())); ?>" title="Eliminar etiqueta">✕</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3>Mis publicaciones</h3>
    <div class="cg-grid">
        <?php if (!$items): ?>
            <p>Aún no tienes publicaciones.</p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <?php
            ?>
            <article class="cg-card cg-profile-item">
                <?php
                $item_title = CatGame_Submissions::title_label($item);
                $item_author = get_userdata((int) ($item['user_id'] ?? 0));
                $item_author_name = $item_author ? (string) $item_author->user_login : 'usuario';
                $item_reactions = (int) ($item['total_reactions'] ?? 0);
                ?>
                <?php echo wp_get_attachment_image((int) $item['attachment_id'], 'medium_large', false, ['loading' => 'lazy', 'class' => 'cg-profile-thumb']); ?>
                <p><span class="cg-badge">#<?php echo (int) $item['id']; ?></span> <?php echo esc_html($item_title); ?></p>
                <small class="cg-author">por @<?php echo esc_html($item_author_name); ?></small>
                <p><?php echo $item_reactions > 0 ? esc_html($item_reactions . ' reacciones') : 'Sin reacciones'; ?></p>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-inline-delete-form" data-cg-confirm="1" data-cg-confirm-title="Eliminar publicación" data-cg-confirm-text="Esta acción no se puede deshacer. ¿Eliminar esta publicación?">
                    <?php wp_nonce_field('catgame_delete_submission'); ?>
                    <input type="hidden" name="action" value="catgame_delete_submission">
                    <input type="hidden" name="submission_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                    <button type="submit" class="cg-tag-delete">Eliminar mi publicación</button>
                </form>
                <?php CatGame_Reactions::render_widget((int) ($item['id'] ?? 0), is_user_logged_in(), (array) ($item['reaction_counts'] ?? []) ? ['reaction_counts' => (array) ($item['reaction_counts'] ?? []), 'my_reaction' => ($item['my_reaction'] ?? null)] : []); ?>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="cg-modal" id="catgame-notifications-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="catgame-notifications-title">
        <div class="cg-modal__backdrop" data-notifications-close="1"></div>
        <div class="cg-modal__content" role="document">
            <button type="button" class="cg-modal__close" data-notifications-close="1" aria-label="Cerrar notificaciones">✕</button>
            <h2 id="catgame-notifications-title">Notificaciones</h2>
            <ul class="cg-notifications-list" id="catgame-notifications-list">
                <li class="cg-notifications-empty">No tienes notificaciones</li>
            </ul>
        </div>
    </div>

</section>
