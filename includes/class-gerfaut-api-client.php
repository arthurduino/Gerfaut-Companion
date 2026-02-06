<?php
/**
 * API Client for communication with Gerfaut
 * Enables WP -> Gerfaut communication (orders, products sync)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_API_Client {
    
    private $base_url;
    private $access_token;
    
    public function __construct() {
        $this->base_url = rtrim(get_option('gerfaut_url', 'https://gerfaut.mooo.com'), '/');
        
        // Get OAuth Manager instance
        if (class_exists('Gerfaut_OAuth_Manager')) {
            $oauth = new Gerfaut_OAuth_Manager();
            $this->access_token = $oauth->refresh_token_if_needed();
        }
    }
    
    /**
     * Check if API is ready (authorized)
     */
    public function is_ready() {
        return !empty($this->access_token);
    }
    
    /**
     * Send GET request to Gerfaut API
     */
    public function get($endpoint, $args = []) {
        return $this->request('GET', $endpoint, null, $args);
    }
    
    /**
     * Send POST request to Gerfaut API
     */
    public function post($endpoint, $data = [], $args = []) {
        return $this->request('POST', $endpoint, $data, $args);
    }
    
    /**
     * Send PUT request to Gerfaut API
     */
    public function put($endpoint, $data = [], $args = []) {
        return $this->request('PUT', $endpoint, $data, $args);
    }
    
    /**
     * Generic request method
     */
    private function request($method, $endpoint, $data = null, $args = []) {
        if (!$this->access_token) {
            return [
                'success' => false,
                'error' => 'Not authorized. Please connect to Gerfaut first.',
            ];
        }
        
        $url = $this->base_url . '/api/wordpress' . $endpoint;
        
        $request_args = array_merge([
            'method' => $method,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'timeout' => 15,
            'sslverify' => apply_filters('https_local_over_ssl', false),
        ], $args);
        
        if ($data) {
            $request_args['body'] = wp_json_encode($data);
        }
        
        $response = wp_remote_request($url, $request_args);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => $response->get_error_message(),
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        return [
            'success' => $status_code >= 200 && $status_code < 300,
            'status_code' => $status_code,
            'data' => $body,
        ];
    }
    
    /**
     * Notify order creation to Gerfaut
     */
    public function notify_order_created($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        
        $items = [];
        foreach ($order->get_items() as $item) {
            $items[] = [
                'product_id' => $item->get_product_id(),
                'product_name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'price' => $item->get_total(),
            ];
        }
        
        return $this->post('/orders', [
            'order_id' => $order_id,
            'order_number' => $order->get_order_number(),
            'customer_email' => $order->get_billing_email(),
            'customer_name' => $order->get_formatted_billing_full_name(),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'status' => $order->get_status(),
            'items' => $items,
            'created_at' => $order->get_date_created()->toIso8601String(),
        ]);
    }
    
    /**
     * Notify order status change to Gerfaut
     */
    public function notify_order_status_change($order_id, $old_status, $new_status) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }
        
        return $this->put('/orders/' . $order_id, [
            'status' => $new_status,
            'previous_status' => $old_status,
            'updated_at' => current_time('c'),
        ]);
    }
    
    /**
     * Notify order shipment to Gerfaut
     */
    public function notify_order_shipment($order_id, $tracking_number, $carrier) {
        return $this->put('/orders/' . $order_id . '/shipment', [
            'tracking_number' => $tracking_number,
            'carrier' => $carrier,
            'shipped_at' => current_time('c'),
        ]);
    }
    
    /**
     * Fetch order details from Gerfaut
     */
    public function get_order($order_id) {
        return $this->get('/orders/' . $order_id);
    }
    
    /**
     * Sync WooCommerce products to Gerfaut
     */
    public function sync_products($product_ids = []) {
        $args = [
            'limit' => 100,
        ];
        
        if (!empty($product_ids)) {
            $args['include'] = $product_ids;
        }
        
        $products = wc_get_products($args);
        
        if (empty($products)) {
            return ['success' => true, 'data' => ['synced' => 0]];
        }
        
        $products_data = [];
        foreach ($products as $product) {
            $products_data[] = [
                'product_id' => $product->get_id(),
                'name' => $product->get_name(),
                'sku' => $product->get_sku(),
                'price' => $product->get_price(),
                'stock' => $product->get_stock_quantity(),
                'status' => $product->get_status(),
            ];
        }
        
        return $this->post('/products/sync', [
            'products' => $products_data,
        ]);
    }
    
    /**
     * Get SAV tickets from Gerfaut
     */
    public function get_sav_tickets($order_id = null) {
        $endpoint = '/sav/tickets';
        
        if ($order_id) {
            $endpoint .= '?order_id=' . $order_id;
        }
        
        return $this->get($endpoint);
    }
    
    /**
     * Update SAV ticket on Gerfaut
     */
    public function update_sav_ticket($ticket_id, $status, $comment = null) {
        $data = ['status' => $status];
        
        if ($comment) {
            $data['comment'] = $comment;
        }
        
        return $this->put('/sav/tickets/' . $ticket_id, $data);
    }
}

/**
 * Hook into WooCommerce order events for automatic sync
 */

// When order is created
add_action('woocommerce_new_order', function($order_id) {
    if (get_option('gerfaut_auto_sync_orders')) {
        $client = new Gerfaut_API_Client();
        if ($client->is_ready()) {
            $client->notify_order_created($order_id);
        }
    }
});

// When order status changes
add_action('woocommerce_order_status_changed', function($order_id, $old_status, $new_status) {
    if (get_option('gerfaut_auto_sync_orders')) {
        $client = new Gerfaut_API_Client();
        if ($client->is_ready()) {
            $client->notify_order_status_change($order_id, $old_status, $new_status);
        }
    }
}, 10, 3);
