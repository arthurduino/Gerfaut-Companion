<?php
/**
 * Plugin Name: Gerfaut Companion
 * Description: Extension compagnon pour afficher des informations sur le dashboard WordPress et la liste des commandes WooCommerce. Inclut les shortcodes [gerfaut_sav], [gerfaut_contact], [gerfaut_sticker] pour intégrer les formulaires.
 * Version: 1.3.22
 * Author: Gerfaut
 */

define('GERFAUT_COMPANION_VERSION', '1.3.22');
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

    if (!wp_next_scheduled('gerfaut_companion_cleanup_unattached_sticker_images')) {
        wp_schedule_event(time(), 'daily', 'gerfaut_companion_cleanup_unattached_sticker_images');
    }
}
register_activation_hook(__FILE__, 'gerfaut_companion_activate');

function gerfaut_companion_deactivate() {
    // Cleanup scheduled event
    if (wp_next_scheduled('gerfaut_companion_cleanup_unattached_sticker_images')) {
        wp_clear_scheduled_hook('gerfaut_companion_cleanup_unattached_sticker_images');
    }
}
register_deactivation_hook(__FILE__, 'gerfaut_companion_deactivate');

function gerfaut_companion_cleanup_unattached_sticker_images() {
    global $wpdb;

    // Grab attachments created by sticker uploader
    $attachment_ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s",
            '_gerfaut_sticker_uploaded'
        )
    );

    if (empty($attachment_ids)) {
        return;
    }

    foreach ($attachment_ids as $attach_id) {
        $url = wp_get_attachment_url($attach_id);
        if (!$url) {
            continue;
        }

        $like = '%' . $wpdb->esc_like($url) . '%';

        $has_order_ref = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE (meta_key = %s OR meta_key = %s) AND meta_value LIKE %s",
            '_gerfaut_sticker_data', '_gerfaut_sticker_items', $like
        ));

        if ($has_order_ref) {
            continue;
        }

        $attachment_post = get_post($attach_id);
        if (!$attachment_post) {
            continue;
        }

        $attached_date = strtotime($attachment_post->post_date);
        if (!$attached_date) {
            continue;
        }

        if ($attached_date > strtotime('-24 hours')) {
            continue;
        }

        wp_delete_attachment($attach_id, true);
    }

    // Cleanup stale sticker products that were generated but never ordered.
    $stale_stickers = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_gerfaut_sticker_product' AND meta_value = '1') AND post_date < %s",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        )
    );

    if (!empty($stale_stickers)) {
        foreach ($stale_stickers as $sticker_product_id) {
            $ordered = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key = '_product_id' AND meta_value = %d",
                $sticker_product_id
            ));

            if (intval($ordered) === 0) {
                wp_delete_post($sticker_product_id, true);
            }
        }
    }
}

add_action('gerfaut_companion_cleanup_unattached_sticker_images', 'gerfaut_companion_cleanup_unattached_sticker_images');

