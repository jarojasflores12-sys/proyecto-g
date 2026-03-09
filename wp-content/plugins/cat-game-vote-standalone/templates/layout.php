<?php
if (!defined('ABSPATH')) {
    exit;
}
$current_page = $data['page'] ?? 'home';
$nav_current_page = in_array($current_page, ['adoption-new', 'adoption-detail'], true) ? 'adoptions' : $current_page;
$event = $data['event'] ?? null;
$event_rules_view = [];
$event_name = '';
$event_date_range = '';
$event_rules_mode = 'none';
$event_rules_items = [];
$event_general_rules = [];
$event_type = 'competitive';
$event_revision = '';

if (is_array($event) && !empty($event['id'])) {
    $event_rules_view = CatGame_Events::build_rules_popup_view($event);
    $event_name = sanitize_text_field((string) ($event_rules_view['name'] ?? 'Evento vigente'));
    $event_date_range = sanitize_text_field((string) ($event_rules_view['date_range'] ?? ''));
    $event_rules_mode = sanitize_key((string) ($event_rules_view['mode'] ?? 'none'));
    $event_rules_items = is_array($event_rules_view['items'] ?? null) ? $event_rules_view['items'] : [];
    $event_general_rules = is_array($event_rules_view['general_summary'] ?? null) ? $event_rules_view['general_summary'] : [];
    $event_type = sanitize_key((string) ($event_rules_view['event_type'] ?? 'competitive'));
    $event_revision = md5((string) ($event['id'] ?? 0) . '|' . (string) ($event['name'] ?? '') . '|' . (string) ($event['starts_at'] ?? '') . '|' . (string) ($event['ends_at'] ?? '') . '|' . (string) ($event['rules_json'] ?? '') . '|' . (string) ($event['event_type'] ?? 'competitive') . '|' . (string) ($event['is_active'] ?? 0));
}
$background_style = '';
$branding = is_array($data['branding'] ?? null) ? $data['branding'] : CatGame_Admin::get_frontend_branding();
$brand_name = sanitize_text_field((string) ($branding['name'] ?? 'PetUnity'));
if ($brand_name === '') {
    $brand_name = 'PetUnity';
}
$brand_subtitle = sanitize_text_field((string) ($branding['subtitle'] ?? ''));
$has_background = !empty($background_url) && is_string($background_url);
$bottom_nav_items = [
    ['page' => 'feed', 'label' => 'Publicaciones', 'icon' => '🐱', 'url' => home_url('/catgame/feed')],
    ['page' => 'leaderboard', 'label' => 'Ranking', 'icon' => '🏆', 'url' => home_url('/catgame/leaderboard')],
    ['page' => 'adoptions', 'label' => 'Adopciones', 'icon' => '🏡', 'url' => home_url('/catgame/adoptions')],
    ['page' => 'home', 'label' => 'Inicio', 'icon' => '🏠', 'url' => home_url('/catgame/')],
    ['page' => 'upload', 'label' => 'Subir', 'icon' => '📷', 'url' => home_url('/catgame/upload')],
    ['page' => 'profile', 'label' => 'Perfil', 'icon' => '👤', 'url' => home_url('/catgame/profile')],
];

if ($has_background) {
    $background_style = sprintf('--catgame-bg-image:url(%s);', esc_url($background_url));
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($title); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(CATGAME_PLUGIN_URL . 'assets/app.css'); ?>?v=<?php echo esc_attr(CATGAME_VERSION); ?>">
</head>
<body class="<?php echo $has_background ? 'cg-has-custom-bg' : ''; ?>" style="<?php echo esc_attr($background_style); ?>">
<div class="cg-shell catgame-layout">
    <header class="cg-header">
        <div class="cg-brand">
            <h1>🐾 <?php echo esc_html($brand_name); ?></h1>
            <?php if ($brand_subtitle !== ''): ?><p><?php echo esc_html($brand_subtitle); ?></p><?php endif; ?>
        </div>
        <nav class="cg-nav" aria-label="Navegación principal">
            <?php foreach (CatGame_Render::nav_items() as $item): ?>
                <?php $is_active = ($nav_current_page === ($item['page'] ?? '')); ?>
                <a class="<?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a>
            <?php endforeach; ?>
        </nav>
    </header>
    <main class="cg-main">
        <?php CatGame_Render::render_page($data['page'] ?? 'home', $data); ?>
    </main>
</div>

<?php if (is_array($event) && !empty($event['id'])): ?>
    <button
        type="button"
        class="cg-event-rules-trigger"
        id="catgame-event-rules-trigger"
        data-event-id="<?php echo (int) $event['id']; ?>"
        data-event-revision="<?php echo esc_attr($event_revision); ?>"
        aria-controls="catgame-event-rules-modal"
        aria-expanded="false"
    >
        📜 Reglas del evento
    </button>

    <div
        class="cg-modal"
        id="catgame-event-rules-modal"
        data-event-id="<?php echo (int) $event['id']; ?>"
        aria-hidden="true"
        role="dialog"
        aria-modal="true"
        aria-labelledby="catgame-event-rules-title"
    >
        <div class="cg-modal__backdrop" data-modal-close="1"></div>
        <div class="cg-modal__content" role="document">
            <button type="button" class="cg-modal__close" data-modal-close="1" aria-label="Cerrar popup de reglas">✕</button>
            <h2 id="catgame-event-rules-title">📣 <?php echo esc_html($event_name !== '' ? $event_name : 'Evento vigente'); ?></h2>
            <?php if ($event_date_range !== ''): ?>
                <p class="cg-modal__dates"><strong>Vigencia:</strong> <?php echo esc_html($event_date_range); ?></p>
            <?php endif; ?>
            <?php if ($event_type === 'thematic'): ?>
                <p class="cg-modal__intro">Este evento es temático. Las publicaciones relacionadas no compiten en ranking.</p>
                <p class="cg-modal__intro">Reglas generales (resumen):</p>
                <ul class="cg-modal__rules">
                    <?php foreach ($event_general_rules as $line): ?>
                        <li><?php echo esc_html((string) $line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php elseif ($event_rules_mode === 'none'): ?>
                <p class="cg-modal__intro">Reglas generales (resumen):</p>
                <ul class="cg-modal__rules">
                    <?php foreach ($event_general_rules as $line): ?>
                        <li><?php echo esc_html((string) $line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="cg-modal__intro">Estas son las reglas del evento activo:</p>
                <ul class="cg-modal__rules">
                    <?php foreach ($event_rules_items as $item): ?>
                        <?php
                        $rule_type = sanitize_key((string) ($item['type'] ?? 'tema'));
                        $rule_title = sanitize_text_field((string) ($item['title'] ?? 'Regla'));
                        $rule_desc = sanitize_text_field((string) ($item['desc'] ?? ''));
                        $rule_value = is_numeric($item['value'] ?? null) ? (float) $item['value'] : null;
                        ?>
                        <li>
                            <span><?php echo esc_html($rule_title); ?><?php if ($rule_desc !== ''): ?> — <?php echo esc_html($rule_desc); ?><?php endif; ?></span>
                            <?php if ($rule_type === 'bonus' && $rule_value !== null): ?><strong>+<?php echo esc_html(number_format_i18n($rule_value, 1)); ?></strong><?php endif; ?>
                            <?php if ($rule_type === 'penalizacion' && $rule_value !== null): ?><strong>-<?php echo esc_html(number_format_i18n($rule_value, 1)); ?></strong><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="cg-modal__intro">Reglas generales (resumen):</p>
                <ul class="cg-modal__rules">
                    <?php foreach ($event_general_rules as $line): ?>
                        <li><?php echo esc_html((string) $line); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

    <nav class="catgame-bottom-nav" aria-label="Navegación inferior">
        <?php foreach ($bottom_nav_items as $item): ?>
            <?php $is_bottom_active = ($nav_current_page === $item['page']); ?>
            <a href="<?php echo esc_url($item['url']); ?>" class="nav-item <?php echo $is_bottom_active ? 'active' : ''; ?> <?php echo $item['page'] === 'home' ? 'is-home' : ''; ?>" data-page="<?php echo esc_attr($item['page']); ?>" aria-current="<?php echo $is_bottom_active ? 'page' : 'false'; ?>">
                <span class="icon" aria-hidden="true"><?php echo esc_html($item['icon']); ?></span>
                <span><?php echo esc_html($item['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

<div class="cg-modal" id="catgame-confirm-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="catgame-confirm-title">
    <div class="cg-modal__backdrop" data-confirm-close="1"></div>
    <div class="cg-modal__content" role="document">
        <button type="button" class="cg-modal__close" data-confirm-close="1" aria-label="Cerrar confirmación">✕</button>
        <h2 id="catgame-confirm-title">Confirmar acción</h2>
        <p id="catgame-confirm-text">¿Deseas continuar?</p>
        <div class="cg-confirm-actions">
            <button type="button" class="secondary" id="catgame-confirm-cancel" data-confirm-close="1">Cancelar</button>
            <button type="button" id="catgame-confirm-accept">Eliminar</button>
        </div>
    </div>
</div>


<div class="cg-modal" id="catgame-report-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="catgame-report-title">
    <div class="cg-modal__backdrop" data-report-close="1"></div>
    <div class="cg-modal__content" role="document">
        <button type="button" class="cg-modal__close" data-report-close="1" aria-label="Cerrar reporte">✕</button>
        <h2 id="catgame-report-title">Reportar publicación</h2>
        <form id="catgame-report-form">
            <input type="hidden" name="submission_id" id="catgame-report-submission-id" value="0">
            <input type="hidden" name="nonce" id="catgame-report-nonce" value="">
            <div class="cg-report-reasons">
                <label><input type="radio" name="reason" value="not_pet" checked> No es una mascota</label>
                <label><input type="radio" name="reason" value="human"> Aparece una persona</label>
                <label><input type="radio" name="reason" value="inappropriate"> Contenido inapropiado</label>
                <label><input type="radio" name="reason" value="other"> Otro</label>
            </div>
            <label>Detalle (opcional, máx 250)
                <textarea name="detail" maxlength="250" rows="3" placeholder="Describe brevemente el problema"></textarea>
            </label>
            <div class="cg-confirm-actions">
                <button type="button" class="secondary" data-report-close="1">Cancelar</button>
                <button type="submit">Enviar reporte</button>
            </div>
        </form>
    </div>
</div>

<div class="cg-modal" id="catgame-appeal-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="catgame-appeal-title">
    <div class="cg-modal__backdrop" data-appeal-close="1"></div>
    <div class="cg-modal__content" role="document">
        <button type="button" class="cg-modal__close" data-appeal-close="1" aria-label="Cerrar apelación">✕</button>
        <h2 id="catgame-appeal-title">Apelar moderación</h2>
        <p>Explica brevemente por qué consideras que la moderación debe revisarse.</p>
        <form id="catgame-appeal-form">
            <input type="hidden" name="submission_id" id="catgame-appeal-submission-id" value="0">
            <label>Mensaje (máx 500)
                <textarea name="message" maxlength="500" rows="4" placeholder="Escribe tu apelación"></textarea>
            </label>
            <div class="cg-confirm-actions">
                <button type="button" class="secondary" data-appeal-close="1">Cancelar</button>
                <button type="submit">Enviar apelación</button>
            </div>
        </form>
    </div>
</div>

<div id="catgame-toast" class="catgame-toast" aria-live="polite" aria-atomic="true"></div>
<script>
window.CATGAME_REACTIONS = {
    nonce: <?php echo wp_json_encode(wp_create_nonce(CatGame_Reactions::nonce_action())); ?>,
    addOrUpdateUrl: <?php echo wp_json_encode(CatGame_Reactions::endpoint_add_or_update_url()); ?>,
    getCountsUrl: <?php echo wp_json_encode(CatGame_Reactions::endpoint_get_counts_url()); ?>,
};
window.CATGAME_FEED = {
    nonce: <?php echo wp_json_encode(wp_create_nonce('catgame_feed_more')); ?>,
    moreUrl: <?php echo wp_json_encode(admin_url('admin-post.php?action=catgame_feed_more')); ?>,
};
window.CATGAME = {
    ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
    nonce: <?php echo wp_json_encode(wp_create_nonce('catgame_nonce')); ?>,
};
</script>
<script src="<?php echo esc_url(CATGAME_PLUGIN_URL . 'assets/app.js'); ?>?v=<?php echo esc_attr(CATGAME_VERSION); ?>"></script>
</body>
</html>
