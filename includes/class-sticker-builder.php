<?php
/**
 * Sticker Builder extensions for Gerfaut companion.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Companion_Sticker_Builder {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Cart & order hooks
        add_action('woocommerce_add_cart_item_data', array($this, 'add_sticker_data_to_cart_item'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_sticker_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_sticker_order_item_meta'), 10, 4);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_sticker_order_meta'));

        // AJAX for adding sticker to cart
        add_action('wp_ajax_gerfaut_add_sticker_to_cart', array($this, 'ajax_add_sticker_to_cart'));
        add_action('wp_ajax_nopriv_gerfaut_add_sticker_to_cart', array($this, 'ajax_add_sticker_to_cart'));

        // Push sticker order to Laravel after paiement commando
        add_action('woocommerce_order_status_completed', array($this, 'push_sticker_order_to_laravel'), 10, 1);
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Stickers Gerfaut',
            'Stickers Gerfaut',
            'manage_options',
            'gerfaut-sticker-settings',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('gerfaut_sticker_settings', 'gerfaut_sticker_price_per_mm');
        register_setting('gerfaut_sticker_settings', 'gerfaut_sticker_quantity_tiers');
        register_setting('gerfaut_sticker_settings', 'gerfaut_companion_laravel_endpoint');
        register_setting('gerfaut_sticker_settings', 'gerfaut_companion_laravel_key');
    }

    public function render_settings_page() {
        $price_per_mm = get_option('gerfaut_sticker_price_per_mm', '0.50');
        $tiers = get_option('gerfaut_sticker_quantity_tiers', array());
        if (!is_array($tiers)) {
            $tiers = array();
        }
        ?>
        <div class="wrap">
            <h1>Paramètres de stickers Gerfaut</h1>
            <form method="post" action="options.php">
                <?php settings_fields('gerfaut_sticker_settings'); ?>
                <?php do_settings_sections('gerfaut_sticker_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="gerfaut_sticker_price_per_mm">Prix par mm</label></th>
                        <td><input type="text" name="gerfaut_sticker_price_per_mm" id="gerfaut_sticker_price_per_mm" value="<?php echo esc_attr($price_per_mm); ?>" class="regular-text" /> €</td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gerfaut_companion_laravel_endpoint">Endpoint Laravel</label></th>
                        <td><input type="text" name="gerfaut_companion_laravel_endpoint" id="gerfaut_companion_laravel_endpoint" value="<?php echo esc_attr(get_option('gerfaut_companion_laravel_endpoint', 'https://manager.gerfaut.ovh/printer/sticker-order')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="gerfaut_companion_laravel_key">Clef API Gerfaut</label></th>
                        <td><input type="text" name="gerfaut_companion_laravel_key" id="gerfaut_companion_laravel_key" value="<?php echo esc_attr(get_option('gerfaut_companion_laravel_key', '')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Tranches quantité / remise (%)</th>
                        <td>
                            <table class="widefat">
                                <thead><tr><th>Min</th><th>Max</th><th>Réduction (%)</th></tr></thead>
                                <tbody id="gerfaut-sticker-tiers-body">
                                <?php foreach ($tiers as $tier) : ?>
                                    <tr>
                                        <td><input type="number" name="gerfaut_sticker_quantity_tiers[][min]" value="<?php echo esc_attr($tier['min']); ?>" min="1" /></td>
                                        <td><input type="number" name="gerfaut_sticker_quantity_tiers[][max]" value="<?php echo esc_attr($tier['max']); ?>" min="1" /></td>
                                        <td><input type="number" name="gerfaut_sticker_quantity_tiers[][discount]" value="<?php echo esc_attr($tier['discount']); ?>" min="0" max="100" /></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p><button type="button" class="button" id="gerfaut-add-tier">Ajouter une tranche</button></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        (function($){
            $('#gerfaut-add-tier').on('click', function() {
                $('#gerfaut-sticker-tiers-body').append('<tr><td><input type="number" name="gerfaut_sticker_quantity_tiers[][min]" value="1" min="1" /></td><td><input type="number" name="gerfaut_sticker_quantity_tiers[][max]" value="10" min="1" /></td><td><input type="number" name="gerfaut_sticker_quantity_tiers[][discount]" value="0" min="0" max="100" /></td></tr>');
            });
        })(jQuery);
        </script>
        <?php
    }

    public function add_sticker_data_to_cart_item($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['gerfaut_sticker_data'])) {
            $cart_item_data['gerfaut_sticker_data'] = wc_clean($_POST['gerfaut_sticker_data']);
            // Unique key to force separate line items
            $cart_item_data['unique_key'] = md5(microtime() . rand());
        }
        return $cart_item_data;
    }

    public function display_sticker_cart_item_data($item_data, $cart_item) {
        if (!empty($cart_item['gerfaut_sticker_data']) && is_array($cart_item['gerfaut_sticker_data'])) {
            $data = $cart_item['gerfaut_sticker_data'];
            $item_data[] = array('key' => 'Sticker image', 'value' => esc_html($data['image_url'] ?? ''));            
            $item_data[] = array('key' => 'Dimensions', 'value' => esc_html(($data['width'] ?? '') . 'x' . ($data['height'] ?? '') . 'mm'));
            $item_data[] = array('key' => 'Orientation', 'value' => esc_html($data['orientation'] ?? 'portrait'));
            $item_data[] = array('key' => 'Seuil noir', 'value' => esc_html($data['threshold'] ?? 128));
        }

        return $item_data;
    }

    public function save_sticker_order_item_meta($item, $cart_item_key, $values, $order) {
        if (!empty($values['gerfaut_sticker_data'])) {
            $sticker_data = $values['gerfaut_sticker_data'];
            if (is_array($sticker_data)) {
                $item->add_meta_data('_gerfaut_sticker_data', wp_json_encode($sticker_data), true);
            }
        }
    }

    public function save_sticker_order_meta($order_id) {
        if (!WC()->cart) {
            return;
        }

        $sticker_items = array();
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!empty($cart_item['gerfaut_sticker_data'])) {
                $sticker_items[] = $cart_item['gerfaut_sticker_data'];
            }
        }

        if (!empty($sticker_items)) {
            update_post_meta($order_id, '_gerfaut_sticker_items', wp_json_encode($sticker_items));
        }
    }

    public function push_sticker_order_to_laravel($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $sticker_items_meta = get_post_meta($order_id, '_gerfaut_sticker_items', true);
        $sticker_items = array();

        if (!empty($sticker_items_meta)) {
            $sticker_items = json_decode($sticker_items_meta, true);
        }

        if (empty($sticker_items)) {
            return;
        }

        $api_url = get_option('gerfaut_companion_laravel_endpoint', 'https://manager.gerfaut.ovh/printer/sticker-order');
        $api_key = get_option('gerfaut_companion_laravel_key', '');

        $payload = array(
            'order_id' => $order_id,
            'order_number' => $order->get_order_number(),
            'customer_email' => $order->get_billing_email(),
            'sticker_items' => $sticker_items,
            'total' => $order->get_total(),
        );

        $args = array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-Gerfaut-API-Key' => $api_key,
            ),
            'body' => wp_json_encode($payload),
            'timeout' => 30,
        );

        $response = wp_remote_post($api_url, $args);

        if (is_wp_error($response)) {
            update_post_meta($order_id, '_gerfaut_sticker_sync_status', 'error:' . $response->get_error_message());
            return;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code >= 200 && $code < 300) {
            update_post_meta($order_id, '_gerfaut_sticker_sync_status', 'sent');
        } else {
            update_post_meta($order_id, '_gerfaut_sticker_sync_status', 'error:' . $code);
        }
    }

    public function ajax_add_sticker_to_cart() {
        if (!isset($_POST['product_id']) || !isset($_POST['sticker_data'])) {
            wp_send_json_error('Données manquantes');
        }

        $product_id = intval($_POST['product_id']);
        $sticker_data = $_POST['sticker_data'];

        // Cleanup
        $sticker_data = array(
            'image_url' => esc_url_raw($sticker_data['image_url'] ?? ''),
            'width' => floatval($sticker_data['width'] ?? 62),
            'height' => floatval($sticker_data['height'] ?? 62),
            'orientation' => sanitize_text_field($sticker_data['orientation'] ?? 'portrait'),
            'quantity' => intval($sticker_data['quantity'] ?: 1),
            'threshold' => intval($sticker_data['threshold'] ?? 128),
        );

        if ($sticker_data['quantity'] < 1) {
            $sticker_data['quantity'] = 1;
        }

        $cart_item_data = array('gerfaut_sticker_data' => $sticker_data);

        $added = WC()->cart->add_to_cart($product_id, $sticker_data['quantity'], 0, array(), $cart_item_data);

        if (!$added) {
            wp_send_json_error('Impossible d’ajouter au panier');
        }

        wp_send_json_success(array('redirect' => wc_get_cart_url()));
    }
}

new Gerfaut_Companion_Sticker_Builder();
