<?php
/**
 * Mondial Relay Shipping Method
 *
 * Adds a shipping method that shows a map for selecting a Mondial Relay relay/locker point.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Companion_Mondial_Relay_Shipping extends WC_Shipping_Method {

    /**
     * Constructor.
     */
    public function __construct($instance_id = 0) {
        $this->id                 = 'gerfaut_mondial_relay';
        $this->instance_id        = absint($instance_id);
        $this->method_title       = __('Mondial Relay (Gerfaut)', 'gerfaut-companion');
        $this->method_description = __('Permet au client de choisir un point relais / locker via une carte.', 'gerfaut-companion');
        $this->supports           = array(
            'shipping-zones',
            'settings',
            'instance-settings',
            'instance-settings-modal',
        );

        $this->init();
    }

    /**
     * Init settings and hooks.
     */
    public function init() {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        // Load options
        $this->enabled     = $this->get_option('enabled');
        $this->title       = $this->get_option('title', __('Mondial Relay', 'gerfaut-companion'));
        $this->cost        = $this->get_option('cost', '0');
        $this->enseigne    = $this->get_option('enseigne', '');
        $this->code_client = $this->get_option('code_client', '');
        $this->api_mode    = $this->get_option('api_mode', 'prod');

        // Save settings
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));

        // Frontend hooks
        add_action('woocommerce_after_shipping_rate', array($this, 'display_relay_selector'), 10, 2);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));

        // Checkout/order hooks
        add_action('woocommerce_checkout_update_order_meta', array($this, 'save_selected_relay'), 10, 2);
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'display_admin_order_meta'), 10, 1);

        // AJAX endpoints
        add_action('init', array($this, 'register_ajax_endpoints'));
    }

    /**
     * Register AJAX endpoints for retrieving/saving selected relay point.
     */
    public function register_ajax_endpoints() {
        // Retrieving point list
        add_action('wp_ajax_gerfaut_mondial_relay_get_points', array($this, 'ajax_get_points'));
        add_action('wp_ajax_nopriv_gerfaut_mondial_relay_get_points', array($this, 'ajax_get_points'));

        // Saving selected point to session
        add_action('wp_ajax_gerfaut_mondial_relay_save_point', array($this, 'ajax_save_point'));
        add_action('wp_ajax_nopriv_gerfaut_mondial_relay_save_point', array($this, 'ajax_save_point'));

        // Retrieve previously selected point from session
        add_action('wp_ajax_gerfaut_mondial_relay_get_selected_point', array($this, 'ajax_get_selected_point'));
        add_action('wp_ajax_nopriv_gerfaut_mondial_relay_get_selected_point', array($this, 'ajax_get_selected_point'));
    }

    /**
     * Define settings fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Activer', 'gerfaut-companion'),
                'type'        => 'checkbox',
                'label'       => __('Activer la méthode Mondial Relay', 'gerfaut-companion'),
                'default'     => 'no',
            ),
            'title' => array(
                'title'       => __('Titre', 'gerfaut-companion'),
                'type'        => 'text',
                'description' => __('Titre affiché au client durant le checkout.', 'gerfaut-companion'),
                'default'     => __('Mondial Relay', 'gerfaut-companion'),
            ),
            'cost' => array(
                'title'       => __('Coût', 'gerfaut-companion'),
                'type'        => 'price',
                'description' => __('Coût facturé pour cette méthode de livraison.', 'gerfaut-companion'),
                'default'     => '0',
            ),
            'enseigne' => array(
                'title'       => __('Enseigne (code)', 'gerfaut-companion'),
                'type'        => 'text',
                'description' => __('Code enseigne fourni par Mondial Relay (identifiant de votre compte).', 'gerfaut-companion'),
                'default'     => '',
            ),
            'code_client' => array(
                'title'       => __('Code client / mot de passe', 'gerfaut-companion'),
                'type'        => 'text',
                'description' => __('Code client ou mot de passe fourni par Mondial Relay.', 'gerfaut-companion'),
                'default'     => '',
            ),
            'api_mode' => array(
                'title'       => __('Mode API', 'gerfaut-companion'),
                'type'        => 'select',
                'options'     => array(
                    'prod' => __('Production', 'gerfaut-companion'),
                    'test' => __('Test', 'gerfaut-companion'),
                ),
                'description' => __('Choisir l\'environnement Mondial Relay (test ou production).', 'gerfaut-companion'),
                'default'     => 'prod',
            ),
        );
    }

    /**
     * Calculate shipping rate.
     */
    public function calculate_shipping($package = array()) {
        $rate = array(
            'id'       => $this->id,
            'label'    => $this->title,
            'cost'     => wc_format_decimal($this->cost),
            'calc_tax' => 'per_item',
        );

        $this->add_rate($rate);
    }

    /**
     * Output the relay picker UI below the shipping method.
     */
    public function display_relay_selector($method, $index) {
        if (!function_exists('is_checkout') || !is_checkout()) {
            // Only render on checkout.
            return;
        }

        if (empty($method) || empty($method->id) || $method->id !== $this->id) {
            return;
        }

        $selected_point = WC()->session ? WC()->session->get('gerfaut_mondial_relay_point') : null;
        $selected_json  = $selected_point ? json_encode($selected_point) : '';

        echo '<div class="gerfaut-mondial-relay-container" data-selected="' . esc_attr($selected_json) . '">
            <p class="gerfaut-mondial-relay-selected">
                ' . esc_html__('Aucun point relais sélectionné.', 'gerfaut-companion') . '
            </p>
            <button type="button" class="button gerfaut-mondial-relay-open-map">' . esc_html__('Choisir un point relais / locker', 'gerfaut-companion') . '</button>
            <input type="hidden" name="gerfaut_mondial_relay_point" class="gerfaut-mondial-relay-point" value="' . esc_attr($selected_json) . '" />
        </div>';
    }

    /**
     * Enqueue frontend assets for the checkout map.
     */
    public function enqueue_assets() {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        // Only enqueue if WooCommerce is active and this shipping method is available.
        if (!function_exists('WC') || !WC() || !WC()->shipping()) {
            return;
        }

        $found = false;
        $packages = WC()->cart->get_shipping_packages();
        foreach ($packages as $package) {
            foreach ($package['rates'] as $rate) {
                if (isset($rate->method_id) && $rate->method_id === $this->id) {
                    $found = true;
                    break 2;
                }
            }
        }

        if (!$found) {
            return;
        }

        // Leaflet (map) assets
        wp_enqueue_style('gerfaut-mondial-relay-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
        wp_enqueue_script('gerfaut-mondial-relay-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);

        wp_enqueue_style(
            'gerfaut-mondial-relay',
            GERFAUT_COMPANION_PLUGIN_URL . 'assets/css/mondial-relay.css',
            array(),
            GERFAUT_COMPANION_VERSION
        );

        wp_enqueue_script(
            'gerfaut-mondial-relay',
            GERFAUT_COMPANION_PLUGIN_URL . 'assets/js/mondial-relay-checkout.js',
            array('jquery', 'gerfaut-mondial-relay-leaflet'),
            GERFAUT_COMPANION_VERSION,
            true
        );

        wp_localize_script('gerfaut-mondial-relay', 'gerfautMondialRelay', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('gerfaut_mondial_relay_nonce'),
            'shippingMethodId' => $this->id,
        ));
    }

    /**
     * Save selected relay point to order meta.
     */
    public function save_selected_relay($order_id, $posted) {
        if (empty($_POST['gerfaut_mondial_relay_point'])) {
            return;
        }

        $value = wp_unslash($_POST['gerfaut_mondial_relay_point']);
        $value = trim($value);
        if (empty($value)) {
            return;
        }

        // Validate JSON
        $decoded = json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return;
        }

        update_post_meta($order_id, '_gerfaut_mondial_relay_point', $decoded);
    }

    /**
     * Display selected relay point in order admin.
     */
    public function display_admin_order_meta($order) {
        $point = get_post_meta($order->get_id(), '_gerfaut_mondial_relay_point', true);
        if (empty($point) || !is_array($point)) {
            return;
        }

        echo '<div class="order_data_column">';
        echo '<h4>' . esc_html__('Point relais Mondial Relay', 'gerfaut-companion') . '</h4>';
        echo '<p>' . esc_html($point['name'] ?? '') . '<br/>' . esc_html($point['address'] ?? '') . '<br/>' . esc_html($point['city'] ?? '') . ' ' . esc_html($point['postcode'] ?? '') . '</p>';
        echo '</div>';
    }

    /**
     * AJAX: Get points from Mondial Relay based on postcode / city.
     */
    public function ajax_get_points() {
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'gerfaut_mondial_relay_nonce')) {
            wp_send_json_error(__('Authentification invalide.', 'gerfaut-companion'), 403);
        }

        $postcode = sanitize_text_field($_REQUEST['postcode'] ?? '');
        $city     = sanitize_text_field($_REQUEST['city'] ?? '');

        if (empty($postcode) && empty($city)) {
            wp_send_json_error(__('Veuillez renseigner un code postal ou une ville.', 'gerfaut-companion'));
        }

        $points = $this->get_relay_points($postcode, $city);

        if (is_wp_error($points)) {
            wp_send_json_error($points->get_error_message());
        }

        wp_send_json_success($points);
    }

    /**
     * AJAX: Save the selected point into the customer session.
     */
    public function ajax_save_point() {
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'gerfaut_mondial_relay_nonce')) {
            wp_send_json_error(__('Authentification invalide.', 'gerfaut-companion'), 403);
        }

        $point = $_REQUEST['point'] ?? '';
        if (is_string($point)) {
            $point = json_decode(stripslashes($point), true);
        }

        if (empty($point) || !is_array($point)) {
            wp_send_json_error(__('Point relais invalide.', 'gerfaut-companion'));
        }

        if (WC()->session) {
            WC()->session->set('gerfaut_mondial_relay_point', $point);
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Return currently selected point from session.
     */
    public function ajax_get_selected_point() {
        if (!wp_verify_nonce($_REQUEST['nonce'] ?? '', 'gerfaut_mondial_relay_nonce')) {
            wp_send_json_error(__('Authentification invalide.', 'gerfaut-companion'), 403);
        }

        $point = WC()->session ? WC()->session->get('gerfaut_mondial_relay_point') : null;
        wp_send_json_success($point);
    }

    /**
     * Fetch relay points from Mondial Relay API.
     *
     * @param string $postcode
     * @param string $city
     * @param int    $limit
     * @return array|WP_Error
     */
    public function get_relay_points($postcode, $city, $limit = 15) {
        if (empty($this->enseigne) || empty($this->code_client)) {
            return new WP_Error('missing_credentials', __('Identifiants Mondial Relay manquants.', 'gerfaut-companion'));
        }

        $endpoint = $this->api_mode === 'test'
            ? 'https://api.mondialrelay.com/Web_Services.asmx?WSDL'
            : 'https://api.mondialrelay.com/Web_Services.asmx?WSDL';

        try {
            $soap = new SoapClient($endpoint, array('cache_wsdl' => WSDL_CACHE_NONE));
        } catch (Exception $e) {
            return new WP_Error('soap_error', $e->getMessage());
        }

        $params = array(
            'Enseigne'  => $this->enseigne,
            'Pays'      => 'FR',
            'CP'        => $postcode,
            'Ville'     => $city,
            'Taille'    => 'S',
            'Poids'     => '1',
            'Action'    => '1',
            'Mode'      => '1',
        );

        try {
            $response = $soap->WSI2_GetRelais($params);
        } catch (Exception $e) {
            return new WP_Error('soap_request_error', $e->getMessage());
        }

        if (empty($response) || !isset($response->WSI2_GetRelaisResult)) {
            return new WP_Error('no_response', __('Aucune réponse de Mondial Relay.', 'gerfaut-companion'));
        }

        $raw = $response->WSI2_GetRelaisResult;
        $lines = explode('#', $raw);

        // The response is a hash separated string: first chunk is status
        if (empty($lines) || count($lines) < 2) {
            return new WP_Error('invalid_response', __('Réponse invalide reçue de Mondial Relay.', 'gerfaut-companion'));
        }

        $status = $lines[0];
        if ($status !== 'OK') {
            return new WP_Error('api_error', sprintf(__('Erreur Mondial Relay : %s', 'gerfaut-companion'), $status));
        }

        $points = array();
        $chunks = array_slice($lines, 1);

        // Response format: each relais has 12 parts, see Mondial Relay docs.
        $perPoint = 12;
        foreach (array_chunk($chunks, $perPoint) as $chunk) {
            if (count($chunk) < $perPoint) {
                continue;
            }

            $points[] = array(
                'id'       => $chunk[0],
                'name'     => $chunk[1],
                'address'  => $chunk[2],
                'postcode' => $chunk[3],
                'city'     => $chunk[4],
                'lat'      => $chunk[5],
                'lng'      => $chunk[6],
                'phone'    => $chunk[7],
                'type'     => $chunk[8],
                'distance' => $chunk[9],
                'comment'  => $chunk[10],
                'schedule' => $chunk[11],
            );

            if (count($points) >= $limit) {
                break;
            }
        }

        return $points;
    }
}
