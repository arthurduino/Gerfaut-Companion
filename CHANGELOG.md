# Changelog - Gerfaut Companion Plugin

## [1.3.10] - 2026-03-07

### ✨ Amélioration de la validation d'adresses
- **Validation d'adresses limitée à la France**: La validation et la correction d'adresses via l'API gouvernementale française (api-adresse.data.gouv.fr) est maintenant désactivée pour les commandes en dehors de la France
- La fonctionnalité se réactive automatiquement si le client sélectionne la France comme pays de livraison
- Améliore les performances pour les commandes internationales

---

## [1.3.8] - 2026-02-08

### ✨ Corrections mineures
- Amélioration des styles de validation d'adresse
- Optimisation du code JavaScript
- Corrections dans la classe d'intégration WooCommerce
- Petites améliorations UX/UI

---

## [1.3.7] - 2026-02-06

### ❌ CORRECTIF CRITIQUE (bis)
- **Correction erreur fatale** : Suppression des require_once OAuth qui causaient un crash (fichiers supprimés)
- Les fichiers OAuth ont été définitivement retirés
- Plugin stable sans dépendances OAuth

---

## [1.3.6] - 2026-02-06

### ❌ CORRECTIF CRITIQUE
- **Correction erreur fatale** : Suppression du code de validation automatique qui causait un crash du site
- Retour à la validation manuelle uniquement (plus stable)
- Site fonctionnel restauré

---

## [1.3.5] - 2026-02-06

### 🐛 Corrections de bugs d'affichage
- Amélioration du feedback visuel de validation d'adresse
- Détection des rues sans numéro avec avertissement approprié
- Confirmation explicite avant validation d'adresse incomplète
- Nettoyage correct des classes CSS de validation WooCommerce
- Feedback immédiat lors de la frappe pour meilleure UX

---

## [1.3.4] - 2026-02-06

### ✅ Validation d'adresse au checkout
- Validation d'adresse en temps réel au checkout WooCommerce
- Vérification automatique via API de géocodage
- Scripts et styles dédiés pour l'intégration
- Amélioration de la qualité des données d'adresse

### 🗑️ Retrait des fonctionnalités OAuth
- Suppression du système OAuth2 (non utilisé)
- Simplification de l'architecture du plugin
- Réduction de la taille et de la complexité

---

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
3. Configure the Gerfaut URL (https://manager.gerfaut.ovh)
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
