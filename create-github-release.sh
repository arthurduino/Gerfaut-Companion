#!/bin/bash

# GitHub Release Creation Script
# Creates a release on GitHub using the GitHub API

VERSION="v1.3.18"
REPO="arthurduino/Gerfaut-Companion"
ZIP_FILE="/home/manager.gerfaut.ovh/public_html/gerfaut-companion-1.3.18.zip"

RELEASE_NOTES="## ✨ Custom Stickers v1.01

### Changements
- Améliorations du builder de stickers (stabilité, validation et UX)
- Amélioration du mapping des métas produits vers le panier
- Meilleure résistance aux champs invalides (orientation/largeur/hauteur)

### Avantages
✅ Moins de bugs de chargement au checkout
✅ Meilleure reprise en cas d’erreur de payload
✅ Traitement des commandes stickers plus fiable

### Compatibilité
✅ Compatible avec v1.3.x

### Notes
- Ce correctif est rétrocompatible avec l’ancien mécanisme de commande stickers
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
