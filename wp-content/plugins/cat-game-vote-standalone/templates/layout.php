<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html($title); ?></title>
    <link rel="stylesheet" href="<?php echo esc_url(CATGAME_PLUGIN_URL . 'assets/app.css'); ?>?v=<?php echo esc_attr(CATGAME_VERSION); ?>">
</head>
<body>
<div class="cg-shell">
    <header class="cg-header">
        <h1>🐱 Cat Game Vote</h1>
        <nav>
            <?php foreach (CatGame_Render::nav_items() as $label => $url): ?>
                <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($label); ?></a>
            <?php endforeach; ?>
        </nav>
    </header>
    <main class="cg-main">
        <?php CatGame_Render::render_page($data['page'] ?? 'home', $data); ?>
    </main>
</div>
<script src="<?php echo esc_url(CATGAME_PLUGIN_URL . 'assets/app.js'); ?>?v=<?php echo esc_attr(CATGAME_VERSION); ?>"></script>
</body>
</html>
