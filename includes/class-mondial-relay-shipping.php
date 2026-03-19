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
                'title'       => __('Clé privée (mot de passe API)', 'gerfaut-companion'),
                'type'        => 'text',
                'description' => __('Clé privée fournie par Mondial Relay (utilisée pour calculer la signature Security).', 'gerfaut-companion'),
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
        $selected_attr  = esc_attr($selected_json);
        $button_label   = esc_html__('Choisir un point relais / locker', 'gerfaut-companion');
        $no_point_label = esc_html__('Aucun point relais sélectionné.', 'gerfaut-companion');
        $asset_url      = GERFAUT_COMPANION_PLUGIN_URL . 'assets/js/mondial-relay-checkout.js';

        echo <<<HTML
<div class="gerfaut-mondial-relay-container" data-selected="$selected_attr">
    <p class="gerfaut-mondial-relay-selected">$no_point_label</p>
    <button type="button" class="button gerfaut-mondial-relay-open-map" onclick="if (window.gerfautMondialRelay && typeof window.gerfautMondialRelay.openModal === 'function') { window.gerfautMondialRelay.openModal(); } else { console.log('Gerfaut Relay: openModal not available'); } return false;">$button_label</button>
    <input type="hidden" name="gerfaut_mondial_relay_point" class="gerfaut-mondial-relay-point" value="$selected_attr" />

    <script>
        console.log("Gerfaut Relay: inline debug script loaded");
        setTimeout(function() {
            var script = document.querySelector("script[src*=\"mondial-relay-checkout.js\"]");
            console.log("Gerfaut Relay: script tag present?", !!script, script);

            if (!script) {
                var s = document.createElement('script');
                s.src = "$asset_url";
                s.onload = function() { console.log("Gerfaut Relay: fallback script loaded"); };
                document.body.appendChild(s);
            }
        }, 500);
    </script>
</div>
HTML;

    /**
     * Enqueue frontend assets for the checkout map.
     */
    public function enqueue_assets() {
        if (!function_exists('is_checkout') || !is_checkout()) {
            return;
        }

        // Ensure WooCommerce is active.
        if (!function_exists('WC') || !WC()) {
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

        if (!extension_loaded('soap')) {
            return new WP_Error('no_soap', __('L’extension SOAP n’est pas disponible sur le serveur.', 'gerfaut-companion'));
        }

        $endpoint = 'https://api.mondialrelay.com/Web_Services.asmx?wsdl';

        try {
            $soap = new SoapClient($endpoint, array(
                'trace' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => 10,
            ));
        } catch (Exception $e) {
            return new WP_Error('soap_error', $e->getMessage());
        }

        $params = array(
            'Enseigne'        => $this->enseigne,
            'Pays'            => 'FR',
            'Ville'           => $city,
            'CP'              => $postcode,
            'Poids'           => '100',
            'Action'          => '24R',
            'NombreResultats' => $limit,
        );

        $code = implode('', $params) . $this->code_client;
        $params['Security'] = strtoupper(md5($code));

        try {
            $response = $soap->WSI4_PointRelais_Recherche($params);
        } catch (Exception $e) {
            return new WP_Error('soap_request_error', $e->getMessage());
        }

        if (empty($response) || !isset($response->WSI4_PointRelais_RechercheResult)) {
            return new WP_Error('no_response', __('Aucune réponse de Mondial Relay.', 'gerfaut-companion'));
        }

        $result = $response->WSI4_PointRelais_RechercheResult;
        if (!isset($result->STAT)) {
            return new WP_Error('invalid_response', __('Réponse invalide reçue de Mondial Relay.', 'gerfaut-companion'));
        }

        if ((string) $result->STAT !== '0') {
            $message = (isset($result->error_message) && $result->error_message)
                ? $result->error_message
                : sprintf(__('Erreur Mondial Relay : %s', 'gerfaut-companion'), (string) $result->STAT);
            return new WP_Error('api_error', $message);
        }

        if (empty($result->PointsRelais) || empty($result->PointsRelais->PointRelais_Details)) {
            return array();
        }

        $pickup_list = $result->PointsRelais->PointRelais_Details;
        if (!is_array($pickup_list)) {
            $pickup_list = array($pickup_list);
        }

        $points = array();
        foreach ($pickup_list as $one_pickup) {
            $lat = isset($one_pickup->Latitude) ? str_replace(',', '.', (string) $one_pickup->Latitude) : '';
            $lng = isset($one_pickup->Longitude) ? str_replace(',', '.', (string) $one_pickup->Longitude) : '';

            $points[] = array(
                'id'       => isset($one_pickup->Num) ? (string) $one_pickup->Num : '',
                'name'     => isset($one_pickup->LgAdr1) ? (string) $one_pickup->LgAdr1 : '',
                'address'  => isset($one_pickup->LgAdr3) ? (string) $one_pickup->LgAdr3 : '',
                'postcode' => isset($one_pickup->CP) ? (string) $one_pickup->CP : '',
                'city'     => isset($one_pickup->Ville) ? (string) $one_pickup->Ville : '',
                'lat'      => $lat,
                'lng'      => $lng,
                'phone'    => isset($one_pickup->Tel) ? (string) $one_pickup->Tel : '',
                'type'     => isset($one_pickup->Type) ? (string) $one_pickup->Type : '',
                'distance' => isset($one_pickup->Distance) ? (string) $one_pickup->Distance : '',
                'comment'  => isset($one_pickup->Commentaire) ? (string) $one_pickup->Commentaire : '',
                'schedule' => isset($one_pickup->Horaires) ? (string) $one_pickup->Horaires : '',
            );

            if (count($points) >= $limit) {
                break;
            }
        }

        return $points;
    }
}
