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

        add_action('woocommerce_add_cart_item_data', array($this, 'add_sticker_data_to_cart_item'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_sticker_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_sticker_order_item_meta'), 10, 4);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_sticker_order_meta'));

        add_action('wp_ajax_gerfaut_add_sticker_to_cart', array($this, 'ajax_add_sticker_to_cart'));
        add_action('wp_ajax_nopriv_gerfaut_add_sticker_to_cart', array($this, 'ajax_add_sticker_to_cart'));
        add_action('wp_ajax_gerfaut_sticker_upload_image', array($this, 'ajax_upload_sticker_image'));
        add_action('wp_ajax_nopriv_gerfaut_sticker_upload_image', array($this, 'ajax_upload_sticker_image'));
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
        register_setting('gerfaut_sticker_settings', 'gerfaut_sticker_quantities');
    }

    public function render_settings_page() {
        $price_per_mm = get_option('gerfaut_sticker_price_per_mm', '0.50');
        $quantities = get_option('gerfaut_sticker_quantities', array(
            array('quantity' => 100, 'discount' => 0),
            array('quantity' => 200, 'discount' => 5),
            array('quantity' => 300, 'discount' => 10),
            array('quantity' => 500, 'discount' => 15),
            array('quantity' => 1000, 'discount' => 20),
        ));

        if (!is_array($quantities)) {
            $quantities = array();
        }

        ?>
        <div class="wrap">
            <h1>Paramètres stickers Gerfaut</h1>
            <form method="post" action="options.php">
                <?php settings_fields('gerfaut_sticker_settings'); ?>
                <?php do_settings_sections('gerfaut_sticker_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="gerfaut_sticker_price_per_mm">Prix par mm</label></th>
                        <td><input type="text" name="gerfaut_sticker_price_per_mm" id="gerfaut_sticker_price_per_mm" value="<?php echo esc_attr($price_per_mm); ?>" class="regular-text" /> €</td>
                    </tr>
                    <tr>
                        <th scope="row">Quantités prédéfinies</th>
                        <td>
                            <table class="widefat">
                                <thead><tr><th>Quantité</th><th>Réduction (%)</th></tr></thead>
                                <tbody id="gerfaut-sticker-quantities-body">
                                <?php foreach ($quantities as $qty) : ?>
                                    <tr>
                                        <td><input type="number" name="gerfaut_sticker_quantities[][quantity]" value="<?php echo esc_attr($qty['quantity']); ?>" min="1" required /></td>
                                        <td><input type="number" name="gerfaut_sticker_quantities[][discount]" value="<?php echo esc_attr($qty['discount']); ?>" min="0" max="100" required /></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                            <p><button type="button" class="button" id="gerfaut-add-quantity">Ajouter option</button></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        (function($) {
            $('#gerfaut-add-quantity').on('click', function() {
                $('#gerfaut-sticker-quantities-body').append('<tr><td><input type="number" name="gerfaut_sticker_quantities[][quantity]" value="100" min="1" required /></td><td><input type="number" name="gerfaut_sticker_quantities[][discount]" value="0" min="0" max="100" required /></td></tr>');
            });
        })(jQuery);
        </script>
        <?php
    }

    public function add_sticker_data_to_cart_item($cart_item_data, $product_id, $variation_id) {
        if (!empty($_POST['sticker_data']) && is_array($_POST['sticker_data'])) {
            $sticker = array_map('sanitize_text_field', wp_unslash($_POST['sticker_data']));
            $stickerData = array(
                'image_url'   => esc_url_raw($sticker['image_url'] ?? ''),
                'orientation' => in_array($sticker['orientation'] ?? 'portrait', array('portrait','landscape')) ? $sticker['orientation'] : 'portrait',
                'dimen'       => max(10, floatval($sticker['dimen'] ?? 62)),
                'quantity'    => intval($sticker['quantity'] ?? 1),
                'threshold'   => min(255, max(0, intval($sticker['threshold'] ?? 128))),
            );

            if ($stickerData['orientation'] === 'portrait') {
                $stickerData['width'] = 62;
                $stickerData['height'] = $stickerData['dimen'];
            } else {
                $stickerData['height'] = 62;
                $stickerData['width'] = $stickerData['dimen'];
            }

            $allowedQuantities = wp_list_pluck(get_option('gerfaut_sticker_quantities', array()), 'quantity');
            if (!in_array($stickerData['quantity'], $allowedQuantities, true)) {
                $stickerData['quantity'] = intval($allowedQuantities ? $allowedQuantities[0] : 1);
            }

            $cart_item_data['gerfaut_sticker_data'] = $stickerData;
            $cart_item_data['unique_key'] = md5(microtime() . rand(0, 999999));
        }

        return $cart_item_data;
    }

    public function display_sticker_cart_item_data($item_data, $cart_item) {
        if (!empty($cart_item['gerfaut_sticker_data'])) {
            $data = $cart_item['gerfaut_sticker_data'];
            $item_data[] = array('key' => 'Sticker', 'value' => sprintf('Dimensions %s x %s mm, Qté %s', esc_html($data['width']), esc_html($data['height']), esc_html($data['quantity'])));
            $item_data[] = array('key' => 'Seuil noir', 'value' => esc_html($data['threshold']));
        }
        return $item_data;
    }

    public function save_sticker_order_item_meta($item, $cart_item_key, $values, $order) {
        if (!empty($values['gerfaut_sticker_data'])) {
            $item->add_meta_data('_gerfaut_sticker_data', wp_json_encode($values['gerfaut_sticker_data']), true);
        }
    }

    public function save_sticker_order_meta($order_id) {
        $sticker_items = array();
        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $cart_item) {
                if (!empty($cart_item['gerfaut_sticker_data'])) {
                    $sticker_items[] = $cart_item['gerfaut_sticker_data'];
                }
            }
        }

        if ($sticker_items) {
            update_post_meta($order_id, '_gerfaut_sticker_items', wp_json_encode($sticker_items));
        }
    }

    public function ajax_add_sticker_to_cart() {
        $product_id = intval($_POST['product_id'] ?? 0);
        $sticker_data = $_POST['sticker_data'] ?? array();

        if (!$product_id || empty($sticker_data['image_url'])) {
            wp_send_json_error('Produit ou image invalide');
        }

        $_POST['sticker_data'] = $sticker_data;
        $result = $this->add_sticker_data_to_cart_item(array(), $product_id, 0);

        if (WC()->cart->add_to_cart($product_id, intval($sticker_data['quantity']), 0, array(), array('gerfaut_sticker_data' => $result['gerfaut_sticker_data'], 'unique_key' => $result['unique_key']))) {
            wp_send_json_success(array('redirect' => wc_get_cart_url()));
        }

        wp_send_json_error('Ajout au panier échoué');
    }

    public function ajax_upload_sticker_image() {
        if (empty($_FILES['sticker_image'])) {
            wp_send_json_error('Aucun fichier');
        }

        $file = $_FILES['sticker_image'];
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $upload = wp_handle_upload($file, array('test_form' => false));
        if (isset($upload['error'])) {
            wp_send_json_error($upload['error']);
        }

        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title'     => sanitize_file_name($file['name']),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $upload['file']);

        if (!is_wp_error($attach_id)) {
            $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);
            wp_send_json_success(array('url' => wp_get_attachment_url($attach_id)));
        }

        wp_send_json_error('Impossible d’insérer l’image');
    }
}

new Gerfaut_Companion_Sticker_Builder();
