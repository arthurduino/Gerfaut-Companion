#!/bin/bash

# GitHub Release Creation Script
# Creates a release on GitHub using the GitHub API

VERSION="v1.3.19"
REPO="arthurduino/Gerfaut-Companion"
ZIP_FILE="/home/manager.gerfaut.ovh/public_html/gerfaut-companion-1.3.19.zip"

RELEASE_NOTES="## ✨ Custom Stickers v1.02

### Changements
- Prise en charge du mode charge utile JSON enrichi
- Amélioration des validations fiche produit / taille en mm
- Correctifs affichage panier pour grandes quantités

### Avantages
✅ UX plus fluide en commande sticker
✅ Meilleur dégradé sur erreur de saisie
✅ Nettoyage best-effort du cart_item_data

### Compatibilité
✅ Compatible avec v1.3.x

### Notes
- Points de configuration restés identiques (WooCommerce > Stickers Gerfaut)
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
