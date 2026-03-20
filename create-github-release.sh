#!/bin/bash

# GitHub Release Creation Script
# Creates a release on GitHub using the GitHub API

VERSION="v1.3.17"
REPO="arthurduino/Gerfaut-Companion"
ZIP_FILE="/home/manager.gerfaut.ovh/public_html/gerfaut-companion-1.3.17.zip"

RELEASE_NOTES="## ✨ Nouveautés : Custom Stickers v1

### Changements
- Ajout de la fonction Custom Stickers v1 dans Gerfaut Companion
- Intégration du builder niveau commande, restitution sur les lignes de panier et commandes
- Synchronisation des commandes stickers vers l’endpoint Laravel configuré

### Avantages
✅ Paramétrage prix et tranches de remise
✅ Centralisation des commandes stickers
✅ Envoi automatique à l’endpoint de production

### Compatibilité
✅ Fonctionne avec les versions précédentes
✅ Pas d’impact sur la livraison ou modules existants

### Notes
- Vérifier les options dans WooCommerce > Stickers Gerfaut
"

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
echo "3. Title: Gerfaut Companion $VERSION - meilleurs suivis"
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
