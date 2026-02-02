# Mise à jour automatique du plugin

## Configuration

1. **Créer un dépôt GitHub** pour votre plugin
2. **Éditer** `gerfaut-companion.php` et remplacer `votre-username` par votre nom d'utilisateur GitHub

## Publication d'une mise à jour

### 1. Mettre à jour la version

Éditer `gerfaut-companion.php` :
```php
 * Version: 1.0.1  // Incrémenter la version
```

Et :
```php
define('GERFAUT_COMPANION_VERSION', '1.0.1'); // Même version
```

### 2. Commiter et pousser

```bash
git add .
git commit -m "Version 1.0.1 - Description des changements"
git push origin main
```

### 3. Créer une release sur GitHub

1. Aller sur GitHub → Releases → "Draft a new release"
2. Tag version: `v1.0.1`
3. Release title: `Version 1.0.1`
4. Description: Liste des changements
5. Joindre le fichier ZIP du plugin
6. Publier la release

### 4. Les sites WordPress recevront automatiquement la notification

Les sites avec le plugin installé verront la mise à jour disponible dans Extensions → Mises à jour.

## Repo privé (optionnel)

Si votre repo est privé, générez un token GitHub :
1. GitHub → Settings → Developer settings → Personal access tokens
2. Créer un token avec le scope `repo`
3. Dans `gerfaut-companion.php`, décommenter :
```php
$updateChecker->setAuthentication('votre_github_token');
```

## Alternative : Sans GitHub

Si vous ne voulez pas utiliser GitHub, vous pouvez héberger les mises à jour sur votre propre serveur en créant un fichier JSON avec les informations de version. Voir la documentation de Plugin Update Checker pour plus de détails.
