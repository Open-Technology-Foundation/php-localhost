# PHP-Localhost

A bash utility script that creates and manages PHP development servers with ease.

## Features

- Start a PHP development server with a single command
- Multiple running modes: foreground, background, or screen session
- Automatic port collision detection and resolution
- Router file support for custom URL handling
- Comprehensive error handling and dependency checking
- Clean shutdown via signal trapping

## Installation

1. Clone this repository
2. Make the script executable: `chmod +x php-localhost`
3. Optionally, add to your PATH

## Dependencies

- PHP (with CLI support)
- lsof (for process detection)
- netcat (for port checking)
- screen (optional, for screen mode)

## Usage

```bash
php-localhost [-q|-v] [-V] [-h] [port] [dir] [mode]
```

### Parameters

Can be specified in any order.

- `port` - Port number to use (default: 8000)
- `dir` - Directory to serve (default: html)
- `mode` - Server run mode (default: fg)
  - `fg` - Run in foreground
  - `bg` - Run in background
  - `screen` - Run in screen session

### Options

- `-q, --quiet` - Suppress output and auto-kill existing servers on the same port
- `-v, --verbose` - Show verbose output (default)
- `-V, --version` - Display version information
- `-h, --help` - Display the help message

## Router Support

If a file named `router.php` exists in the served directory, it will be used automatically to handle URL routing and custom request processing.

A router file allows you to:
- Handle URLs that don't match physical files
- Create custom API endpoints
- Define rewrite rules and redirects
- Implement custom 404 handlers

## Examples

```bash
php-localhost                    # Default: port=8000, dir=html, mode=fg
php-localhost 8001 html bg       # Run on port 8001 serving html in background
php-localhost . 8080             # Serve current directory on port 8080
php-localhost screen 8087 public # Run in screen on port 8087 serving dir public
php-localhost html screen 8000   # Serve html on port 8000 in screen session
php-localhost -q 8080 .          # Quiet mode, auto-kill existing servers on port 8080
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

