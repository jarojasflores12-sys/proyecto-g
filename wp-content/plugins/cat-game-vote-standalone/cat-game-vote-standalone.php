<?php
/**
 * Plugin Name: Cat Game Vote Standalone
 * Description: Frontend standalone para juego de gatos con votación comunitaria y moderación manual.
 * Version: 0.24.5
 * Author: Codex
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CATGAME_VERSION', '0.24.5');
define('CATGAME_PLUGIN_FILE', __FILE__);
define('CATGAME_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CATGAME_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CATGAME_INSTAGRAM_URL', 'https://instagram.com/');

require_once CATGAME_PLUGIN_DIR . 'includes/class-db.php';
require_once CATGAME_PLUGIN_DIR . 'includes/class-events.php';
require_once CATGAME_PLUGIN_DIR . 'includes/class-submissions.php';
require_once CATGAME_PLUGIN_DIR . 'includes/class-votes.php';
require_once CATGAME_PLUGIN_DIR . 'includes/class-reactions.php';
require_once CATGAME_PLUGIN_DIR . 'includes/class-auth.php';
require_once CATGAME_PLUGIN_DIR . 'includes/class-render.php';
require_once CATGAME_PLUGIN_DIR . 'includes/class-router.php';
require_once CATGAME_PLUGIN_DIR . 'includes/class-admin.php';

register_activation_hook(__FILE__, ['CatGame_DB', 'activate']);
register_deactivation_hook(__FILE__, ['CatGame_DB', 'deactivate']);

add_action('plugins_loaded', static function () {
    CatGame_DB::init();
    CatGame_Events::init();
    CatGame_Submissions::init();
    CatGame_Votes::init();
    CatGame_Reactions::init();
    CatGame_Auth::init();
    CatGame_Render::init();
    CatGame_Router::init();
    CatGame_Admin::init();
});
