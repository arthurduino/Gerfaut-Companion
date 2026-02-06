#!/bin/bash

# Gerfaut Companion v2.0.0 - Build & Release Script
# Creates distributable ZIP archive

set -e

PLUGIN_DIR="/home/gerfaut.mooo.com/public_html/gerfaut-companion-plugin"
PLUGIN_NAME="gerfaut-companion"
VERSION="2.0.0"
BUILD_DIR="/tmp/gerfaut-build"
RELEASE_DIR="/home/gerfaut.mooo.com/public_html/releases"

echo "╔════════════════════════════════════════════════════════════════════════╗"
echo "║          Gerfaut Companion v$VERSION - Build & Release                   ║"
echo "╚════════════════════════════════════════════════════════════════════════╝"
echo ""

# Create release directory
mkdir -p "$RELEASE_DIR"

# Clean build directory
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

echo "📦 Building plugin package..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Copy plugin files (excluding unnecessary files)
echo "📋 Copying plugin files..."
cp -r "$PLUGIN_DIR" "$BUILD_DIR/$PLUGIN_NAME"

# Clean up unnecessary files
cd "$BUILD_DIR/$PLUGIN_NAME"

echo "🧹 Cleaning up..."

# Remove build/dev files
rm -f deploy.sh sync-sav.php UPDATE.md RELEASE.md

# Remove git files
rm -rf .git .gitignore

# Remove node_modules if present
rm -rf node_modules

# Create a clean vendor if exists
if [ -d "vendor" ]; then
    echo "✓ Vendor directory included"
fi

# Create build info
cat > BUILD_INFO.txt << EOF
Gerfaut Companion Plugin v$VERSION
Built: $(date)
Status: Release

Installation:
1. Upload this ZIP to WordPress Plugins directory
2. Or extract to wp-content/plugins/gerfaut-companion/
3. Activate plugin from WordPress Admin

For setup instructions, see README.md
EOF

# Back to release directory
cd "$RELEASE_DIR"

# Create ZIP archive
ZIP_FILE="$RELEASE_DIR/${PLUGIN_NAME}-${VERSION}.zip"

echo "📦 Creating ZIP archive..."
cd "$BUILD_DIR"
zip -r "$ZIP_FILE" "$PLUGIN_NAME" -q

# Calculate size
SIZE=$(du -h "$ZIP_FILE" | cut -f1)

echo ""
echo "✅ Package created successfully!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📦 Release Package Information"
echo "───────────────────────────────────────────────────────────────────────"
echo "Plugin Name:    Gerfaut Companion"
echo "Version:        $VERSION"
echo "File:           $ZIP_FILE"
echo "Size:           $SIZE"
echo "Created:        $(date)"
echo ""

# Generate checksums
echo "🔐 Generating checksums..."
cd "$RELEASE_DIR"

SHA256=$(sha256sum "$ZIP_FILE" | awk '{print $1}')
MD5=$(md5sum "$ZIP_FILE" | awk '{print $1}')

echo "SHA256: $SHA256"
echo "MD5:    $MD5"
echo ""

# Create checksum file
cat > "${PLUGIN_NAME}-${VERSION}.sha256" << EOF
$SHA256  $ZIP_FILE
EOF

# Create release info
cat > "${PLUGIN_NAME}-${VERSION}.txt" << EOF
Gerfaut Companion Plugin - Release Information
===============================================

Version: $VERSION
Released: $(date)
Status: Stable

File: ${PLUGIN_NAME}-${VERSION}.zip
Size: $SIZE
SHA256: $SHA256
MD5: $MD5

📋 Requirements
───────────────────────────────────────────────────────────
- WordPress 5.8+
- PHP 7.4+
- WooCommerce 5.0+
- Gerfaut 2.0+ (with OAuth2)

✨ New Features
───────────────────────────────────────────────────────────
✅ OAuth2 Authentication
✅ Bidirectional Communication
✅ Automatic Order Syncing
✅ Webhook Support
✅ Enhanced Admin UI

🔒 Security
───────────────────────────────────────────────────────────
✅ OAuth2 Token Management
✅ HMAC-SHA256 Webhook Signatures
✅ Protected API Endpoints
✅ Secure Token Storage

✅ Compatibility
───────────────────────────────────────────────────────────
✅ 100% backward compatible with v1.x
✅ No data loss
✅ Gradual migration path

🚀 Installation
───────────────────────────────────────────────────────────
1. Extract ZIP file
2. Upload to wp-content/plugins/
3. Activate from WordPress Admin
4. Go to WordPress Admin > Gerfaut > Connexion Gerfaut
5. Click "🔗 Connect to Gerfaut"
6. Authorize OAuth2 connection

📚 Documentation
───────────────────────────────────────────────────────────
- README.md - Full documentation
- RELEASE.md - Release notes
- See Gerfaut documentation for OAuth2 setup

🔗 Links
───────────────────────────────────────────────────────────
GitHub: https://github.com/arthurduino/Gerfaut-Companion
Gerfaut: https://gerfaut.mooo.com

📝 License
───────────────────────────────────────────────────────────
GNU General Public License v2.0 or later

EOF

# Clean up build directory
rm -rf "$BUILD_DIR"

echo "✅ Release files created:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
ls -lh "$RELEASE_DIR" | grep -E "(${PLUGIN_NAME}-${VERSION}|Build)" || true

echo ""
echo "📤 Ready for distribution!"
echo ""
echo "Files to distribute:"
echo "  1. $ZIP_FILE (main plugin)"
echo "  2. ${PLUGIN_NAME}-${VERSION}.txt (info)"
echo "  3. ${PLUGIN_NAME}-${VERSION}.sha256 (checksum)"
echo ""

echo "✨ Release v$VERSION is ready for production!"
echo ""
