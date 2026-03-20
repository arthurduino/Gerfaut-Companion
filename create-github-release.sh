#!/bin/bash

# GitHub Release Creation Script
# Creates a release on GitHub using the GitHub API

VERSION="v1.3.20"
REPO="arthurduino/Gerfaut-Companion"
ZIP_FILE="/home/manager.gerfaut.ovh/public_html/gerfaut-companion-1.3.20.zip"

RELEASE_NOTES="## ✨ Custom Stickers v1.03

### Changements
- Ajout validation nombre caract.` > seuil (format texte sticker)
- Correction du calcul de prix par mm pour devinciations < 1
- Mise à jour des titres admin et aides de saisie

### Avantages
✅ Réduction des erreurs utilisateurs
✅ Meilleurs retours de debug dans journal admin
✅ Résilience accrues côté checkout

### Compatibilité
✅ Compatible avec v1.3.x

### Notes
- Toujours configurable via WooCommerce > Stickers Gerfaut
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
