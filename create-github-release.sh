#!/bin/bash

# GitHub Release Creation Script
# Creates a release on GitHub using the GitHub API

VERSION="v1.3.21"
REPO="arthurduino/Gerfaut-Companion"
ZIP_FILE="/home/manager.gerfaut.ovh/public_html/gerfaut-companion-1.3.21.zip"

RELEASE_NOTES="## ✨ Custom Stickers v1.04

### Changements
- Ajout option limite maximale (width/height) pour sécuriser l'upload
- Amélioration des messages d'erreur via `wc_add_notice`
- Nettoyage JS et suppression de code mort

### Avantages
✅ Meilleure protection UX 
✅ Moins d'inputs invalides
✅ Contrôle de qualité renforcé

### Compatibilité
✅ Compatible avec v1.3.x

### Notes
- Ne modifie pas le comportement back-office.
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
