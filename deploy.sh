#!/bin/bash
# Script de mise à jour automatique du plugin Gerfaut Companion
# Usage: ./deploy.sh 1.0.1 "Description de la mise à jour"

set -e

if [ -z "$1" ]; then
    echo "❌ Version manquante. Usage: ./deploy.sh 1.0.1 \"Description\""
    exit 1
fi

VERSION=$1
DESCRIPTION=${2:-"Mise à jour"}
PLUGIN_DIR=$(dirname "$0")
cd "$PLUGIN_DIR"

echo "🔄 Mise à jour vers la version $VERSION"

# 1. Mettre à jour le numéro de version dans le fichier principal
echo "📝 Mise à jour de la version dans gerfaut-companion.php..."
sed -i "s/Version: .*/Version: $VERSION/" gerfaut-companion.php
sed -i "s/define('GERFAUT_COMPANION_VERSION', '.*');/define('GERFAUT_COMPANION_VERSION', '$VERSION');/" gerfaut-companion.php

# 2. Créer le ZIP sans le token
echo "📦 Création du ZIP..."
cd ..
zip -r gerfaut-companion.zip gerfaut-companion-plugin/ \
    -x "gerfaut-companion-plugin/.git/*" \
    -x "gerfaut-companion-plugin/.github-token" \
    -x "gerfaut-companion-plugin/.gitignore" \
    -x "gerfaut-companion-plugin/composer.lock" \
    -x "gerfaut-companion-plugin/node_modules/*" \
    -x "gerfaut-companion-plugin/deploy.sh" \
    -q

cd gerfaut-companion-plugin

# 3. Commit et push
echo "💾 Commit des changements..."
git add .
git commit -m "Release v$VERSION - $DESCRIPTION" || echo "Rien à committer"
git tag -a "v$VERSION" -m "$DESCRIPTION"
git push origin main
git push origin "v$VERSION"

# 4. Créer la release GitHub avec le ZIP
echo "🚀 Création de la release GitHub..."

# Lire le token GitHub
GITHUB_TOKEN=$(cat .github-token 2>/dev/null | tr -d '\n')
if [ -z "$GITHUB_TOKEN" ]; then
    echo "❌ Token GitHub introuvable dans .github-token"
    exit 1
fi

REPO="arthurduino/Gerfaut-Companion"
ZIP_FILE="../gerfaut-companion.zip"

# Utiliser GitHub CLI si disponible, sinon l'API REST
if command -v gh &> /dev/null; then
    echo "   Utilisation de GitHub CLI..."
    gh release create "v$VERSION" "$ZIP_FILE" \
        --title "v$VERSION" \
        --notes "$DESCRIPTION" \
        --repo "$REPO"
else
    echo "   Utilisation de l'API GitHub..."
    
    # 1. Créer la release
    RELEASE_DATA=$(cat <<EOF
{
  "tag_name": "v$VERSION",
  "name": "v$VERSION",
  "body": "$DESCRIPTION",
  "draft": false,
  "prerelease": false
}
EOF
)
    
    RELEASE_RESPONSE=$(curl -s -X POST \
        -H "Authorization: token $GITHUB_TOKEN" \
        -H "Accept: application/vnd.github.v3+json" \
        -d "$RELEASE_DATA" \
        "https://api.github.com/repos/$REPO/releases")
    
    UPLOAD_URL=$(echo "$RELEASE_RESPONSE" | grep -o '"upload_url": "[^"]*' | cut -d'"' -f4 | sed 's/{?name,label}//')
    
    if [ -z "$UPLOAD_URL" ]; then
        echo "❌ Erreur lors de la création de la release"
        echo "$RELEASE_RESPONSE"
        exit 1
    fi
    
    # 2. Uploader le ZIP
    echo "   Upload du fichier ZIP..."
    curl -s -X POST \
        -H "Authorization: token $GITHUB_TOKEN" \
        -H "Content-Type: application/zip" \
        --data-binary @"$ZIP_FILE" \
        "${UPLOAD_URL}?name=gerfaut-companion.zip" > /dev/null
fi

echo "✅ Déploiement terminé !"
echo "   Version: $VERSION"
echo "   Release: https://github.com/$REPO/releases/tag/v$VERSION"
echo "   ZIP: $ZIP_FILE"
