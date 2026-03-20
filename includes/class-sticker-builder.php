<?php
/**
 * Sticker Builder extensions for Gerfaut companion.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Companion_Sticker_Builder {
    const STICKER_PRODUCT_SKU = 'gerfaut-companion-sticker-product';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_assets'));

        add_action('woocommerce_add_cart_item_data', array($this, 'add_sticker_data_to_cart_item'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_sticker_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_sticker_order_item_meta'), 10, 4);
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_sticker_order_meta'));
        add_action('woocommerce_before_calculate_totals', array($this,'apply_sticker_cart_price'), 20, 1);
        add_filter('woocommerce_cart_item_quantity', array($this, 'filter_sticker_cart_quantity'), 10, 3);

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

    public function admin_enqueue_assets() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'woocommerce_page_gerfaut-sticker-settings') {
            wp_enqueue_media();
            wp_enqueue_script('gerfaut-sticker-admin', GERFAUT_COMPANION_PLUGIN_URL . 'assets/js/sticker-builder-admin.js', array('jquery'), GERFAUT_COMPANION_VERSION, true);
        }
    }

    public function register_settings() {
        register_setting('gerfaut_sticker_settings', 'gerfaut_sticker_price_per_mm', array(
            'sanitize_callback' => array($this, 'sanitize_sticker_price_per_mm'),
            'default' => '0.50',
        ));
        register_setting('gerfaut_sticker_settings', 'gerfaut_sticker_quantities', array(
            'sanitize_callback' => array($this, 'sanitize_sticker_quantities'),
            'default' => array(
                array('quantity' => 100, 'discount' => 0),
                array('quantity' => 200, 'discount' => 5),
                array('quantity' => 300, 'discount' => 10),
                array('quantity' => 500, 'discount' => 15),
                array('quantity' => 1000, 'discount' => 20),
            ),
        ));
        register_setting('gerfaut_sticker_settings', 'gerfaut_sticker_preview_bg_url', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => '',
        ));
    }

    public function sanitize_sticker_quantities($quantities) {
        if (!is_array($quantities)) {
            return array();
        }

        // Convert both styles: 
        // - structured rows: [0=>['quantity'=>100,'discount'=>0], ...]
        // - alternating rows: [0=>['quantity'=>100],1=>['discount'=>0],...] (ancien format issue)
        $normalized = array();

        foreach ($quantities as $row) {
            if (!is_array($row)) {
                continue;
            }

            $quantity = isset($row['quantity']) ? intval($row['quantity']) : null;
            $discount = isset($row['discount']) ? floatval($row['discount']) : null;

            if ($quantity !== null && $discount !== null) {
                // Complete row
                if ($quantity < 1) {
                    continue;
                }
                $discount = max(0, min(100, $discount));
                $normalized[] = array('quantity' => $quantity, 'discount' => $discount);
                continue;
            }

            // Partial row handling for legacy format
            if ($quantity !== null) {
                $normalized[] = array('quantity' => $quantity, 'discount' => 0);
                continue;
            }

            if ($discount !== null) {
                $lastIndex = count($normalized) - 1;
                if ($lastIndex >= 0 && isset($normalized[$lastIndex]['quantity']) && !isset($normalized[$lastIndex]['discount'])) {
                    // Attach discount to previous quantity
                    $normalized[$lastIndex]['discount'] = max(0, min(100, $discount));
                } else {
                    // Or start a new row with default quantity if no prior quantity exists
                    $normalized[] = array('quantity' => 1, 'discount' => max(0, min(100, $discount)));
                }
            }
        }

        // Filter and normalize values again
        $sanitized = array();
        foreach ($normalized as $row) {
            $quantity = isset($row['quantity']) ? intval($row['quantity']) : 0;
            $discount = isset($row['discount']) ? floatval($row['discount']) : 0;
            if ($quantity < 1) {
                continue;
            }
            $discount = max(0, min(100, $discount));
            $sanitized[] = array('quantity' => $quantity, 'discount' => $discount);
        }

        return $this->sort_sticker_quantities($sanitized);
    }

    public function sort_sticker_quantities($quantities) {
        if (!is_array($quantities)) {
            return array();
        }

        usort($quantities, function($a, $b) {
            $qa = isset($a['quantity']) ? intval($a['quantity']) : 0;
            $qb = isset($b['quantity']) ? intval($b['quantity']) : 0;
            if ($qa === $qb) {
                return 0;
            }
            return ($qa < $qb) ? -1 : 1;
        });

        return $quantities;
    }

    public function sanitize_sticker_price_per_mm($value) {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        $number = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($number === false || $number < 0) {
            return 0.0;
        }

        // Conserver précision 9 décimales
        $formatted = number_format($number, 9, '.', '');
        return floatval($formatted);
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

        // Formater les données pour affichage et corriger format legacy (un item par champ)
        $quantities = $this->sanitize_sticker_quantities($quantities);

        if (empty($quantities)) {
            $quantities = array(
                array('quantity' => 100, 'discount' => 0),
                array('quantity' => 200, 'discount' => 5),
                array('quantity' => 300, 'discount' => 10),
                array('quantity' => 500, 'discount' => 15),
                array('quantity' => 1000, 'discount' => 20),
            );
        }

        $quantities = $this->sort_sticker_quantities($quantities);
        $previewBg = get_option('gerfaut_sticker_preview_bg_url', '');

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
                        <th scope="row"><label for="gerfaut_sticker_preview_bg_url">Image de fond preview</label></th>
                        <td>
                            <input type="text" id="gerfaut_sticker_preview_bg_url" name="gerfaut_sticker_preview_bg_url" value="<?php echo esc_attr($previewBg); ?>" class="regular-text" placeholder="URL de l\'image" />
                            <button type="button" class="button" id="gerfaut_sticker_preview_bg_button">Choisir dans la bibliothèque</button>
                            <div id="gerfaut_sticker_preview_bg_preview" style="margin-top:10px; max-width:360px; height:120px; background-size:cover; background-position:center; border:1px solid #d1d5db; border-radius:8px; background-image: url('<?php echo esc_url($previewBg); ?>');"></div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Quantités prédéfinies</th>
                        <td>
                            <table class="widefat gerfaut-sticker-quantities-table">
                                <thead><tr><th style="width: 30%;">Quantité</th><th style="width: 30%;">Réduction (%)</th><th style="width: 40%;">Action</th></tr></thead>
                                <tbody id="gerfaut-sticker-quantities-body">
                                <?php foreach ($quantities as $index => $qty) : ?>
                                    <tr class="gerfaut-sticker-quantity-row">
                                        <td><input type="number" name="gerfaut_sticker_quantities[<?php echo intval($index); ?>][quantity]" value="<?php echo esc_attr($qty['quantity']); ?>" min="1" required /></td>
                                        <td><input type="number" name="gerfaut_sticker_quantities[<?php echo intval($index); ?>][discount]" value="<?php echo esc_attr($qty['discount']); ?>" min="0" max="100" required /></td>
                                        <td><button type="button" class="button gerfaut-remove-quantity">Supprimer</button></td>
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
            function getNextIndex() {
                return $('#gerfaut-sticker-quantities-body tr').length;
            }

            $('#gerfaut-add-quantity').on('click', function() {
                var i = getNextIndex();
                var row = '<tr class="gerfaut-sticker-quantity-row">'
                    + '<td><input type="number" name="gerfaut_sticker_quantities[' + i + '][quantity]" value="100" min="1" required /></td>'
                    + '<td><input type="number" name="gerfaut_sticker_quantities[' + i + '][discount]" value="0" min="0" max="100" required /></td>'
                    + '<td><button type="button" class="button gerfaut-remove-quantity">Supprimer</button></td>'
                    + '</tr>';
                $('#gerfaut-sticker-quantities-body').append(row);
            });

            $('#gerfaut-sticker-quantities-body').on('click', '.gerfaut-remove-quantity', function() {
                $(this).closest('tr').remove();
                // Re-index rows to keep the structure propre
                $('#gerfaut-sticker-quantities-body tr').each(function(index) {
                    $(this).find('input[name*="[quantity]"]').attr('name', 'gerfaut_sticker_quantities[' + index + '][quantity]');
                    $(this).find('input[name*="[discount]"]').attr('name', 'gerfaut_sticker_quantities[' + index + '][discount]');
                });
            });

            // WP media chooser pour l'image de fond preview
            $('#gerfaut_sticker_preview_bg_button').on('click', function(e) {
                e.preventDefault();
                var frame = wp.media({
                    title: 'Choisir une image de fond',
                    button: { text: 'Utiliser cette image' },
                    multiple: false
                });

                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#gerfaut_sticker_preview_bg_url').val(attachment.url);
                    $('#gerfaut_sticker_preview_bg_preview').css('background-image', 'url("' + attachment.url + '")');
                });

                frame.open();
            });
        })(jQuery);
        </script>
        <?php
    }

    public function add_sticker_data_to_cart_item($cart_item_data, $product_id, $variation_id) {
        $sticker_source = array();

        if (!empty($_POST['sticker_data']) && is_array($_POST['sticker_data'])) {
            $sticker_source = array_map('sanitize_text_field', wp_unslash($_POST['sticker_data']));
        } elseif (!empty($cart_item_data['gerfaut_sticker_data']) && is_array($cart_item_data['gerfaut_sticker_data'])) {
            $sticker_source = $cart_item_data['gerfaut_sticker_data'];
        }

        if (!empty($sticker_source)) {
            $sticker = $sticker_source;
            $stickerData = array(
                'image_url'   => esc_url_raw($sticker['image_url'] ?? ''),
                'orientation' => in_array($sticker['orientation'] ?? 'portrait', array('portrait','landscape')) ? $sticker['orientation'] : 'portrait',
                'dimen'       => max(10, floatval($sticker['dimen'] ?? 62)),
                'quantity'    => intval($sticker['quantity'] ?? 1),
                'threshold'   => min(255, max(0, intval($sticker['threshold'] ?? 128))),
            );

            // Prefer explicit width/height from front-end computed data
            $postedWidth = isset($sticker['width']) ? max(1, floatval($sticker['width'])) : 0;
            $postedHeight = isset($sticker['height']) ? max(1, floatval($sticker['height'])) : 0;
            if ($postedWidth && $postedHeight) {
                $stickerData['width'] = $postedWidth;
                $stickerData['height'] = $postedHeight;
            } else {
                if ($stickerData['orientation'] === 'portrait') {
                    $stickerData['width'] = 62;
                    $stickerData['height'] = $stickerData['dimen'];
                } else {
                    $stickerData['height'] = 62;
                    $stickerData['width'] = $stickerData['dimen'];
                }
            }

            $allowedQuantities = wp_list_pluck(get_option('gerfaut_sticker_quantities', array()), 'quantity');
            $quantitiesMeta = get_option('gerfaut_sticker_quantities', array());
            $discount = 0;
            foreach ($quantitiesMeta as $config) {
                if (isset($config['quantity']) && intval($config['quantity']) === $stickerData['quantity']) {
                    $discount = floatval($config['discount'] ?? 0);
                    break;
                }
            }
            $stickerData['discount'] = $discount;

            if (!in_array($stickerData['quantity'], $allowedQuantities, true)) {
                $stickerData['quantity'] = intval($allowedQuantities ? $allowedQuantities[0] : 1);
            }

            // Recalc discount after normalization quatity
            $discount = 0;
            foreach ($quantitiesMeta as $config) {
                if (isset($config['quantity']) && intval($config['quantity']) === $stickerData['quantity']) {
                    $discount = floatval($config['discount'] ?? 0);
                    break;
                }
            }
            $stickerData['discount'] = $discount;

            $pricePerMm = floatval(get_option('gerfaut_sticker_price_per_mm', 0.50));
            $surface = $stickerData['width'] * $stickerData['height'];

            $postedUnitPrice = isset($sticker['unit_price']) ? floatval($sticker['unit_price']) : 0;
            $postedTotalPrice = isset($sticker['total_price']) ? floatval($sticker['total_price']) : 0;

            if ($postedUnitPrice > 0 && $postedTotalPrice > 0) {
                $unitPrice = round($postedUnitPrice, 4);
                $totalPrice = round($postedTotalPrice, 4);
            } else {
                $unitPrice = round($surface * $pricePerMm * (1 - $discount / 100), 4);
                $totalPrice = round($unitPrice * $stickerData['quantity'], 4);
            }

            $stickerData['price_per_mm'] = $pricePerMm;
            $stickerData['unit_price'] = $unitPrice;
            $stickerData['total_price'] = $totalPrice;

            $cart_item_data['gerfaut_sticker_data'] = $stickerData;
            $cart_item_data['unique_key'] = md5(microtime() . rand(0, 999999));
        }

        return $cart_item_data;
    }

    public function display_sticker_cart_item_data($item_data, $cart_item) {
        if (!empty($cart_item['gerfaut_sticker_data'])) {
            $data = $cart_item['gerfaut_sticker_data'];
            $item_data[] = array(
                'key' => 'Sticker',
                'value' => sprintf('Dimensions %s x %s mm, Qté %s', esc_html($data['width']), esc_html($data['height']), esc_html($data['quantity']))
            );
            $item_data[] = array('key' => 'Seuil noir', 'value' => esc_html($data['threshold']));
            if (!empty($data['unit_price'])) {
                $item_data[] = array('key' => 'Prix unitaire', 'value' => wc_price($data['unit_price']));
            }
            if (!empty($data['total_price'])) {
                $item_data[] = array('key' => 'Prix total', 'value' => wc_price($data['total_price']));
            }
        }
        return $item_data;
    }

    public function apply_sticker_cart_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (!empty($cart_item['gerfaut_sticker_data'])) {
                $total_price = floatval($cart_item['gerfaut_sticker_data']['total_price'] ?? 0);
                $real_quantity = max(1, intval($cart_item['gerfaut_sticker_data']['real_quantity'] ?? $cart_item['gerfaut_sticker_data']['quantity'] ?? 1));

                if ($total_price <= 0 && !empty($cart_item['gerfaut_sticker_data']['unit_price'])) {
                    $total_price = floatval($cart_item['gerfaut_sticker_data']['unit_price']) * $real_quantity;
                }

                if ($total_price > 0) {
                    $cart_item['data']->set_price($total_price);
                    $cart_item['data']->set_regular_price($total_price);
                    $cart_item['data']->set_sale_price('');
                }
            }
        }
    }

    public function filter_sticker_cart_quantity($product_quantity, $cart_item_key, $cart_item) {
        if (!empty($cart_item['gerfaut_sticker_data'])) {
            $qty = intval($cart_item['quantity']);
            if ($qty < 1) {
                $qty = 1;
            }
            return '<span class="gerfaut-sticker-qty">' . esc_html($qty) . '</span>' . '<input type="hidden" name="cart[' . esc_attr($cart_item_key) . '][qty]" value="' . esc_attr($qty) . '" />';
        }

        return $product_quantity;
    }

    public function save_sticker_order_item_meta($item, $cart_item_key, $values, $order) {
        if (!empty($values['gerfaut_sticker_data'])) {
            $item->add_meta_data('_gerfaut_sticker_data', wp_json_encode($values['gerfaut_sticker_data']), true);
            $real_quantity = intval($values['gerfaut_sticker_data']['real_quantity'] ?? $values['gerfaut_sticker_data']['quantity'] ?? 1);
            $item->add_meta_data('_gerfaut_sticker_real_quantity', $real_quantity, true);
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
        $sticker_data = is_array($_POST['sticker_data'] ?? null) ? $_POST['sticker_data'] : array();

        if (!$product_id) {
            $product_id = self::get_sticker_product_id();
        }

        if (!$product_id) {
            wp_send_json_error('Produit invalide (product_id manquant).');
        }

        if (empty($sticker_data['image_url'])) {
            wp_send_json_error('Image du sticker manquante.');
        }

        $_POST['sticker_data'] = $sticker_data;
        $result = $this->add_sticker_data_to_cart_item(array(), $product_id, 0);

        if (!isset($result['gerfaut_sticker_data'])) {
            wp_send_json_error('Impossible de construire les données du sticker.');
        }

        $sticker_product_id = self::create_sticker_product($result['gerfaut_sticker_data']);
        if (!$sticker_product_id) {
            wp_send_json_error('Impossible de créer le produit sticker.');
        }

        $real_quantity = max(1, intval($result['gerfaut_sticker_data']['quantity'] ?? $sticker_data['quantity'] ?? 1));
        $result['gerfaut_sticker_data']['real_quantity'] = $real_quantity;

        // Force cart line quantity to 1, keep real quantity in sticker metadata
        $cart_quantity = 1;

        if (WC()->cart->add_to_cart($sticker_product_id, $cart_quantity, 0, array(), array('gerfaut_sticker_data' => $result['gerfaut_sticker_data'], 'unique_key' => $result['unique_key']))) {
            wp_send_json_success(array('redirect' => wc_get_cart_url()));
        }

        wp_send_json_error('Ajout au panier échoué');
    }

    public static function create_sticker_product($sticker_data) {
        if (empty($sticker_data) || !is_array($sticker_data)) {
            return 0;
        }

        $sku = 'gerfaut-sticker-' . time() . '-' . wp_generate_uuid4();
        if (wc_get_product_id_by_sku($sku)) {
            $sku .= '-' . wp_rand(1000, 9999);
        }

        $real_quantity = max(1, intval($sticker_data['quantity'] ?? 1));
        $total_price = floatval($sticker_data['total_price'] ?? 0);

        $product_name = sprintf(
            'Stickers personnalisés x%s',
            esc_html($real_quantity)
        );

        $product = new WC_Product_Simple();
        $product->set_name($product_name);
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_price($total_price);
        $product->set_regular_price($total_price);
        $product->set_sale_price('');
        $product->set_sku($sku);
        $product->set_manage_stock(false);
        $product->set_stock_status('instock');

        if (!empty($sticker_data['image_url'])) {
            $attachment_id = attachment_url_to_postid($sticker_data['image_url']);
            if ($attachment_id) {
                $product->set_image_id($attachment_id);
            }
        }

        $product_id = $product->save();

        if ($product_id) {
            update_post_meta($product_id, '_gerfaut_sticker_product', '1');
            update_post_meta($product_id, '_gerfaut_sticker_data', wp_json_encode($sticker_data));
            update_post_meta($product_id, '_gerfaut_sticker_created', current_time('mysql'));

            // Specific sticker metadata for tracking
            update_post_meta($product_id, '_gerfaut_sticker_orientation', sanitize_text_field($sticker_data['orientation'] ?? '')); 
            update_post_meta($product_id, '_gerfaut_sticker_width', floatval($sticker_data['width'] ?? 0));
            update_post_meta($product_id, '_gerfaut_sticker_height', floatval($sticker_data['height'] ?? 0));
            update_post_meta($product_id, '_gerfaut_sticker_quantity', intval($sticker_data['quantity'] ?? 1));
            update_post_meta($product_id, '_gerfaut_sticker_threshold', intval($sticker_data['threshold'] ?? 0));
            update_post_meta($product_id, '_gerfaut_sticker_discount', floatval($sticker_data['discount'] ?? 0));
            update_post_meta($product_id, '_gerfaut_sticker_unit_price', floatval($sticker_data['unit_price'] ?? 0));
            update_post_meta($product_id, '_gerfaut_sticker_total_price', floatval($sticker_data['total_price'] ?? 0));

            // Non-indexable tags for common SEO plugins
            update_post_meta($product_id, '_yoast_wpseo_meta-robots-noindex', '1');
            update_post_meta($product_id, '_aioseo_noindex', '1');

            // Make sure the product stays hidden
            $product_post = array(
                'ID' => $product_id,
                'post_status' => 'publish',
                'post_type' => 'product',
            );
            wp_update_post($product_post);
        }

        return $product_id ? $product_id : 0;
    }

    public static function get_sticker_product_id() {
        $sku = self::STICKER_PRODUCT_SKU;
        $existing_id = wc_get_product_id_by_sku($sku);

        if ($existing_id) {
            return $existing_id;
        }

        $product = new WC_Product_Simple();
        $product->set_name('Sticker personnalisé');
        $product->set_status('publish');
        $product->set_catalog_visibility('hidden');
        $product->set_price(1);
        $product->set_regular_price(1);
        $product->set_sku($sku);
        $product->set_manage_stock(false);
        $product_id = $product->save();

        if ($product_id) {
            return $product_id;
        }

        return 0;
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
            update_post_meta($attach_id, '_gerfaut_sticker_uploaded', '1');
            wp_send_json_success(array('url' => wp_get_attachment_url($attach_id)));
        }

        wp_send_json_error('Impossible d’insérer l’image');
    }
}

new Gerfaut_Companion_Sticker_Builder();
