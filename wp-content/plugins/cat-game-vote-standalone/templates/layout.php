<?php
if (!defined('ABSPATH')) {
    exit;
}
$current_page = $data['page'] ?? 'home';
$event = $data['event'] ?? null;
$event_rules = [];
$event_name = '';
$event_date_range = '';

if (is_array($event) && !empty($event['id'])) {
    $event_name = sanitize_text_field((string) ($event['name'] ?? 'Evento vigente'));
    $event_rules = CatGame_Events::decode_rules(isset($event['rules_json']) ? (string) $event['rules_json'] : '');
    $starts_at = isset($event['starts_at']) ? strtotime((string) $event['starts_at']) : false;
    $ends_at = isset($event['ends_at']) ? strtotime((string) $event['ends_at']) : false;

    if ($starts_at && $ends_at) {
        $event_date_range = wp_date('d/m/Y H:i', $starts_at) . ' - ' . wp_date('d/m/Y H:i', $ends_at);
    }
}
$background_style = '';
$has_background = !empty($background_url) && is_string($background_url);
$bottom_nav_items = [
    ['page' => 'feed', 'label' => 'Publicaciones', 'icon' => '🐱', 'url' => home_url('/catgame/feed')],
    ['page' => 'leaderboard', 'label' => 'Ranking', 'icon' => '🏆', 'url' => home_url('/catgame/leaderboard')],
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
        <h1>🐱 Cat Game Vote</h1>
        <nav class="cg-nav" aria-label="Navegación principal">
            <?php foreach (CatGame_Render::nav_items() as $item): ?>
                <?php $is_active = ($current_page === ($item['page'] ?? '')); ?>
                <a class="<?php echo $is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url($item['url']); ?>"><?php echo esc_html($item['label']); ?></a>
            <?php endforeach; ?>
        </nav>
    </header>
    <main class="cg-main">
        <?php CatGame_Render::render_page($data['page'] ?? 'home', $data); ?>
    </main>
</div>

<?php if (!empty($event_rules) && !empty($event['id'])): ?>
    <button
        type="button"
        class="cg-event-rules-trigger"
        id="catgame-event-rules-trigger"
        data-event-id="<?php echo (int) $event['id']; ?>"
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
            <p class="cg-modal__intro">Estas son las reglas y bonificaciones del evento activo:</p>
            <ul class="cg-modal__rules">
                <?php foreach ($event_rules as $rule_tag => $rule_points): ?>
                    <li>
                        <span><?php echo esc_html(CatGame_Submissions::label_for_tag((string) $rule_tag)); ?></span>
                        <strong>+<?php echo esc_html(number_format_i18n((float) $rule_points, 1)); ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

    <nav class="catgame-bottom-nav" aria-label="Navegación inferior">
        <?php foreach ($bottom_nav_items as $item): ?>
            <?php $is_bottom_active = ($current_page === $item['page']); ?>
            <a href="<?php echo esc_url($item['url']); ?>" class="nav-item <?php echo $is_bottom_active ? 'active' : ''; ?>" aria-current="<?php echo $is_bottom_active ? 'page' : 'false'; ?>">
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

<div id="catgame-toast" class="catgame-toast" aria-live="polite" aria-atomic="true"></div>
<script>
window.CATGAME_REACTIONS = {
    nonce: <?php echo wp_json_encode(wp_create_nonce(CatGame_Reactions::nonce_action())); ?>,
    addOrUpdateUrl: <?php echo wp_json_encode(CatGame_Reactions::endpoint_add_or_update_url()); ?>,
    getCountsUrl: <?php echo wp_json_encode(CatGame_Reactions::endpoint_get_counts_url()); ?>,
};
</script>
<script src="<?php echo esc_url(CATGAME_PLUGIN_URL . 'assets/app.js'); ?>?v=<?php echo esc_attr(CATGAME_VERSION); ?>"></script>
</body>
</html>
