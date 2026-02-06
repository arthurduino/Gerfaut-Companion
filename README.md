# Gerfaut Companion 2.0.0

## 📌 Description

Extension compagnon pour intégrer WordPress/WooCommerce avec Gerfaut. Cette version 2.0 ajoute l'authentification **OAuth2** et la **synchronisation bidirectionnelle** des commandes.

### ✨ Fonctionnalités principales

**Authentification OAuth2**
- Connexion simplifiée avec un clic "Connect to Gerfaut"
- Plus besoin de copier/coller de tokens manuels
- Gestion automatique de l'expiration des tokens

**Communication bidirectionnelle**
- WordPress → Gerfaut : Synchronisation automatique des commandes
- Gerfaut → WordPress : Webhooks en temps réel
- Sync optionnelle des produits et tickets SAV

**Dashboard & Colonnes WooCommerce**
- Widget de statistiques (commandes, revenus)
- Colonnes personnalisées (suivi, drapeaux, SAV)
- Intégration des shortcodes [gerfaut_sav] et [gerfaut_contact]

---

## 📋 Requirements

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+
- Gerfaut 2.0+ (avec OAuth2 support)

---

## 🚀 Installation & Setup

### 1. Installation du plugin

```bash
# Copier le plugin
cp -r gerfaut-companion /path/to/wordpress/wp-content/plugins/

# Ou via FTP/SFTP
# Uploader le dossier gerfaut-companion vers wp-content/plugins/
```

### 2. Activer le plugin

**WordPress Admin > Plugins > Gerfaut Companion > Activate**

### 3. Configuration initiale

**WordPress Admin > Gerfaut > Connexion Gerfaut**

#### 3.1 Configurer l'URL Gerfaut
```
Gerfaut URL: https://gerfaut.mooo.com
Save Settings
```

#### 3.2 Autoriser la connexion
```
Cliquez le bouton: 🔗 Connect to Gerfaut
↓
Vous serez redirigé vers Gerfaut
↓
Acceptez l'autorisation
↓
Retour automatique à WordPress
↓
Status: ✓ Connected
```

#### 3.3 (Optionnel) Activer auto-sync
```
Auto-sync Orders: [✓] Coché
Save Settings
```

---

## 🔧 Configuration avancée

### Options WordPress (via WP-CLI)

```bash
# URL du serveur Gerfaut
wp option update gerfaut_url 'https://gerfaut.mooo.com'

# Activer/désactiver auto-sync
wp option update gerfaut_auto_sync_orders 1

# Vérifier l'authorization
wp option get gerfaut_oauth_authorized

# Vérifier l'email connecté
wp option get gerfaut_user_email
```

### Webhooks

Les webhooks sont **automatiquement enregistrés** :

```
POST https://votre-site.com/wp-json/gerfaut/v1/webhooks/order-updated
POST https://votre-site.com/wp-json/gerfaut/v1/webhooks/order-shipment
POST https://votre-site.com/wp-json/gerfaut/v1/webhooks/sav-ticket
```

Vérifiez que:
1. **REST API** est activée (défaut)
2. **Permalinks** ne contiennent pas index.php

---

## 📡 Utilisation

### Synchronisation automatique (si activée)

**Automatique** - Les événements sont envoyés à Gerfaut :

```
Création de commande       → POST /api/wordpress/orders
Changement de statut       → PUT /api/wordpress/orders/{id}
```

### Synchronisation manuelle

Depuis votre code PHP :

```php
$client = new Gerfaut_API_Client();

if ($client->is_ready()) {
    // Notifier la création
    $client->notify_order_created($order_id);
    
    // Notifier un changement
    $client->notify_order_status_change($order_id, 'pending', 'processing');
    
    // Notifier l'expédition
    $client->notify_order_shipment($order_id, 'FR1234567890', 'La Poste');
    
    // Synchroniser les produits
    $client->sync_products([1, 2, 3]);
}
```

### Shortcodes

**Formulaire SAV :**
```
[gerfaut_sav]
```

**Formulaire de contact :**
```
[gerfaut_contact]
```

Avec paramètres optionnels :
```
[gerfaut_sav height="800px"]
```

---

## 🔄 Migration depuis v1.x

La version 2.0 est **100% compatible** avec v1.x :

✅ Toutes les données sont préservées
✅ Tous les anciens paramètres fonctionnent
✅ Migration progressive possible
✅ Ancien système reste fonctionnel

---

## 📚 Structure du plugin

```
gerfaut-companion/
├── gerfaut-companion.php          # v2.0.0
├── includes/
│   ├── class-oauth-manager.php            # NEW
│   ├── class-gerfaut-api-client.php       # NEW
│   ├── class-webhook-receiver.php         # NEW
│   ├── class-oauth-settings-page.php      # NEW
│   ├── class-dashboard-widget.php
│   ├── class-orders-columns.php
│   └── ...autres fichiers...
└── vendor/
```

---

## 🧪 Troubleshooting

### Connexion impossible
```
1. Vérifier gerfaut_url: wp option get gerfaut_url
2. Vérifier l'accès: curl https://gerfaut.mooo.com
3. Réautoriser: Click "Connect to Gerfaut"
```

### Orders not syncing
```
1. Vérifier Auto-sync: wp option get gerfaut_auto_sync_orders
2. Vérifier token: wp option get gerfaut_oauth_access_token
3. Vérifier logs: tail -f wp-content/debug.log
```

### Webhooks not received
```
1. Vérifier REST API: wp rest-api info
2. Vérifier permalinks: wp option get permalink_structure
3. Vérifier logs: wp log tail
```

---

## 🔐 Sécurité

✅ OAuth2 token management (1 année expiry)
✅ HMAC-SHA256 webhook signatures
✅ Tokens stockés sécurisés
✅ Révocation facile (bouton Disconnect)

---

## 📝 Changelog

### v2.0.0 (2026-02-06) - Major Release

**✨ New:**
- OAuth2 Authentication
- Simplified admin UI
- Bidirectional communication
- Automatic order syncing
- Webhook support

**🔒 Security:**
- OAuth2 token management
- HMAC-SHA256 signatures
- Protected API endpoints

**✅ Compatibility:**
- 100% backward compatible
- No data loss
- Gradual migration path

### v1.2.0 (Previous)
- Dashboard widget
- Order columns
- Email integration
- Shortcodes
zip -r gerfaut-companion.zip gerfaut-companion-plugin/ -x "*.git*" "*.DS_Store" "node_modules/*"
```

Le fichier `gerfaut-companion.zip` sera créé et prêt à être téléversé sur WordPress.

## Déploiement et Mises à jour

### Commande unique pour déployer une nouvelle version

```bash
cd /home/gerfaut.mooo.com/public_html/gerfaut-companion-plugin && chmod +x deploy.sh && ./deploy.sh 1.0.1 "Description de la mise à jour"
```

Cette commande :
1. Met à jour le numéro de version dans le plugin
2. Crée le ZIP prêt pour installation
3. Commit et tag la nouvelle version
4. Push sur GitHub
5. Crée la release automatiquement (si GitHub CLI est installé)

**Note:** Le token GitHub est stocké de manière sécurisée dans `.github-token` (exclu du repo).

## Installation

1. Téléversez le dossier `gerfaut-companion-plugin` dans `/wp-content/plugins/`
   - Ou téléversez le fichier `gerfaut-companion.zip` via le menu Extensions > Ajouter > Téléverser une extension
2. Activez l'extension via le menu 'Extensions' dans WordPress
3. Assurez-vous que WooCommerce est installé et activé

## Configuration

### Configuration de base
Aucune configuration nécessaire. Le plugin fonctionne immédiatement après activation.

### Affichage des SAV
Les tickets SAV sont **automatiquement synchronisés** depuis votre application Laravel vers WooCommerce.
À chaque création ou mise à jour de ticket SAV, un meta_data `_gerfaut_sav_count` est écrit dans la commande WooCommerce.

Le plugin WordPress lit simplement ce meta_data pour afficher le nombre de SAV.

### Synchronisation initiale (optionnel)
Si vous avez déjà des tickets SAV existants, exécutez une fois le script de synchronisation :

```bash
cd /chemin/vers/wordpress/wp-content/plugins/gerfaut-companion-plugin
# Configurer d'abord les identifiants DB dans sync-sav.php
php sync-sav.php
```

Ce script n'est utile que pour la migration initiale. Par la suite, tout est automatique.

## Prérequis

- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+

## Structure des fichiers

```
gerfaut-companion-plugin/
├── gerfaut-companion.php           # Fichier principal du plugin
├── includes/
│   ├── class-dashboard-widget.php  # Classe pour le widget dashboard
│   └── class-orders-columns.php    # Classe pour les colonnes commandes
├── assets/
│   └── css/
│       └── admin.css               # Styles admin
├── sync-sav.php                    # Script de synchronisation SAV
└── README.md                       # Documentation
```

## Développement

### Ajouter de nouvelles statistiques au dashboard
Modifiez la méthode `get_order_statistics()` dans `includes/class-dashboard-widget.php`

### Ajouter de nouvelles colonnes aux commandes
Modifiez les méthodes `add_order_columns()` et `render_column_content()` dans `includes/class-orders-columns.php`

### Distribuer des mises à jour

Le plugin utilise **Plugin Update Checker** pour distribuer les mises à jour automatiquement via GitHub.

**Voir [UPDATE.md](UPDATE.md) pour le guide complet de publication.**

Résumé rapide :
1. Mettre à jour la version dans `gerfaut-companion.php`
2. Commiter et pousser sur GitHub
3. Créer une release avec tag (ex: `v1.0.1`)
4. Les sites WordPress recevront automatiquement la notification de mise à jour

## Auteur

Gerfaut - https://gerfaut.mooo.com

## Licence

Propriétaire

## Version

1.0.0
