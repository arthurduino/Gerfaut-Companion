<?php
/**
 * Plugin Name: Gerfaut Companion
 * Plugin URI: https://gerfaut.mooo.com
 * Description: Extension compagnon pour afficher des informations sur le dashboard WordPress et la liste des commandes WooCommerce
 * Version: 1.0.0
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
define('GERFAUT_COMPANION_VERSION', '1.0.0');
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
    
    // Token d'accès GitHub (stocké dans un fichier sécurisé)
    $tokenFile = GERFAUT_COMPANION_PLUGIN_DIR . '.github-token';
    if (file_exists($tokenFile)) {
        $token = trim(file_get_contents($tokenFile));
        if (!empty($token)) {
            $updateChecker->setAuthentication($token);
        }
    }
}

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
