<?php
/**
 * Webhook Receiver for Gerfaut -> WordPress communication
 * Handles incoming webhooks from Gerfaut server
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_Webhook_Receiver {
    
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }
    
    /**
     * Register REST API endpoints for webhooks
     */
    public function register_endpoints() {
        register_rest_route('gerfaut/v1', '/webhooks/order-updated', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'handle_order_updated'],
                'permission_callback' => [$this, 'verify_webhook_signature'],
            ],
        ]);
        
        register_rest_route('gerfaut/v1', '/webhooks/order-shipment', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'handle_order_shipment'],
                'permission_callback' => [$this, 'verify_webhook_signature'],
            ],
        ]);
        
        register_rest_route('gerfaut/v1', '/webhooks/sav-ticket', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'handle_sav_ticket'],
                'permission_callback' => [$this, 'verify_webhook_signature'],
            ],
        ]);
    }
    
    /**
     * Verify webhook signature from Gerfaut
     */
    public function verify_webhook_signature(\WP_REST_Request $request) {
        $signature = $request->get_header('X-Gerfaut-Signature');
        
        if (!$signature) {
            return false;
        }
        
        $body = $request->get_body();
        $secret = get_option('gerfaut_webhook_secret');
        
        if (!$secret) {
            return false;
        }
        
        $expected = hash_hmac('sha256', $body, $secret);
        
        return hash_equals($signature, $expected);
    }
    
    /**
     * Handle order update webhook
     */
    public function handle_order_updated(\WP_REST_Request $request) {
        $data = $request->get_json_params();
        
        if (!isset($data['order_id'])) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Missing order_id',
            ], 400);
        }
        
        $order_id = $data['order_id'];
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        }
        
        // Update order status if provided
        if (isset($data['status'])) {
            $order->set_status($data['status']);
        }
        
        // Store Gerfaut metadata
        if (isset($data['gerfaut_order_id'])) {
            $order->update_meta_data('_gerfaut_order_id', $data['gerfaut_order_id']);
        }
        
        if (isset($data['metadata'])) {
            foreach ($data['metadata'] as $key => $value) {
                $order->update_meta_data('_gerfaut_' . $key, $value);
            }
        }
        
        $order->save();
        
        do_action('gerfaut_order_updated', $order_id, $data);
        
        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Order updated',
        ], 200);
    }
    
    /**
     * Handle shipment webhook
     */
    public function handle_order_shipment(\WP_REST_Request $request) {
        $data = $request->get_json_params();
        
        if (!isset($data['order_id'])) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Missing order_id',
            ], 400);
        }
        
        $order_id = $data['order_id'];
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        }
        
        // Store shipment info
        if (isset($data['tracking_number'])) {
            $order->update_meta_data('_tracking_number', $data['tracking_number']);
        }
        
        if (isset($data['carrier'])) {
            $order->update_meta_data('_carrier', $data['carrier']);
        }
        
        if (isset($data['shipped_at'])) {
            $order->update_meta_data('_shipped_at', $data['shipped_at']);
        }
        
        $order->set_status('shipped');
        $order->save();
        
        // Send notification to customer
        do_action('gerfaut_order_shipped', $order_id, $data);
        
        return new \WP_REST_Response([
            'success' => true,
            'message' => 'Shipment recorded',
        ], 200);
    }
    
    /**
     * Handle SAV ticket webhook
     */
    public function handle_sav_ticket(\WP_REST_Request $request) {
        $data = $request->get_json_params();
        
        if (!isset($data['order_id'])) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Missing order_id',
            ], 400);
        }
        
        $order = wc_get_order($data['order_id']);
        
        if (!$order) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Order not found',
            ], 404);
        }
        
        // Store SAV ticket info
        if (isset($data['ticket_id'])) {
            $order->update_meta_data('_gerfaut_sav_ticket_id', $data['ticket_id']);
        }
        
        if (isset($data['status'])) {
            $order->update_meta_data('_gerfaut_sav_status', $data['status']);
        }
        
        if (isset($data['message'])) {
            $order->update_meta_data('_gerfaut_sav_message', $data['message']);
        }
        
        $order->save();
        
        do_action('gerfaut_sav_ticket_updated', $data['order_id'], $data);
        
        return new \WP_REST_Response([
            'success' => true,
            'message' => 'SAV ticket updated',
        ], 200);
    }
}

// Initialize
new Gerfaut_Webhook_Receiver();
