<?php
/**
 * OAuth Settings Page for Gerfaut Integration
 * Admin interface for connecting to Gerfaut
 */

if (!defined('ABSPATH')) {
    exit;
}

class Gerfaut_OAuth_Settings_Page {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }
    
    /**
     * Add menu item in admin
     */
    public function add_admin_menu() {
        add_menu_page(
            'Gerfaut Connection',
            'Gerfaut',
            'manage_options',
            'gerfaut-settings',
            [$this, 'render_settings_page'],
            'dashicons-link'
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('gerfaut_oauth_settings', 'gerfaut_url');
        register_setting('gerfaut_oauth_settings', 'gerfaut_auto_sync_orders');
        register_setting('gerfaut_oauth_settings', 'gerfaut_webhook_secret');
    }
    
    /**
     * Render the settings page
     */
    public function render_settings_page() {
        $oauth = new Gerfaut_OAuth_Manager();
        $is_authorized = $oauth->is_authorized();
        $token = $oauth->get_access_token();
        $user_email = get_option('gerfaut_user_email');
        $gerfaut_url = get_option('gerfaut_url', 'https://gerfaut.mooo.com');
        $auto_sync = get_option('gerfaut_auto_sync_orders');
        
        // Handle errors and messages
        $oauth_error = isset($_GET['oauth_error']) ? sanitize_text_field($_GET['oauth_error']) : null;
        $oauth_success = isset($_GET['oauth_success']) ? sanitize_text_field($_GET['oauth_success']) : null;
        ?>
        
        <div class="wrap">
            <h1>Gerfaut Connection</h1>
            
            <!-- Status Card -->
            <div class="gerfaut-status-card" style="margin: 20px 0; padding: 20px; background: #f5f5f5; border-radius: 8px; border-left: 4px solid <?php echo $is_authorized ? '#28a745' : '#dc3545'; ?>;">
                
                <?php if ($oauth_success): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><strong>✓ Success!</strong> Your WordPress site is now connected to Gerfaut.</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($oauth_error): ?>
                    <div class="notice notice-error is-dismissible">
                        <p><strong>✗ Error:</strong> <?php echo esc_html($oauth_error); ?></p>
                    </div>
                <?php endif; ?>
                
                <h2>Connection Status</h2>
                
                <?php if ($is_authorized): ?>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="font-size: 40px;">✓</div>
                        <div>
                            <p style="margin: 0; font-size: 18px; color: #28a745; font-weight: bold;">Connected to Gerfaut</p>
                            <?php if ($user_email): ?>
                                <p style="margin: 5px 0 0 0; color: #666;">Connected as: <strong><?php echo esc_html($user_email); ?></strong></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('gerfaut_revoke_auth', 'gerfaut_nonce'); ?>
                            <button type="submit" name="gerfaut_revoke" value="1" class="button button-secondary" 
                                    onclick="return confirm('Are you sure you want to disconnect from Gerfaut?');">
                                Disconnect from Gerfaut
                            </button>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="font-size: 40px;">✗</div>
                        <div>
                            <p style="margin: 0; font-size: 18px; color: #dc3545; font-weight: bold;">Not Connected</p>
                            <p style="margin: 5px 0 0 0; color: #666;">Click below to connect your WordPress site to Gerfaut</p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 15px;">
                        <a href="<?php echo esc_url($oauth->get_authorization_url()); ?>" class="button button-primary" style="padding: 10px 20px; font-size: 16px;">
                            🔗 Connect to Gerfaut
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Settings Form -->
            <form method="post" action="options.php" style="margin-top: 30px;">
                <?php settings_fields('gerfaut_oauth_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="gerfaut_url">Gerfaut URL</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="gerfaut_url" 
                                   name="gerfaut_url" 
                                   value="<?php echo esc_url($gerfaut_url); ?>" 
                                   class="regular-text" 
                                   placeholder="https://gerfaut.mooo.com">
                            <p class="description">The URL of your Gerfaut server (used for OAuth and API calls)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="gerfaut_auto_sync_orders">Auto-sync Orders</label>
                        </th>
                        <td>
                            <input type="checkbox" 
                                   id="gerfaut_auto_sync_orders" 
                                   name="gerfaut_auto_sync_orders" 
                                   value="1" 
                                   <?php checked($auto_sync, 1); ?>>
                            <label for="gerfaut_auto_sync_orders">Automatically sync order creation and status changes to Gerfaut</label>
                            <p class="description">When enabled, new orders and status changes will be automatically sent to Gerfaut</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <!-- Token Info (for debugging) -->
            <?php if ($is_authorized && current_user_can('manage_options')): ?>
                <div style="margin-top: 30px; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 4px;">
                    <h3>Debug Information</h3>
                    <p>
                        <strong>OAuth Status:</strong> Connected<br>
                        <strong>Token Prefix:</strong> <?php echo esc_html(substr($token, 0, 10) . '...'); ?><br>
                        <strong>User Email:</strong> <?php echo esc_html($user_email); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <style>
            .gerfaut-status-card h2 {
                margin-top: 0;
                color: #333;
            }
            
            .button-primary {
                background: #007cba;
                color: white;
                border: 1px solid #007cba;
            }
            
            .button-primary:hover {
                background: #005a87;
            }
        </style>
        
        <?php
        
        // Handle revoke request
        if (isset($_POST['gerfaut_revoke'])) {
            if (wp_verify_nonce($_POST['gerfaut_nonce'] ?? '', 'gerfaut_revoke_auth')) {
                $oauth->revoke_authorization();
                wp_redirect(admin_url('admin.php?page=gerfaut-settings&revoked=1'));
                exit;
            }
        }
    }
}

// Initialize
new Gerfaut_OAuth_Settings_Page();
