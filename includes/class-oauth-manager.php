<?php
/**
 * OAuth Manager Class for Gerfaut Integration
 * Handles OAuth2 flow with Gerfaut server
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_OAuth_Manager {
    
    private $gerfaut_url;
    private $client_id;
    private $redirect_uri;
    private $token_option = 'gerfaut_oauth_access_token';
    private $refresh_token_option = 'gerfaut_oauth_refresh_token';
    private $token_expires_option = 'gerfaut_oauth_token_expires_at';
    private $authorized_option = 'gerfaut_oauth_authorized';
    
    public function __construct() {
        $this->gerfaut_url = rtrim(get_option('gerfaut_url', 'https://gerfaut.mooo.com'), '/');
        $this->client_id = 'wordpress-' . md5(site_url());
        $this->redirect_uri = admin_url('admin-ajax.php?action=gerfaut_oauth_callback');
        
        // Handle OAuth callback
        add_action('wp_ajax_gerfaut_oauth_callback', [$this, 'handle_oauth_callback']);
        add_action('wp_ajax_nopriv_gerfaut_oauth_callback', [$this, 'handle_oauth_callback']);
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Get the OAuth authorization URL
     */
    public function get_authorization_url() {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => urlencode($this->redirect_uri),
            'response_type' => 'code',
            'scope' => 'orders:read orders:write products:read',
            'state' => $this->generate_state(),
        ];
        
        return $this->gerfaut_url . '/api/oauth/authorize?' . http_build_query($params);
    }
    
    /**
     * Exchange authorization code for access token
     */
    public function exchange_code_for_token($code, $state) {
        // Verify state to prevent CSRF
        $stored_state = get_transient('gerfaut_oauth_state');
        if (!$stored_state || $stored_state !== $state) {
            return new WP_Error('invalid_state', 'Invalid OAuth state parameter');
        }
        
        $response = wp_remote_post($this->gerfaut_url . '/api/oauth/token', [
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->client_id,
                'redirect_uri' => $this->redirect_uri,
            ],
            'timeout' => 10,
        ]);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            return new WP_Error('oauth_error', $body['error']);
        }
        
        if (!isset($body['access_token'])) {
            return new WP_Error('no_token', 'No access token in response');
        }
        
        // Store tokens
        update_option($this->token_option, $body['access_token']);
        if (isset($body['refresh_token'])) {
            update_option($this->refresh_token_option, $body['refresh_token']);
        }
        
        $expires_in = $body['expires_in'] ?? 31536000; // 1 year default
        update_option($this->token_expires_option, time() + $expires_in);
        update_option($this->authorized_option, 1);
        
        // Clear state
        delete_transient('gerfaut_oauth_state');
        
        return $body['access_token'];
    }
    
    /**
     * Handle OAuth callback from Gerfaut
     */
    public function handle_oauth_callback() {
        $code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : null;
        $state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : null;
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : null;
        
        if ($error) {
            wp_safe_remote_post(admin_url('admin-ajax.php'), [
                'blocking' => false,
                'sslverify' => apply_filters('https_local_over_ssl', false),
                'body' => [
                    'action' => 'gerfaut_oauth_error',
                    'error' => $error,
                ],
            ]);
            wp_redirect(admin_url('admin.php?page=gerfaut-settings&oauth_error=' . $error));
            exit;
        }
        
        if (!$code) {
            wp_redirect(admin_url('admin.php?page=gerfaut-settings&oauth_error=no_code'));
            exit;
        }
        
        $token = $this->exchange_code_for_token($code, $state);
        
        if (is_wp_error($token)) {
            wp_redirect(admin_url('admin.php?page=gerfaut-settings&oauth_error=' . $token->get_error_code()));
            exit;
        }
        
        // Fetch and store Gerfaut settings
        $this->sync_gerfaut_settings();
        
        wp_redirect(admin_url('admin.php?page=gerfaut-settings&oauth_success=1'));
        exit;
    }
    
    /**
     * Fetch current user/site info from Gerfaut
     */
    public function sync_gerfaut_settings() {
        $token = $this->get_access_token();
        if (!$token) {
            return false;
        }
        
        $response = wp_remote_get($this->gerfaut_url . '/api/wordpress/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ],
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['user'])) {
            update_option('gerfaut_user_id', $data['user']['id']);
            update_option('gerfaut_user_email', $data['user']['email']);
        }
        
        if (isset($data['site'])) {
            update_option('gerfaut_site_id', $data['site']['id']);
            update_option('gerfaut_site_url', $data['site']['shop_url']);
        }
        
        return true;
    }
    
    /**
     * Get stored access token
     */
    public function get_access_token() {
        return get_option($this->token_option);
    }
    
    /**
     * Check if authorized
     */
    public function is_authorized() {
        return (bool) get_option($this->authorized_option);
    }
    
    /**
     * Revoke OAuth authorization
     */
    public function revoke_authorization() {
        delete_option($this->token_option);
        delete_option($this->refresh_token_option);
        delete_option($this->token_expires_option);
        delete_option($this->authorized_option);
        delete_option('gerfaut_user_id');
        delete_option('gerfaut_user_email');
        delete_option('gerfaut_site_id');
        delete_option('gerfaut_site_url');
        
        return true;
    }
    
    /**
     * Refresh access token if expired
     */
    public function refresh_token_if_needed() {
        $expires_at = get_option($this->token_expires_option);
        
        if (!$expires_at || time() < ($expires_at - 300)) { // Refresh 5 min before expiry
            return $this->get_access_token();
        }
        
        $refresh_token = get_option($this->refresh_token_option);
        if (!$refresh_token) {
            return null;
        }
        
        $response = wp_remote_post($this->gerfaut_url . '/api/oauth/token', [
            'body' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token,
                'client_id' => $this->client_id,
            ],
        ]);
        
        if (is_wp_error($response)) {
            return null;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['access_token'])) {
            return null;
        }
        
        // Store new tokens
        update_option($this->token_option, $body['access_token']);
        if (isset($body['refresh_token'])) {
            update_option($this->refresh_token_option, $body['refresh_token']);
        }
        $expires_in = $body['expires_in'] ?? 31536000;
        update_option($this->token_expires_option, time() + $expires_in);
        
        return $body['access_token'];
    }
    
    /**
     * Generate CSRF state token
     */
    private function generate_state() {
        $state = bin2hex(random_bytes(32));
        set_transient('gerfaut_oauth_state', $state, HOUR_IN_SECONDS);
        return $state;
    }
    
    /**
     * Register OAuth settings
     */
    public function register_settings() {
        register_setting('gerfaut_oauth_settings', 'gerfaut_url');
    }
}

// Initialize
new Gerfaut_OAuth_Manager();
