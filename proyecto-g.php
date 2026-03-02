<?php
/**
 * Plugin Name: Proyecto G
 * Description: Plugin base.
 * Version: 0.1.1
 * Author: Tu equipo
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action('admin_notices', function () {
    echo '<div class="notice notice-success"><p><strong>Proyecto G</strong> activo.</p></div>';
});
