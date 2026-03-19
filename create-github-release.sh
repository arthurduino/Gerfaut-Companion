#!/bin/bash

# GitHub Release Creation Script
# Creates a release on GitHub using the GitHub API

VERSION="v1.3.12"
REPO="arthurduino/Gerfaut-Companion"
ZIP_FILE="/home/manager.gerfaut.ovh/public_html/gerfaut-companion-1.3.12.zip"

RELEASE_NOTES="## ✨ Nouveautés : Meilleur suivi de commande

### Changements
- Amélioration du suivi de commande : les liens sont maintenant récupérés depuis **config/carriers.json** (support multi-transporteurs)

### Avantages
✅ Plus de flexibilité pour les transporteurs (suivi dynamique)

### Compatibilité
✅ Compatible avec les versions précédentes
✅ Fonctionne avec les configurations existantes de suivi de transporteurs

### Notes
- Le suivi est généré uniquement si un modèle de lien est présent dans **config/carriers.json**
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
