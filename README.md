# PHP-Localhost

A bash utility for creating and managing PHP development servers with ease.

## Features

- Start a PHP development server with a single command
- Auto-selects random available port from configurable range
- Auto-detects common web directories (html, public, www, etc.)
- Multiple running modes: foreground, background, or screen session
- Automatic port collision detection and resolution
- Router file support for custom URL handling
- Open website in default browser with a single command
- Interactive management of running servers via companion script
- Comprehensive error handling and dependency checking
- Clean shutdown via signal trapping
- Named parameter support with intuitive options

## Installation

1. Clone this repository
2. Make the scripts executable: `chmod +x php-localhost php-localhost-screens`
3. Optionally, add to your PATH

## Dependencies

- PHP (with CLI support)
- lsof (for process detection)
- netcat (for port checking)
- screen (optional, for screen mode)

## Usage

    php-localhost [-q] [-v] [-V] [-h] [-p PORT] [-d DIR] [-i MIN] [-a MAX] [-x] [--fg|--bg|--screen] [port] [dir] [mode]


### Parameters

Arguments can be in any order.

- `port` - Port number to use (default: auto-assign from port range)
- `dir` - Directory to serve (auto-detects html/public/www folders)
- `mode` - Server run mode (default: fg)
  - `fg` - Run in foreground
  - `bg` - Run in background
  - `screen` - Run in screen session

### Options

- `-p, --port PORT` - Specify port number (0 = auto-assign from port range)
- `-d, --directory DIR` - Specify directory to serve
- `-i, --minport MIN` - Set minimum port range for auto-assignment (default: 8100)
- `-a, --maxport MAX` - Set maximum port range for auto-assignment (default: 8999)
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
- List all running PHP server instances
- Interactively manage (view/kill) running servers
- Manage screen sessions for PHP servers

## Router Support

If a file named `router.php` exists in the served directory, it will be used automatically to handle URL routing and custom request processing.

A router file allows you to:
- Handle URLs that don't match physical files
- Create custom API endpoints
- Define rewrite rules and redirects
- Implement custom 404 handlers

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

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

