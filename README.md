# Gerfaut Companion Plugin

Extension WordPress/WooCommerce compagnon pour afficher des informations utiles sur le dashboard et la liste des commandes.

## Fonctionnalités

### Dashboard WordPress
- **Widget de statistiques** affichant :
  - Nombre de commandes aujourd'hui
  - Nombre de commandes cette semaine
  - Nombre de commandes ce mois
  - Revenus du jour
- **Liste des commandes récentes** (5 dernières)
- **Liste des commandes en attente** (en attente de paiement ou en attente)

### Liste des commandes WooCommerce
Ajoute des colonnes personnalisées :
- **Suivi** : Numéro de suivi et transporteur
- **État suivi** : Statut actuel du colis (Distribué, En transit, etc.)
- **Drapeaux** : Bouton pour marquer les drapeaux comme commandés
- **SAV** : Nombre de tickets SAV associés à la commande avec liens directs

### Intégration de formulaires (Shortcodes)
Intégrez facilement les formulaires sur vos pages WordPress :

**Formulaire SAV :**
```
[gerfaut_sav]
```

**Formulaire de contact :**
```
[gerfaut_contact]
```

Paramètres optionnels :
- `site_url` : URL du site (par défaut : URL WordPress actuelle)
- `height` : Hauteur minimale du conteneur (ex: `height="600px"`)

Exemple :
```
[gerfaut_sav height="800px"]
```

## Génération du fichier ZIP

Pour créer le fichier ZIP à installer sur WordPress :

```bash
cd /home/gerfaut.mooo.com/public_html
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
