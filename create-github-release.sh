#!/bin/bash

# GitHub Release Creation Script
# Creates a release on GitHub using the GitHub API

VERSION="v1.3.10"
REPO="arthurduino/Gerfaut-Companion"
ZIP_FILE="/home/manager.gerfaut.ovh/public_html/gerfaut-companion-1.3.10.zip"

RELEASE_NOTES="## ✨ Amélioration de la validation d'adresses

### Changements

#### Validation d'adresses limitée à la France
- La fonctionnalité de validation et de correction d'adresses via l'API gouvernementale française (api-adresse.data.gouv.fr) est maintenant **désactivée pour les commandes en dehors de la France**
- Les assets CSS/JavaScript de validation ne sont chargés que si le pays sélectionné est la France
- La validation se désactive automatiquement quand le client change le pays
- La validation se réactive automatiquement si le client sélectionne à nouveau la France

### Avantages
✅ Améliore les performances pour les commandes internationales
✅ Comportement plus logique (pas de validation d'adresse française pour les adresses étrangères)
✅ Dynamique - s'adapte au changement de pays pendant la saisie

### Compatibilité
✅ Entièrement compatible avec les versions précédentes
✅ Aucun changement pour les commandes françaises

### Cas d'usage
- Client français : validation activée ✅
- Client étranger : validation désactivée ❌
- Client change pays vers France : validation activée ✅
- Client change pays vers l'étranger : validation désactivée ❌"

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
echo "3. Title: Gerfaut Companion $VERSION - Address Validation France Only"
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
