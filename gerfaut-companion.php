<?php
/**
 * Plugin Name: Gerfaut Companion
 * Plugin URI: https://gerfaut.mooo.com
 * Description: Extension compagnon pour afficher des informations sur le dashboard WordPress et la liste des commandes WooCommerce. Inclut les shortcodes [gerfaut_sav] et [gerfaut_contact] pour intégrer les formulaires.
 * Version: 1.1.4
 * Author: Gerfaut
 * Author URI: https://gerfaut.mooo.com
 * Text Domain: gerfaut-companion
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('GERFAUT_COMPANION_VERSION', '1.1.4');
define('GERFAUT_COMPANION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GERFAUT_COMPANION_PLUGIN_URL', plugin_dir_url(__FILE__));

// Charger l'autoloader Composer si disponible
if (file_exists(GERFAUT_COMPANION_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once GERFAUT_COMPANION_PLUGIN_DIR . 'vendor/autoload.php';
}

// Configuration des mises à jour automatiques depuis GitHub
if (class_exists('YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
    $updateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
        'https://github.com/arthurduino/Gerfaut-Companion',
        __FILE__,
        'gerfaut-companion'
    );
    
    // Définir la branche
    $updateChecker->setBranch('main');
}

// Include shortcodes (always available, even without WooCommerce)
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-embed-shortcodes.php';
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-embed-blocks.php';

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'gerfaut_companion_woocommerce_missing_notice');
    return;
}

function gerfaut_companion_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e('Gerfaut Companion nécessite WooCommerce pour fonctionner. Veuillez installer et activer WooCommerce.', 'gerfaut-companion'); ?></p>
    </div>
    <?php
}

// Include plugin classes
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-dashboard-widget.php';
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-orders-columns.php';
require_once GERFAUT_COMPANION_PLUGIN_DIR . 'includes/class-woo-email-savelink.php';

// Declare HPOS compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

// Initialize plugin
function gerfaut_companion_init() {
    // Initialize dashboard widget
    new Gerfaut_Companion_Dashboard_Widget();
    
    // Initialize orders columns
    new Gerfaut_Companion_Orders_Columns();
    
    // Shortcodes embed are initialized directly in their class file
}
add_action('plugins_loaded', 'gerfaut_companion_init');

// Enqueue admin styles
function gerfaut_companion_admin_styles() {
    wp_enqueue_style(
        'gerfaut-companion-admin',
        GERFAUT_COMPANION_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        GERFAUT_COMPANION_VERSION
    );
}
add_action('admin_enqueue_scripts', 'gerfaut_companion_admin_styles');

// Enqueue block editor assets
function gerfaut_companion_block_editor_assets() {
    wp_enqueue_script(
        'gerfaut-companion-blocks',
        GERFAUT_COMPANION_PLUGIN_URL . 'assets/js/blocks.js',
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components'),
        GERFAUT_COMPANION_VERSION,
        true
    );
}
add_action('enqueue_block_editor_assets', 'gerfaut_companion_block_editor_assets');

// Activation hook
register_activation_hook(__FILE__, 'gerfaut_companion_activate');
function gerfaut_companion_activate() {
    // Set default options
    add_option('gerfaut_companion_activated', time());
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'gerfaut_companion_deactivate');
function gerfaut_companion_deactivate() {
    // Cleanup if needed
}
