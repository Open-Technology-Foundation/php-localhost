# PHP-Localhost

A bash utility script that creates and manages PHP development servers with ease.

## Features

- Start a PHP development server with a single command
- Multiple running modes: foreground, background, or screen session
- Automatic port collision detection and resolution
- Router file support for custom URL handling
- Comprehensive error handling and dependency checking
- Clean shutdown via signal trapping
- Smart directory detection (uses 'html' directory if available, otherwise uses current directory)
- Named parameter support with -p/--port and -d/--directory options

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

    php-localhost [-q] [-v] [-V] [-h] [-p PORT] [-d DIR] [--fg|--bg|--screen] [port] [dir] [mode]


### Parameters

Arguments can be in any order.

- `port` - Port number to use (default: 8000)
- `dir` - Directory to serve (defaults to 'html' if available, otherwise current directory)
- `mode` - Server run mode (default: fg)
  - `fg` - Run in foreground
  - `bg` - Run in background
  - `screen` - Run in screen session

### Options

- `-p, --port PORT` - Specify port number
- `-d, --directory DIR` - Specify directory to serve
- `--fg` - Run in foreground mode
- `--bg` - Run in background mode
- `--screen` - Run in screen session
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
php-localhost                     # Default: port=8000, dir=auto-detected, mode=fg
php-localhost 8001 html bg        # Run on port 8001 serving html in background
php-localhost . 8080              # Serve current directory on port 8080
php-localhost screen 8087 public  # Run in screen session on port 8087 serving public
php-localhost html screen 8000    # Serve html on port 8000 in screen session
php-localhost -q 8080 .           # Quiet mode, auto-kill existing servers on port 8080
php-localhost -p 8080 -d public   # Use named parameters
php-localhost --port 8080 --bg    # Run in background on port 8080 using current directory
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

