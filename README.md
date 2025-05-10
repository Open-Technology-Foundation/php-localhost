# PHP-Localhost

A bash utility for creating and managing PHP development servers with ease.

## Features

- Start a PHP development server with a single command
- Auto-selects random available port from configurable range (8100-8999 by default)
- Auto-detects common web directories (html, public, www, httpdocs, htdocs, webroot, web, wwwroot)
- Multiple running modes: foreground, background, or screen session
- Automatic port collision detection and resolution
- Router file support for custom URL handling
- Auto-detects and launches browser in both GUI and terminal environments
- Interactive management of running servers via companion script
- Comprehensive error handling and dependency checking
- Clean shutdown via signal trapping
- Named parameter support with intuitive options

## Installation

1. Clone this repository
2. Make the scripts executable: `chmod +x php-localhost php-localhost-screens`
3. Optionally, add to your PATH or run the included install.sh script

## Dependencies

- PHP (with CLI support)
- lsof (for process detection)
- netcat (for port checking)
- screen (optional, for screen mode)
- A web browser (optional, for the -x/--execute option)

## Usage

```
php-localhost [-q] [-v] [-V] [-h] [-p PORT] [-d DIR] [-i MIN] [-a MAX] [-x] [--fg|--bg|--screen] [port] [dir] [mode]
```

### Parameters

Arguments can be in any order.

- `port` - Port number to use (default: auto-assign from port range 8100-8999)
- `dir` - Directory to serve (auto-detects common web directories)
- `mode` - Server run mode (default: fg)
  - `fg` - Run in foreground
  - `bg` - Run in background
  - `screen` - Run in screen session

### Options

- `-p, --port PORT` - Specify port number (0 = auto-assign from port range)
- `-d, --directory DIR` - Specify directory to serve
- `-i, --minport MIN` - Set minimum port for auto-assignment (default: 8100)
- `-a, --maxport MAX` - Set maximum port for auto-assignment (default: 8999)
- `--fg` - Run in foreground mode
- `--bg` - Run in background mode
- `--screen` - Run in screen session
- `-x, --execute` - Open URL in browser when server starts
- `-q, --quiet` - Suppress output and auto-kill existing servers on the same port
- `-v, --verbose` - Show verbose output (default)
- `-V, --version` - Display version information
- `-h, --help` - Display the help message

## Management

Use the companion script `php-localhost-screens` to:
- List all running PHP server instances (both standalone and screen sessions)
- Interactively select servers by PID
- Kill servers or attach to screen sessions
- Auto-refresh the server list

Simply run `php-localhost-screens` without arguments for an interactive menu.

## Router Support

If a file named `router.php` exists in the served directory, it will be used automatically to handle URL routing and custom request processing.

A router file allows you to:
- Handle URLs that don't match physical files
- Create custom API endpoints
- Define rewrite rules and redirects
- Implement custom 404 handlers

## Browser Integration

When using the `-x/--execute` flag, the script will:
- Attempt to detect the default browser in GUI environments using:
  - xdg-settings
  - xdg-mime
  - update-alternatives
  - $BROWSER environment variable
  - Common browser executables
- In terminal-only environments, it will try to find terminal browsers:
  - w3m, lynx, elinks, links
- Launch the detected browser with the localhost URL

## Web Interface

The project also includes a PHP file browser interface that:
- Provides directory navigation with breadcrumb trails
- Offers file viewing with syntax highlighting for common file types
- Supports file downloads
- Includes file/directory management features
- Displays system information
- Supports dark/light theme switching

Access this interface by navigating to the server URL in any browser.

## Examples

```bash
# Auto-detect port and directory, run in foreground
php-localhost

# Run on port 8001 serving html/ in background
php-localhost 8001 html bg

# Set custom port range (1024-2048) and auto-select
php-localhost -i 1024 -a 2048

# Serve current directory on port 8086
php-localhost . 8086

# Screen session on port 8087 serving public/
php-localhost screen 8087 public

# Quiet mode, auto-kill existing servers on port 8080
php-localhost -q 8080 .

# Run in background with auto-port and open browser
php-localhost --bg -x

# List and manage running PHP servers
php-localhost-screens
```

## Current Version

Version 1.0.431

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.