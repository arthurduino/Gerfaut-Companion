#!/bin/bash

# Gerfaut Companion v2.0.0 - Installation & Deployment Script
# Automatically installs the plugin to a WordPress installation

set -e

# Configuration
WORDPRESS_PATH="${1:-.}"
PLUGIN_URL="${2:-https://releases.gerfaut.mooo.com/gerfaut-companion-2.0.0.zip}"

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║    Gerfaut Companion v2.0.0 - Installation & Deployment               ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Check WordPress installation
echo -e "${YELLOW}📋 Checking WordPress installation...${NC}"
if [ ! -f "$WORDPRESS_PATH/wp-config.php" ]; then
    echo -e "${RED}✗ WordPress not found at $WORDPRESS_PATH${NC}"
    echo ""
    echo "Usage:"
    echo "  $0 /path/to/wordpress [plugin-zip-url]"
    echo ""
    echo "Examples:"
    echo "  $0 /var/www/html"
    echo "  $0 /home/user/wordpress https://releases.gerfaut.mooo.com/gerfaut-companion-2.0.0.zip"
    exit 1
fi

PLUGINS_DIR="$WORDPRESS_PATH/wp-content/plugins"
PLUGIN_DIR="$PLUGINS_DIR/gerfaut-companion"

echo -e "${GREEN}✓ WordPress found at $WORDPRESS_PATH${NC}"
echo ""

# Check if plugin already exists
if [ -d "$PLUGIN_DIR" ]; then
    echo -e "${YELLOW}⚠ Plugin already exists at $PLUGIN_DIR${NC}"
    read -p "Do you want to update it? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}📦 Backing up existing plugin...${NC}"
        BACKUP_DIR="$PLUGIN_DIR.backup.$(date +%Y%m%d_%H%M%S)"
        mv "$PLUGIN_DIR" "$BACKUP_DIR"
        echo -e "${GREEN}✓ Backup created at $BACKUP_DIR${NC}"
    else
        echo -e "${RED}Installation cancelled.${NC}"
        exit 0
    fi
fi

echo ""
echo -e "${YELLOW}📥 Downloading Gerfaut Companion...${NC}"

# Create temp directory
TEMP_DIR=$(mktemp -d)
ZIP_FILE="$TEMP_DIR/gerfaut-companion.zip"

# Download plugin
if command -v wget &> /dev/null; then
    wget -q "$PLUGIN_URL" -O "$ZIP_FILE"
elif command -v curl &> /dev/null; then
    curl -sL "$PLUGIN_URL" -o "$ZIP_FILE"
else
    echo -e "${RED}✗ Neither wget nor curl found. Cannot download plugin.${NC}"
    exit 1
fi

if [ ! -f "$ZIP_FILE" ]; then
    echo -e "${RED}✗ Failed to download plugin${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Downloaded successfully${NC}"
echo ""

# Extract plugin
echo -e "${YELLOW}📦 Extracting plugin...${NC}"

if command -v unzip &> /dev/null; then
    unzip -q "$ZIP_FILE" -d "$TEMP_DIR"
elif command -v tar &> /dev/null; then
    tar -xzf "$ZIP_FILE" -C "$TEMP_DIR"
else
    echo -e "${RED}✗ No extraction tool found (unzip or tar required)${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Extracted${NC}"
echo ""

# Move plugin to WordPress
echo -e "${YELLOW}📂 Installing plugin...${NC}"

if [ -d "$TEMP_DIR/gerfaut-companion" ]; then
    mv "$TEMP_DIR/gerfaut-companion" "$PLUGIN_DIR"
else
    echo -e "${RED}✗ Plugin directory not found in archive${NC}"
    exit 1
fi

# Set permissions
chmod -R 755 "$PLUGIN_DIR"
chown -R www-data:www-data "$PLUGIN_DIR" 2>/dev/null || true

echo -e "${GREEN}✓ Plugin installed at $PLUGIN_DIR${NC}"
echo ""

# Clean up
rm -rf "$TEMP_DIR"

# Activation instructions
echo -e "${GREEN}╔════════════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║           ✅ Installation Complete!                                    ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════════════════╝${NC}"
echo ""

echo -e "${BLUE}📚 Next Steps:${NC}"
echo ""
echo "1. Go to WordPress Admin Dashboard"
echo "   https://your-site.com/wp-admin"
echo ""
echo "2. Navigate to Plugins"
echo "   Plugins > Installed Plugins"
echo ""
echo "3. Find 'Gerfaut Companion' and click 'Activate'"
echo ""
echo "4. Configure the plugin"
echo "   Admin Menu > Gerfaut > Connexion Gerfaut"
echo ""
echo "5. Set Gerfaut URL"
echo "   https://gerfaut.mooo.com"
echo ""
echo "6. Click 'Connect to Gerfaut' and authorize"
echo ""
echo -e "${BLUE}📚 Documentation:${NC}"
echo "  • Plugin README: $(find $PLUGIN_DIR -name "README.md" -type f 2>/dev/null | head -1)"
echo "  • Release Notes: $(find $PLUGIN_DIR -name "RELEASE.md" -type f 2>/dev/null | head -1)"
echo ""

echo -e "${GREEN}🎉 Gerfaut Companion v2.0.0 is ready to use!${NC}"
echo ""

# Check if WP-CLI is available for activation
if command -v wp &> /dev/null; then
    read -p "Do you want to activate the plugin now via WP-CLI? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${YELLOW}🔌 Activating plugin...${NC}"
        wp plugin activate gerfaut-companion --path="$WORDPRESS_PATH"
        echo -e "${GREEN}✓ Plugin activated${NC}"
    fi
fi

echo ""
