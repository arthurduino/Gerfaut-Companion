# Validation d'adresse au checkout WooCommerce

## Fonctionnalité

Cette fonctionnalité valide les adresses saisies lors du checkout WooCommerce en utilisant l'API gouvernementale française [api-adresse.data.gouv.fr](https://api-adresse.data.gouv.fr/).

## Caractéristiques

### Suggestions en temps réel
- Affiche des suggestions d'adresses au fur et à mesure de la saisie
- Debounce de 300ms pour éviter les requêtes excessives
- Affichage dès 4 caractères saisis
- Limite de 5 suggestions

### Validation automatique
- Validation lors du blur (sortie du champ)
- Validation lors de la soumission du formulaire
- Score minimum de 0.5 requis pour une validation automatique

### Détection d'anomalies
- **Adresse non validée** : Aucune correspondance trouvée dans l'API
- **Numéro de voie manquant** : Détecte quand l'adresse est une rue sans numéro (type='street')
  - Exception : Les lieux-dits (type='locality') sont acceptés sans numéro
  - Affiche un avertissement orange
  - Demande confirmation avant soumission

### Confirmations utilisateur
Si l'adresse n'est pas validée lors de la soumission :
1. **Adresse avec suggestions** : Propose de choisir une suggestion ou confirmer
2. **Adresse sans numéro de voie** : Demande confirmation spécifique
3. **Adresse forcée** : L'utilisateur peut forcer la soumission après confirmation

## Types d'adresses détectés

L'API retourne différents types d'adresses :
- `housenumber` : Adresse complète avec numéro (✓ valide)
- `street` : Rue sans numéro (⚠️ avertissement)
- `locality` : Lieu-dit (✓ accepté sans numéro)
- `municipality` : Commune

## Indicateurs visuels

### Couleurs
- **Vert** : Adresse validée
- **Orange** : Numéro de voie manquant ou adresse forcée
- **Rouge** : Adresse non validée

### Messages
- "Adresse validée" (vert)
- "Attention : numéro de voie manquant" (orange)
- "Adresse non validée" (rouge)
- "Adresse non validée (confirmée par l'utilisateur)" (orange)

## Champs concernés

- Adresse de facturation (billing)
- Adresse de livraison (shipping) - uniquement si "Livrer à une adresse différente" est coché

## Compatibilité

- WooCommerce 5.0+
- Fonctionne avec les formulaires de checkout standard
- Compatible avec l'event `updated_checkout` de WooCommerce

## Configuration

Les paramètres sont définis dans `class-address-validation.php` :

```php
'apiBase' => 'https://api-adresse.data.gouv.fr/search/',
'limit' => 5,              // Nombre de suggestions
'minChars' => 4,           // Caractères minimum avant recherche
'minScore' => 0.5,         // Score minimum pour validation auto
'debounceMs' => 300,       // Délai avant recherche (ms)
```

## Fichiers

- `includes/class-address-validation.php` : Classe PHP et chargement des assets
- `assets/js/address-validation.js` : Logique JavaScript
- `assets/css/address-validation.css` : Styles

## API utilisée

[API Adresse - Base Adresse Nationale](https://adresse.data.gouv.fr/api-doc/adresse)

Exemple de requête :
```
https://api-adresse.data.gouv.fr/search/?q=8+bd+du+port&limit=5
```

Exemple de réponse :
```json
{
  "features": [{
    "properties": {
      "label": "8 Boulevard du Port 95000 Cergy",
      "score": 0.97,
      "type": "housenumber",
      "housenumber": "8",
      "street": "Boulevard du Port",
      "postcode": "95000",
      "city": "Cergy"
    }
  }]
}
```
