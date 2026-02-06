# Gerfaut Companion v2.0.0 Release Notes

**Release Date**: February 6, 2026  
**Status**: Stable Release  
**Download**: See releases on GitHub

---

## 🎉 What's New in v2.0.0

### Major Features

#### 1. OAuth2 Authentication ⭐
- **Simplified Connection**: One-click "Connect to Gerfaut" button
- **No Manual Token Copying**: OAuth2 standard flow
- **Automatic Token Renewal**: Tokens refresh automatically before expiry
- **Easy Revocation**: Disconnect anytime via admin UI

#### 2. Bidirectional Communication
- **WordPress → Gerfaut (API)**
  - Automatic order creation notifications
  - Status change updates
  - Shipment tracking information
  - Product synchronization
  
- **Gerfaut → WordPress (Webhooks)**
  - Real-time order updates
  - Shipment notifications
  - SAV ticket updates
  - Automatic metadata storage

#### 3. Automatic Order Syncing
- **Toggle on/off** in admin settings
- **Auto-sync events:**
  - New order creation
  - Order status changes
  - Manual webhook triggers

#### 4. Enhanced Admin Interface
- **New Menu Item**: WordPress Admin > Gerfaut > Connexion Gerfaut
- **Status Display**: Live connection status
- **Configuration Form**: Gerfaut URL, auto-sync toggle
- **One-Click Authorization**: OAuth2 flow

---

## 🔒 Security Enhancements

✅ **OAuth2 Token Management**
- Standard OAuth2 authentication
- Tokens with 1-year expiration
- Automatic refresh before expiry
- Secure storage in WordPress options

✅ **Webhook Signature Verification**
- HMAC-SHA256 signing
- Mandatory signature verification
- Secret rotation support

✅ **API Protection**
- OAuth middleware validation
- Bearer token authentication
- Protected endpoints

---

## 📊 Database Schema Changes

**New Columns Added** (no data loss):
```sql
-- OAuth2 Configuration
oauth_client_id VARCHAR(255) UNIQUE
oauth_client_secret VARCHAR(255)
oauth_scopes TEXT
oauth_authorized_at TIMESTAMP

-- Webhook Configuration
webhook_url VARCHAR(255)
webhook_secret VARCHAR(255)
bidirectional_sync BOOLEAN DEFAULT false

-- Metadata
auth_method ENUM('token', 'oauth2') DEFAULT 'token'
```

**Important**: All existing data is preserved. This is a pure additive migration.

---

## 🔄 Compatibility

### Backward Compatibility ✅
- **100% compatible** with v1.2.0
- Old authentication method still works
- Gradual migration possible
- No breaking changes

### Browser Support
- Chrome/Edge 90+
- Firefox 88+
- Safari 14+

### Server Requirements
- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+
- Gerfaut 2.0+ (required for OAuth2)

---

## 📋 What Changed

### New Files Added

**Admin & Settings**
- `class-oauth-manager.php` - OAuth2 authentication flow
- `class-oauth-settings-page.php` - Admin UI for settings

**API Communication**
- `class-gerfaut-api-client.php` - API client for WP→Gerfaut
- `class-webhook-receiver.php` - Webhook handler for Gerfaut→WP

### Updated Files

**Main Plugin**
- `gerfaut-companion.php` - Version bumped to 2.0.0, new includes

### Unchanged Features ✅
- Dashboard widget (still works)
- Order columns (still works)
- Email integration (still works)
- Shortcodes [gerfaut_sav] and [gerfaut_contact] (still work)
- All settings preserved

---

## 🚀 Getting Started

### For Existing Users (v1.x → v2.0)

1. **Update Plugin**
   ```
   WordPress Admin > Plugins > Update Gerfaut Companion
   ```

2. **Go to Settings**
   ```
   WordPress Admin > Gerfaut > Connexion Gerfaut
   ```

3. **Connect OAuth2**
   - Set Gerfaut URL: `https://gerfaut.mooo.com`
   - Click "🔗 Connect to Gerfaut"
   - Authorize on OAuth screen
   - Status shows "✓ Connected"

4. **Optional: Enable Auto-Sync**
   - Check "Auto-sync Orders"
   - Save Settings

### For New Users

1. **Install Plugin**
   ```
   1. Download gerfaut-companion-2.0.0.zip
   2. WordPress Admin > Plugins > Add New > Upload
   3. Select ZIP file and upload
   4. Activate plugin
   ```

2. **Configure**
   ```
   WordPress Admin > Gerfaut > Connexion Gerfaut
   ```

3. **Connect**
   - Click "🔗 Connect to Gerfaut"
   - Follow OAuth2 flow
   - Done!

---

## 🧪 Testing Checklist

Before deploying to production:

- [ ] Plugin activates without errors
- [ ] No PHP warnings/notices
- [ ] Admin menu appears
- [ ] OAuth connect button displays
- [ ] Can authorize via OAuth2
- [ ] Status shows "Connected" after auth
- [ ] Orders sync if auto-sync enabled
- [ ] Webhooks received correctly
- [ ] Order metadata updates correctly
- [ ] Old features still work
  - [ ] Dashboard widget
  - [ ] Order columns
  - [ ] Shortcodes

---

## 📈 Performance Impact

✅ **Minimal**
- OAuth2 tokens cached
- Webhooks asynchronous
- No additional database queries (beyond needed)
- API calls only when needed

---

## 🐛 Known Issues

**None at release**

---

## 📚 Documentation

### User Guide
- **README.md** - Full user documentation
- **Installation**: See README.md setup section
- **Configuration**: See README.md config section
- **Troubleshooting**: See README.md troubleshooting section

### Developer Guide
- See [Gerfaut Documentation](https://gerfaut.mooo.com/docs)
- See [OAuth2 Implementation Guide](../OAUTH2_DEPLOYMENT.md)

---

## 🆘 Support

### If You Find Issues

1. **Check troubleshooting** in README.md
2. **Check WordPress logs**: `wp-content/debug.log`
3. **Check plugin status**: WordPress Admin > Plugins
4. **Verify configuration**: WordPress Admin > Gerfaut

### Common Issues & Solutions

#### "Cannot connect to Gerfaut"
- Verify Gerfaut URL is correct
- Check internet connection
- Verify Gerfaut server is running

#### "Orders not syncing"
- Verify auto-sync is enabled
- Check OAuth connection status
- Verify Gerfaut is receiving API calls

#### "Webhooks not working"
- Verify REST API is enabled
- Check WordPress logs for errors
- Verify webhook secret matches

---

## 🙏 Credits

Developed by **Gerfaut Team**

Plugin designed to integrate WordPress/WooCommerce with Gerfaut order management system.

---

## 📄 License

GNU General Public License v2.0 or later

See LICENSE file for details.

---

## 🎯 Future Roadmap

### v2.1 (Planned)
- [ ] Advanced webhook filtering
- [ ] Custom field mapping
- [ ] Batch synchronization

### v3.0 (Planned)
- [ ] Complete refactor with modern PHP
- [ ] Advanced settings UI
- [ ] Extended API support

---

## 🔗 Links

- **Download**: See GitHub Releases
- **GitHub**: https://github.com/arthurduino/Gerfaut-Companion
- **Gerfaut**: https://gerfaut.mooo.com
- **Support**: Check documentation

---

**🎉 Thank you for upgrading to Gerfaut Companion 2.0.0!**

If you have feedback or issues, please report them on GitHub.
