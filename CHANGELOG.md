# Changelog - Gerfaut Companion Plugin

## [1.3.0] - 2026-02-06

### 🎉 Major Features - OAuth2 Integration

#### OAuth2 Authentication
- **Simplified Connection**: One-click "Connect to Gerfaut" button instead of manual token configuration
- **OAuth2 Manager**: Complete OAuth2 flow implementation for WordPress
- **Automatic Token Refresh**: No need to manually update tokens
- **Secure Storage**: OAuth tokens stored securely in WordPress options

#### Bidirectional API Communication
- **Gerfaut API Client**: Communicate with Gerfaut server (WP → Gerfaut)
  - Notify order creation
  - Notify order status changes
  - Notify shipments
  - Sync products
  - Get SAV tickets
- **Webhook Receiver**: Receive updates from Gerfaut (Gerfaut → WP)
  - Order updates
  - Shipment notifications
  - SAV ticket updates
- **HMAC-SHA256 Signature**: All webhooks verified with cryptographic signatures

#### Admin UI
- **New Settings Page**: Menu > Gerfaut > Connexion Gerfaut
- **Connection Status**: Visual indicator of OAuth connection status
- **Configuration Options**:
  - Gerfaut URL configuration
  - Auto-sync orders toggle
  - Debug information for admins

#### Auto-Sync
- **Automatic Order Sync**: Optional auto-sync of new orders to Gerfaut
- **Status Change Sync**: Automatic notification on order status changes
- **Manual Control**: Can be enabled/disabled anytime

### 📁 New Files
- `includes/class-oauth-manager.php` - OAuth2 flow management
- `includes/class-gerfaut-api-client.php` - API client for Gerfaut communication
- `includes/class-webhook-receiver.php` - Webhook receiver for incoming events
- `includes/class-oauth-settings-page.php` - Admin settings page

### 🔒 Security Enhancements
- OAuth2 standard authentication
- HMAC-SHA256 webhook signature verification
- Secure token storage
- CSRF protection with state parameter

### 🔄 Backward Compatibility
- ✅ Fully backward compatible with existing installations
- ✅ Old token-based authentication still supported
- ✅ No breaking changes

### 📋 Requirements
- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+
- Gerfaut server v1.3.0+ (with OAuth2 support)

### 🚀 Upgrade Instructions
1. Update the plugin
2. Go to WordPress Admin > Gerfaut > Connexion Gerfaut
3. Configure the Gerfaut URL (https://gerfaut.mooo.com)
4. Click "Connect to Gerfaut"
5. Authorize the connection
6. (Optional) Enable auto-sync orders

---

## [1.2.0] - Previous Release
- Dashboard widget with order statistics
- Custom orders columns (tracking, flags, SAV)
- Email savelink integration
- Embed shortcodes for SAV and contact forms
- HPOS compatibility
