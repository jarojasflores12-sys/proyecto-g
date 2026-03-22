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
$brand = CatGame_Admin::get_frontend_branding();
$brand_name = sanitize_text_field((string) ($brand['name'] ?? 'PetUnity'));
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

$profile_link = home_url('/catgame/user/' . rawurlencode(sanitize_user($username, true)));
$best_photo_link = $best_photo ? home_url('/catgame/submission/' . (int) ($best_photo['id'] ?? 0)) : '';
$profile_base_url = home_url('/catgame/profile');
$profile_view = sanitize_key((string) ($_GET['profile_view'] ?? 'main'));
$allowed_profile_views = ['main', 'location', 'notifications', 'rules', 'account', 'stats', 'tags', 'community', 'feedback'];
if (!in_array($profile_view, $allowed_profile_views, true)) {
    $profile_view = 'main';
}
$is_profile_main_view = $profile_view === 'main';
$profile_view_labels = [
    'location' => 'Ubicación',
    'notifications' => 'Notificaciones',
    'rules' => 'Normas',
    'account' => 'Estado de la cuenta',
    'stats' => 'Estadísticas',
    'tags' => 'Mis etiquetas',
    'community' => 'Comunidad',
    'feedback' => 'Ayúdanos a mejorar',
];
$profile_view_descriptions = [
    'location' => 'Actualiza la ciudad y el país que usas por defecto para publicar.',
    'notifications' => 'Consulta tus avisos sin salir de esta vista.',
    'rules' => 'Revisa el estado de aceptación y consulta las normas del juego.',
    'account' => 'Mira sanciones, strikes y restricciones activas de tu cuenta.',
    'stats' => 'Explora el resumen de rendimiento de tus publicaciones.',
    'tags' => 'Administra las etiquetas personalizadas que has creado.',
    'community' => 'Encuentra el canal oficial para compartir ideas con PetUnity.',
    'feedback' => 'Envíanos sugerencias o reporta problemas desde tu perfil.',
];
$default_city = CatGame_Submissions::visual_label(trim((string) ($prefs['default_city'] ?? '')));
$default_country = CatGame_Submissions::visual_label(trim((string) ($prefs['default_country'] ?? '')));
$terms_accepted = !empty($prefs['terms_accepted']);
$terms_accepted_at = trim((string) ($prefs['terms_accepted_at'] ?? ''));
$location_missing = $default_city === '' || $default_country === '';
$profile_incomplete = $location_missing || !$terms_accepted;

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
<section class="cg-profile-shell">
    <div class="cg-profile-topbar">
        <h2>Mi perfil</h2>
        <div class="cg-profile-topbar-actions">
            <details class="cg-profile-menu">
                <summary class="secondary cg-profile-menu__toggle" aria-label="Opciones de perfil">
                    <span class="cg-profile-menu__icon" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span class="cg-notif-badge" id="catgame-notif-badge" hidden>0</span>
                </summary>
                <div class="cg-profile-menu__panel">
                    <a class="cg-profile-menu__item" href="<?php echo esc_url(add_query_arg('profile_view', 'location', $profile_base_url)); ?>">Ubicación</a>
                    <a class="cg-profile-menu__item" href="<?php echo esc_url(add_query_arg('profile_view', 'notifications', $profile_base_url)); ?>">Notificaciones</a>
                    <a class="cg-profile-menu__item" href="<?php echo esc_url(add_query_arg('profile_view', 'rules', $profile_base_url)); ?>">Normas</a>
                    <a class="cg-profile-menu__item" href="<?php echo esc_url(add_query_arg('profile_view', 'account', $profile_base_url)); ?>">Estado de la cuenta</a>
                    <a class="cg-profile-menu__item" href="<?php echo esc_url(add_query_arg('profile_view', 'stats', $profile_base_url)); ?>">Estadísticas</a>
                    <a class="cg-profile-menu__item" href="<?php echo esc_url(add_query_arg('profile_view', 'tags', $profile_base_url)); ?>">Mis etiquetas</a>
                    <a class="cg-profile-menu__item" href="<?php echo esc_url(add_query_arg('profile_view', 'community', $profile_base_url)); ?>">Comunidad</a>
                    <a class="cg-profile-menu__item" href="<?php echo esc_url(add_query_arg('profile_view', 'feedback', $profile_base_url)); ?>">Ayúdanos a mejorar</a>
                    <button type="button" class="cg-profile-menu__item cg-profile-menu__item--logout" data-profile-logout-open="1"><span aria-hidden="true">⏻</span><span>Cerrar sesión</span></button>
                </div>
            </details>
        </div>
    </div>

    <?php if ($registered): ?>
        <p class="cg-alert cg-alert-success">¡Cuenta creada! Ya estás dentro.</p>
    <?php endif; ?>
    <?php if ($complete_profile): ?>
        <p class="cg-alert cg-alert-error">Completa ciudad, país y aceptación de normas para continuar.</p>
    <?php endif; ?>
    <?php if ($profile_incomplete): ?>
        <p class="cg-alert cg-alert-error">Debes completar ciudad, país y aceptar las normas para poder subir fotos.</p>
    <?php endif; ?>
    <?php if ($tag_deleted): ?>
        <p class="cg-alert cg-alert-success">Etiqueta eliminada del catálogo personal.</p>
    <?php endif; ?>
    <?php if ($profile_saved): ?>
        <p class="cg-alert cg-alert-success">Perfil guardado.</p>
    <?php endif; ?>
    <?php if ($profile_error === 'missing_location'): ?>
        <p class="cg-alert cg-alert-error">Debes completar ciudad y país para guardar.</p>
    <?php elseif ($profile_error === 'missing_terms'): ?>
        <p class="cg-alert cg-alert-error">Debes aceptar las normas y sanciones para guardar.</p>
    <?php endif; ?>
    <?php $review_appeal_status = sanitize_key(wp_unslash($_GET['review_appeal'] ?? '')); ?>
    <?php $feedback_status = sanitize_key(wp_unslash($_GET['feedback_sent'] ?? '')); ?>
    <?php $feedback_error = sanitize_key(wp_unslash($_GET['feedback_error'] ?? '')); ?>
    <?php if ($review_appeal_status === 'sent'): ?>
        <p class="cg-alert cg-alert-success">Apelación de revisión enviada.</p>
    <?php elseif ($review_appeal_status === 'expired'): ?>
        <p class="cg-alert cg-alert-error">La ventana de apelación de revisión (24h) expiró.</p>
    <?php elseif ($review_appeal_status === 'invalid'): ?>
        <p class="cg-alert cg-alert-error">No se pudo procesar la apelación solicitada.</p>
    <?php endif; ?>

    <?php if ($feedback_status === '1'): ?>
        <p class="cg-alert cg-alert-success">Gracias. Tu mensaje fue enviado correctamente.</p>
    <?php endif; ?>
    <?php if ($feedback_error === 'empty'): ?>
        <p class="cg-alert cg-alert-error">Escribe un mensaje antes de enviar tu comentario.</p>
    <?php elseif ($feedback_error === 'save_failed'): ?>
        <p class="cg-alert cg-alert-error">No se pudo guardar tu mensaje. Intenta nuevamente.</p>
    <?php endif; ?>

    <?php if (!$is_profile_main_view): ?>
        <div class="cg-card cg-profile-subview-head">
            <a class="cg-profile-subview-head__back" href="<?php echo esc_url($profile_base_url); ?>">← Volver a Mi perfil</a>
            <p class="cg-profile-subview-head__eyebrow">Panel de perfil</p>
            <h3><?php echo esc_html($profile_view_labels[$profile_view] ?? 'Mi perfil'); ?></h3>
            <p class="cg-profile-subview-head__intro"><?php echo esc_html($profile_view_descriptions[$profile_view] ?? 'Gestiona esta sección de tu perfil.'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-form" id="catgame-profile-form">
        <?php wp_nonce_field('catgame_profile_update'); ?>
        <input type="hidden" name="action" value="catgame_profile_update">

        <div class="cg-profile-user">
            <div class="cg-avatar cg-avatar-<?php echo esc_attr($avatar_color); ?>" aria-hidden="true"><?php echo esc_html($initial); ?></div>
            <div class="cg-profile-user-meta">
                <p class="cg-profile-user-eyebrow">Mi perfil</p>
                <h2 class="cg-profile-user-name">@<?php echo esc_html($username); ?></h2>
            </div>
            <div class="cg-profile-user-actions">
                <button type="button" class="secondary js-avatar-color-toggle" aria-expanded="false" aria-controls="cg-avatar-colors">Cambiar color</button>
                <button type="button" class="secondary js-share-link" data-url="<?php echo esc_url($profile_link); ?>" data-share-title="<?php echo esc_attr($brand_name); ?>" data-share-text="Mira el perfil de esta mascota en <?php echo esc_attr($brand_name); ?>">Compartir perfil</button>
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


        <?php if ($profile_view === 'location'): ?>
            <div class="cg-card cg-profile-subview-card cg-profile-subview-section" id="cg-profile-location">
                <div class="cg-profile-subview-section__head">
                    <h4>Tu ubicación base</h4>
                    <p>Estos datos se usan para completar más rápido tus próximas publicaciones.</p>
                </div>
                <div class="cg-profile-location-fields">
                <label>Ciudad
                    <input type="text" name="default_city" value="<?php echo esc_attr($default_city); ?>" placeholder="Ej: Talca" required>
                </label>
                <label>País
                    <input type="text" name="default_country" value="<?php echo esc_attr($default_country); ?>" placeholder="Ej: Chile" required>
                </label>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($profile_view === 'rules'): ?>
            <div class="cg-card cg-profile-subview-card cg-profile-subview-section cg-profile-terms" id="cg-profile-terms">
                <div class="cg-profile-subview-section__head">
                    <h4>Normas del juego</h4>
                    <p>Mantén esta aceptación al día para publicar con normalidad.</p>
                </div>
            <?php if ($terms_accepted): ?>
                <?php
                $terms_accepted_label = '';
                if ($terms_accepted_at !== '') {
                    $wp_timezone = wp_timezone();
                    $accepted_dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $terms_accepted_at, $wp_timezone);
                    if ($accepted_dt instanceof DateTimeImmutable) {
                        $terms_accepted_label = wp_date('d/m/Y \a \l\a\s H:i', $accepted_dt->getTimestamp(), $wp_timezone);
                    } else {
                        $terms_accepted_ts = strtotime($terms_accepted_at);
                        if ($terms_accepted_ts) {
                            $terms_accepted_label = wp_date('d/m/Y \a \l\a\s H:i', $terms_accepted_ts, $wp_timezone);
                        }
                    }
                }
                ?>
                <p class="cg-alert cg-alert-success">✅ Normas aceptadas<?php echo $terms_accepted_label !== '' ? ' el ' . esc_html($terms_accepted_label) : ''; ?>.</p>
            <?php else: ?>
                <label class="cg-terms-checkbox">
                    <input type="checkbox" name="accept_terms" value="1">
                    Acepto las normas y sanciones del juego
                </label>
                <button type="button" class="secondary" data-open-upload-rules="1">Ver normas</button>
            <?php endif; ?>
            </div>
        <?php endif; ?>
    </form>

    <div class="cg-modal" id="catgame-upload-rules-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="catgame-upload-rules-title">
        <div class="cg-modal__backdrop" data-upload-rules-close="1"></div>
        <div class="cg-modal__content" role="document">
            <button type="button" class="cg-modal__close" data-upload-rules-close="1" aria-label="Cerrar normas">✕</button>
            <h2 id="catgame-upload-rules-title">Normas y sanciones</h2>
            <div class="cg-modal__rules">
                <section class="cg-modal__rules-section">
                    <h3>Qué sí está permitido</h3>
                    <ul>
                        <li>Mascotas domésticas.</li>
                        <li>Ejemplos: perro, gato, conejo, gallina, pez, etc.</li>
                    </ul>
                </section>
                <section class="cg-modal__rules-section">
                    <h3>Qué no se permite</h3>
                    <ul>
                        <li>Fauna silvestre o exótica.</li>
                        <li>Personas.</li>
                        <li>Contenido sexual, explícito o violento.</li>
                        <li>Maltrato animal.</li>
                        <li>Spam o imágenes que no correspondan.</li>
                    </ul>
                </section>
                <section class="cg-modal__rules-section">
                    <h3>Sanciones</h3>
                    <ul>
                        <li><strong>Leve:</strong> se elimina la publicación.</li>
                        <li><strong>Moderada:</strong> se elimina la publicación + no puede subir por 3 días.</li>
                        <li><strong>Grave:</strong> se elimina la publicación + no puede subir ni reaccionar + apelación 24h; si no apela o se rechaza, cuenta eliminada y ban permanente.</li>
                    </ul>
                </section>
                <section class="cg-modal__rules-section">
                    <h3>Apelaciones</h3>
                    <ul>
                        <li>Leve y moderada: 72 horas.</li>
                        <li>Grave: 24 horas.</li>
                    </ul>
                </section>
            </div>
            <div class="cg-confirm-actions">
                <button type="button" data-upload-rules-close="1">Entendido</button>
            </div>
        </div>
    </div>


    <?php if ($profile_view === 'account'): ?>
    <article class="cg-card cg-account-status-card cg-profile-subview-card" id="cg-account-status">
        <div class="cg-profile-subview-section__head">
            <h3>Estado de tu cuenta</h3>
            <p>Consulta aquí tu estado actual y cualquier restricción activa.</p>
        </div>
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

    <?php endif; ?>

    <?php if ($profile_view === 'stats' && !empty($top_position_for_user)): ?>
        <p class="cg-alert cg-alert-success">🏆 Estás en el Top 3 de La Arena: #<?php echo (int) $top_position_for_user; ?>. <a href="<?php echo esc_url(home_url('/catgame/leaderboard')); ?>">Ver ranking</a></p>
    <?php endif; ?>

    <?php if ($profile_view === 'stats'): ?>
    <section class="cg-card cg-profile-subview-card cg-profile-subview-section">
        <div class="cg-profile-subview-section__head">
            <h3>Resumen de actividad</h3>
            <p>Filtra el alcance y revisa tus métricas más importantes en un solo lugar.</p>
        </div>
        <form method="get" action="<?php echo esc_url($profile_base_url); ?>" class="cg-form-inline cg-profile-stats-filter">
        <input type="hidden" name="profile_view" value="stats">
        <label>Alcance
            <select name="scope">
                <option value="event" <?php selected($scope, 'event'); ?>>La Arena activa</option>
                <option value="global" <?php selected($scope, 'global'); ?>>Global</option>
            </select>
        </label>
        <button type="submit">Aplicar</button>
        </form>
    </section>

    <h3 class="cg-profile-section-title" id="cg-profile-summary">Resumen</h3>
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

    <h3 class="cg-profile-section-title">Tu publicación destacada</h3>
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
        <button type="button" class="secondary js-share-link" data-url="<?php echo esc_url($best_photo_link ?: $profile_link); ?>" data-share-title="<?php echo esc_attr($brand_name); ?>" data-share-text="Mira esta publicación destacada en <?php echo esc_attr($brand_name); ?>">Compartir mi publicación destacada</button>
    </div>

    <?php endif; ?>

    <?php if ($profile_view === 'community'): ?>
    <section class="cg-card cg-profile-subview-card cg-profile-subview-section" id="cg-profile-community">
        <div class="cg-profile-subview-section__head">
            <h3>Comunidad</h3>
            <p>¿Ideas para eventos futuros? Escríbenos en Instagram.</p>
        </div>
        <a class="cg-cta" href="<?php echo esc_url(CATGAME_INSTAGRAM_URL); ?>" target="_blank" rel="noopener noreferrer">Ir a Instagram</a>
    </section>


    <?php endif; ?>

    <?php if ($profile_view === 'feedback'): ?>
    <section class="cg-card cg-feedback-card cg-profile-subview-card cg-profile-subview-section" id="cg-profile-feedback">
        <div class="cg-profile-subview-section__head">
            <h3>Ayúdanos a mejorar</h3>
            <p>Puedes enviarnos comentarios, sugerencias o reportar errores que encuentres en el juego.</p>
        </div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-form cg-feedback-form">
            <?php wp_nonce_field('catgame_submit_feedback'); ?>
            <input type="hidden" name="action" value="catgame_submit_feedback">
            <input type="hidden" name="feedback_source_page" value="profile">

            <label>Tipo de mensaje
                <select name="feedback_type" required>
                    <option value="comment">Comentario</option>
                    <option value="suggestion">Sugerencia</option>
                    <option value="technical_error">Error técnico</option>
                    <option value="bug_report">Reporte de bug</option>
                </select>
            </label>

            <label>Mensaje
                <textarea name="feedback_message" rows="4" maxlength="1200" placeholder="Escribe aquí tu comentario, sugerencia o describe el error que encontraste." required></textarea>
            </label>

            <button type="submit">Enviar comentario</button>
        </form>
    </section>

    <?php endif; ?>

    <?php if ($profile_view === 'tags'): ?>
    <section class="cg-card cg-profile-subview-card cg-profile-subview-section" id="cg-profile-tags">
        <div class="cg-profile-subview-section__head">
            <h3>Mis etiquetas</h3>
            <p>Gestiona las etiquetas personalizadas que usas para clasificar a tus mascotas.</p>
        </div>
    <?php if (empty($custom_tags)): ?>
        <p class="cg-profile-empty-state">No tienes etiquetas todavía.</p>
    <?php else: ?>
        <ul class="cg-profile-tags-list">
            <?php foreach ($custom_tags as $tag => $label): ?>
                <li class="cg-tag-item">
                    <strong><?php echo esc_html(CatGame_Submissions::label_for_tag($tag, get_current_user_id())); ?></strong>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-tag-delete-form" data-cg-confirm="1" data-cg-confirm-title="Eliminar etiqueta" data-cg-confirm-text="Esta acción no se puede deshacer. ¿Eliminar esta etiqueta?">
                        <?php wp_nonce_field('catgame_delete_custom_tag'); ?>
                        <input type="hidden" name="action" value="catgame_delete_custom_tag">
                        <input type="hidden" name="tag" value="<?php echo esc_attr($tag); ?>">
                        <button type="submit" class="cg-tag-delete cg-tag-remove" aria-label="Eliminar etiqueta <?php echo esc_attr(CatGame_Submissions::label_for_tag($tag, get_current_user_id())); ?>" title="Eliminar etiqueta">Eliminar</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    </section>

    <?php endif; ?>

    <?php if ($profile_view === 'notifications'): ?>
        <section class="cg-card cg-profile-subview-card cg-profile-subview-section">
            <div class="cg-profile-subview-section__head">
                <h3>Notificaciones</h3>
                <p>Abre tus notificaciones desde aquí sin recargar la pantalla principal del perfil.</p>
            </div>
            <button type="button" class="secondary" data-notifications-open="1">Ver notificaciones</button>
        </section>
    <?php endif; ?>

    <?php if ($is_profile_main_view): ?>
    <h3>Mis publicaciones</h3>
    <div class="cg-grid">
        <?php if (!$items): ?>
            <p>Aún no tienes publicaciones.</p>
        <?php endif; ?>
        <?php foreach ($items as $item): ?>
            <?php
                $item_title = CatGame_Submissions::title_label($item);
                $item_reactions = (int) ($item['total_reactions'] ?? 0);
                $item_is_event = (int) ($item['event_id'] ?? 0) > 0;
                $item_location = CatGame_Submissions::visual_label((string) ($item['city'] ?? '')) . ', ' . CatGame_Submissions::visual_label((string) ($item['country'] ?? ''));
                $appeal_html = class_exists('CatGame_Reports') ? CatGame_Reports::appeal_button_html((array) $item, (int) get_current_user_id()) : '';
                if (is_string($appeal_html) && strpos($appeal_html, 'No existe una moderación activa para esta publicación.') !== false) {
                    $appeal_html = '';
                }
                ?>
            <article class="cg-card cg-profile-item">
                <header class="cg-profile-item__header">
                    <div class="cg-profile-item__title-wrap">
                        <p class="cg-profile-item__title"><span class="cg-badge">#<?php echo (int) ($item['id'] ?? 0); ?></span> <?php echo esc_html($item_title); ?></p>
                        <small class="cg-profile-item__badge-line">
                            <span class="cg-inline-badge"><?php echo $item_is_event ? '🏆 La Arena' : '🐾 El Parque'; ?></span>
                        </small>
                    </div>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-inline-delete-form" data-cg-confirm="1" data-cg-confirm-title="Eliminar publicación" data-cg-confirm-text="Esta acción no se puede deshacer. ¿Eliminar esta publicación?">
                        <?php wp_nonce_field('catgame_delete_submission'); ?>
                        <input type="hidden" name="action" value="catgame_delete_submission">
                        <input type="hidden" name="submission_id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                        <button type="submit" class="cg-tag-delete cg-profile-item__delete">Eliminar</button>
                    </form>
                </header>

                <?php echo wp_get_attachment_image((int) $item['attachment_id'], 'medium_large', false, ['loading' => 'lazy', 'class' => 'cg-profile-thumb']); ?>

                <p class="cg-profile-item__location">📍 <?php echo esc_html($item_location); ?></p>
                <p class="cg-profile-item__total">Reacciones: <?php echo (int) $item_reactions; ?></p>

                <?php echo $appeal_html; ?>
                <?php echo CatGame_Submissions::review_appeal_button_html((array) $item, (int) get_current_user_id()); ?>
                <?php CatGame_Reactions::render_widget((int) ($item['id'] ?? 0), is_user_logged_in(), (array) ($item['reaction_counts'] ?? []) ? ['reaction_counts' => (array) ($item['reaction_counts'] ?? []), 'my_reaction' => ($item['my_reaction'] ?? null)] : []); ?>
            </article>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>

    <div class="cg-modal" id="catgame-profile-logout-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="catgame-profile-logout-title">
        <div class="cg-modal__backdrop" data-profile-logout-close="1"></div>
        <div class="cg-modal__content cg-profile-logout-modal" role="document">
            <button type="button" class="cg-modal__close" data-profile-logout-close="1" aria-label="Cerrar confirmación">✕</button>
            <h2 id="catgame-profile-logout-title">¿Estás seguro que quieres cerrar sesión?</h2>
            <p class="cg-modal__intro">Podrás volver a entrar cuando quieras con tu cuenta actual.</p>
            <div class="cg-confirm-actions">
                <button type="button" class="secondary" data-profile-logout-close="1">Cancelar</button>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="cg-profile-menu__logout-form">
                    <?php wp_nonce_field('catgame_logout'); ?>
                    <input type="hidden" name="action" value="catgame_logout">
                    <button type="submit">Cerrar sesión</button>
                </form>
            </div>
        </div>
    </div>

    <div class="cg-modal" id="catgame-notifications-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="catgame-notifications-title">
        <div class="cg-modal__backdrop" data-notifications-close="1"></div>
        <div class="cg-modal__content" role="document">
            <button type="button" class="cg-modal__close" data-notifications-close="1" aria-label="Cerrar notificaciones">Eliminar</button>
            <h2 id="catgame-notifications-title">Notificaciones</h2>
            <ul class="cg-notifications-list" id="catgame-notifications-list">
                <li class="cg-notifications-empty">No tienes notificaciones</li>
            </ul>
        </div>
    </div>

</section>
