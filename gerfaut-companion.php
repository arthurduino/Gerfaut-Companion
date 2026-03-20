<?php
/**
 * Plugin Name: Gerfaut Companion
 * Description: Extension compagnon pour afficher des informations sur le dashboard WordPress et la liste des commandes WooCommerce. Inclut les shortcodes [gerfaut_sav], [gerfaut_contact], [gerfaut_sticker] pour intégrer les formulaires.
 * Version: 1.3.19
 * Author: Gerfaut
 */

define('GERFAUT_COMPANION_VERSION', '1.3.19');
define('GERFAUT_COMPANION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GERFAUT_COMPANION_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-embed-shortcodes.php';
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-embed-blocks.php';
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-dashboard-widget.php';
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-orders-columns.php';
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-woo-email-savelink.php';
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-address-validation.php';
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-sticker-builder.php';

function gerfaut_companion_admin_styles() {
    wp_enqueue_style('gerfaut-companion-admin', GERFAUT_COMPANION_PLUGIN_URL . 'assets/css/admin.css', array(), GERFAUT_COMPANION_VERSION);
}
add_action('admin_enqueue_scripts', 'gerfaut_companion_admin_styles');

function gerfaut_companion_frontend_assets() {
    wp_enqueue_script('gerfaut-companion-sticker', GERFAUT_COMPANION_PLUGIN_URL . 'assets/js/sticker-builder.js', array('jquery'), GERFAUT_COMPANION_VERSION, true);
    wp_enqueue_style('gerfaut-companion-sticker-css', GERFAUT_COMPANION_PLUGIN_URL . 'assets/css/sticker-builder.css', array(), GERFAUT_COMPANION_VERSION);
    wp_localize_script('gerfaut-companion-sticker', 'gerfautSticker', array('ajaxUrl' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'gerfaut_companion_frontend_assets');

function gerfaut_companion_block_editor_assets() {
    wp_enqueue_script('gerfaut-companion-blocks', GERFAUT_COMPANION_PLUGIN_URL . 'assets/js/blocks.js', array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components'), GERFAUT_COMPANION_VERSION, true);
}
add_action('enqueue_block_editor_assets', 'gerfaut_companion_block_editor_assets');

function gerfaut_companion_activate() {
    add_option('gerfaut_companion_activated', time());
}
register_activation_hook(__FILE__, 'gerfaut_companion_activate');

function gerfaut_companion_deactivate() {
    // Cleanup if needed
}
register_deactivation_hook(__FILE__, 'gerfaut_companion_deactivate');
