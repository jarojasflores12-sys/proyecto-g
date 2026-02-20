<?php
if (!defined('ABSPATH')) {
    exit;
}
$current_page = $data['page'] ?? 'home';
$background_style = '';
$has_background = !empty($background_url) && is_string($background_url);
$bottom_nav_items = [
    ['page' => 'home', 'label' => 'Inicio', 'icon' => '🏠', 'url' => home_url('/catgame/')],
    ['page' => 'upload', 'label' => 'Subir', 'icon' => '📷', 'url' => home_url('/catgame/upload')],
    ['page' => 'feed', 'label' => 'Feed', 'icon' => '🐱', 'url' => home_url('/catgame/feed')],
    ['page' => 'leaderboard', 'label' => 'Ranking', 'icon' => '🏆', 'url' => home_url('/catgame/leaderboard')],
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

    <nav class="catgame-bottom-nav" aria-label="Navegación inferior">
        <?php foreach ($bottom_nav_items as $item): ?>
            <?php $is_bottom_active = ($current_page === $item['page']); ?>
            <a href="<?php echo esc_url($item['url']); ?>" class="nav-item <?php echo $is_bottom_active ? 'active' : ''; ?>" aria-current="<?php echo $is_bottom_active ? 'page' : 'false'; ?>">
                <span class="icon" aria-hidden="true"><?php echo esc_html($item['icon']); ?></span>
                <span><?php echo esc_html($item['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
<div id="catgame-toast" class="catgame-toast" aria-live="polite" aria-atomic="true"></div>
<script src="<?php echo esc_url(CATGAME_PLUGIN_URL . 'assets/app.js'); ?>?v=<?php echo esc_attr(CATGAME_VERSION); ?>"></script>
</body>
</html>
