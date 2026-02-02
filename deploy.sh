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
if ! command -v gh &> /dev/null; then
    echo "⚠️  GitHub CLI (gh) n'est pas installé. Créez la release manuellement sur:"
    echo "   https://github.com/arthurduino/Gerfaut-Companion/releases/new"
    echo "   Tag: v$VERSION"
    echo "   Uploadez le fichier: ../gerfaut-companion.zip"
else
    gh release create "v$VERSION" ../gerfaut-companion.zip \
        --title "v$VERSION" \
        --notes "$DESCRIPTION" \
        --repo arthurduino/Gerfaut-Companion
fi

echo "✅ Déploiement terminé !"
echo "   Version: $VERSION"
echo "   ZIP: ../gerfaut-companion.zip"
