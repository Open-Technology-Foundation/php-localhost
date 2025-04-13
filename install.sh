#!/bin/bash
set -euo pipefail

# PHP-Localhost Installer
# This script installs php-localhost and all required dependencies
# for Ubuntu/Debian-based Linux distributions

echo "PHP-Localhost Installer"
echo "======================="
echo

# Check if running as root
if [ "$(id -u)" -ne 0 ]; then
  echo "This script must be run as root or with sudo."
  exit 1
fi

# Define installation directory
INSTALL_DIR="/usr/local/bin"

# Check and install dependencies
echo "Checking and installing dependencies..."

# Install PHP and other dependencies
apt-get update
apt-get install -y php-cli php-common lsof netcat screen

# Install essential PHP modules
echo "Installing essential PHP modules..."
apt-get install -y \
    php-curl \
    php-json \
    php-mbstring \
    php-xml \
    php-zip \
    php-gd \
    php-mysql \
    php-sqlite3 \
    php-pgsql \
    php-xdebug \
    php-intl

# Ask about installing additional development tools
echo
echo "Would you like to install additional development tools? (y/n)"
read -r install_dev_tools

if [[ "$install_dev_tools" =~ ^[Yy]$ ]]; then
    echo "Installing additional development tools..."
    apt-get install -y \
        composer \
        git \
        mariadb-client \
        sqlite3 \
        php-pear \
        phpunit \
        php-dev
    
    # Install browser(s) if not already installed
    if ! command -v firefox >/dev/null && ! command -v chromium-browser >/dev/null && ! command -v google-chrome >/dev/null; then
        echo "Would you like to install Firefox browser? (y/n)"
        read -r install_browser
        if [[ "$install_browser" =~ ^[Yy]$ ]]; then
            apt-get install -y firefox
        fi
    fi
fi

# Verify PHP installation
PHP_VERSION=$(php -r 'echo PHP_VERSION;' 2>/dev/null) || {
  echo "Error: PHP installation failed. Please check for errors above."
  exit 1
}
echo "PHP $PHP_VERSION installed successfully."

# Copy scripts to installation directory
echo "Installing php-localhost scripts to $INSTALL_DIR..."
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" &>/dev/null && pwd)"

# Create symlinks in /usr/local/bin
ln -sf "$SCRIPT_DIR/php-localhost" "$INSTALL_DIR/php-localhost"
ln -sf "$SCRIPT_DIR/php-localhost-screens" "$INSTALL_DIR/php-localhost-screens"
chmod +x "$SCRIPT_DIR/php-localhost" "$SCRIPT_DIR/php-localhost-screens"

echo "Symlinks created in $INSTALL_DIR"

# Ask if user wants actual copies instead of symlinks
echo "Would you like to make actual copies instead of symlinks? (y/n)"
read -r use_copies

if [[ "$use_copies" =~ ^[Yy]$ ]]; then
  # Remove symlinks and copy files
  rm -f "$INSTALL_DIR/php-localhost" "$INSTALL_DIR/php-localhost-screens"
  cp "$SCRIPT_DIR/php-localhost" "$INSTALL_DIR/php-localhost"
  cp "$SCRIPT_DIR/php-localhost-screens" "$INSTALL_DIR/php-localhost-screens"
  chmod +x "$INSTALL_DIR/php-localhost" "$INSTALL_DIR/php-localhost-screens"
  echo "Files copied to $INSTALL_DIR"
fi

# Create desktop shortcut for all users
echo "Would you like to create a desktop shortcut for all users? (y/n)"
read -r create_shortcut

if [[ "$create_shortcut" =~ ^[Yy]$ ]]; then
  DESKTOP_FILE="/usr/share/applications/php-localhost.desktop"
  
  # Create desktop entry file
  cat > "$DESKTOP_FILE" << EOF
[Desktop Entry]
Name=PHP Localhost
Comment=Start PHP development server
Exec=x-terminal-emulator -e bash -c "php-localhost; echo 'Press Enter to close'; read"
Icon=terminal
Terminal=false
Type=Application
Categories=Development;WebDevelopment;
Keywords=PHP;Web;Development;Server;
EOF

  chmod 644 "$DESKTOP_FILE"
  echo "Desktop shortcut created at: $DESKTOP_FILE"
fi

# Display installed PHP modules
echo
echo "Installed PHP modules:"
php -m | sort

# Check for common frameworks and recommendations
echo
echo "Checking for common PHP frameworks and tools..."

if [ -f /usr/bin/composer ]; then
    echo "✓ Composer is installed. You can use it to install frameworks like Laravel, Symfony, etc."
    echo "  Example: composer create-project laravel/laravel my-project"
else
    echo "✗ Composer is not installed. It's recommended for PHP dependency management."
fi

if [ -f /usr/bin/phpunit ]; then
    echo "✓ PHPUnit is installed. You can use it for testing PHP applications."
else
    echo "✗ PHPUnit is not installed. It's useful for PHP testing."
fi

# Setup complete
echo
echo "PHP-Localhost installation complete!"
echo

# PHP info
PHP_VERSION=$(php -r 'echo PHP_VERSION;')
echo "PHP Version: $PHP_VERSION"
echo

echo "Usage examples:"
echo "  php-localhost                     # Start server with auto-detected settings"
echo "  php-localhost 8080 ~/projects/web # Start server on port 8080 for specific folder"
echo "  php-localhost --bg -x             # Start server in background and open in browser"
echo "  php-localhost-screens             # Manage running servers"
echo
echo "For more options, run:"
echo "  php-localhost --help"
echo

# Offer to start the server for a non-root user
SUDO_USER=${SUDO_USER:-}

if [ -n "$SUDO_USER" ]; then
  echo "Would you like to start the PHP server now as user $SUDO_USER? (y/n)"
  read -r start_server

  if [[ "$start_server" =~ ^[Yy]$ ]]; then
    echo "Starting PHP server..."
    
    # Prompt for directory to serve
    echo "Enter the directory to serve (leave empty for auto-detection):"
    read -r serve_dir
    
    # Run the server as the original user
    if [ -n "$serve_dir" ]; then
      su - "$SUDO_USER" -c "php-localhost -x '$serve_dir'"
    else
      su - "$SUDO_USER" -c "php-localhost -x"
    fi
  fi
fi

exit 0