#!/bin/bash

# GitHub Release Creation Script
# Creates a release on GitHub using the GitHub API

VERSION="v1.3.0"
REPO="arthurduino/Gerfaut-Companion"
ZIP_FILE="/home/gerfaut.mooo.com/public_html/gerfaut-companion-1.3.0.zip"

RELEASE_NOTES="## 🎉 OAuth2 Bidirectional Integration

### Major Features

#### OAuth2 Authentication
- **Simplified Connection**: One-click \"Connect to Gerfaut\" button
- **OAuth2 Manager**: Complete OAuth2 flow implementation
- **Automatic Token Refresh**: No manual token updates needed
- **Secure Storage**: OAuth tokens stored securely in WordPress

#### Bidirectional API Communication
- **WP → Gerfaut**: Order sync, status changes, shipments, products
- **Gerfaut → WP**: Real-time order updates via webhooks
- **HMAC-SHA256**: Cryptographic signature verification

#### Admin UI
- **New Settings Page**: Menu > Gerfaut > Connexion Gerfaut
- **Connection Status**: Visual connection indicator
- **Auto-sync Toggle**: Enable/disable automatic order sync

### New Files
- \`class-oauth-manager.php\` - OAuth2 flow management
- \`class-gerfaut-api-client.php\` - API client for Gerfaut
- \`class-webhook-receiver.php\` - Webhook receiver
- \`class-oauth-settings-page.php\` - Admin settings page

### Security
- OAuth2 standard authentication
- HMAC-SHA256 webhook signatures
- CSRF protection
- Secure token storage

### Compatibility
✅ 100% backward compatible
✅ Old token auth still supported
✅ No breaking changes

### Requirements
- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+
- Gerfaut server v1.3.0+

### Upgrade Instructions
1. Update the plugin
2. Go to WordPress Admin > Gerfaut > Connexion Gerfaut
3. Configure Gerfaut URL
4. Click \"Connect to Gerfaut\"
5. Authorize the connection"

echo "======================================================================"
echo "GitHub Release Creation"
echo "======================================================================"
echo ""
echo "Repository: $REPO"
echo "Version: $VERSION"
echo "Archive: $ZIP_FILE"
echo ""
echo "To create the release manually:"
echo ""
echo "1. Go to: https://github.com/$REPO/releases/new"
echo "2. Tag: $VERSION"
echo "3. Title: Gerfaut Companion $VERSION - OAuth2 Integration"
echo "4. Upload the ZIP file: $ZIP_FILE"
echo "5. Paste the release notes (see below)"
echo ""
echo "======================================================================"
echo "RELEASE NOTES:"
echo "======================================================================"
echo ""
echo "$RELEASE_NOTES"
echo ""
echo "======================================================================"
echo "Archive location: $ZIP_FILE"
echo "======================================================================"
