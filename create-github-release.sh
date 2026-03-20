#!/bin/bash

# GitHub Release Creation Script
# Creates a release on GitHub using the GitHub API

VERSION="v1.3.22"
REPO="arthurduino/Gerfaut-Companion"
ZIP_FILE="/home/manager.gerfaut.ovh/public_html/gerfaut-companion-1.3.22.zip"

RELEASE_NOTES="## ✨ Custom Stickers v1.05

### Changements
- Ajout d'une validation serveur du paramètre `threshold` (0-255)
- Harmonisation des sauvegardes meta avec prefixe `_gerfaut_`
- Amélioration du log d'export pour Laravel

### Avantages
✅ Moins de données corrompues en base
✅ Meilleure compatibilité multilingue
✅ Report d'erreur précis

### Compatibilité
✅ Compatible avec v1.3.x

### Notes
- Le test de webhook sticker doit être ré-exécuté après mise à jour
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
