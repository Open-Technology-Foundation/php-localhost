# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Structure

PHP-Localhost is a comprehensive bash utility for creating and managing PHP development servers with ease.

- `php-localhost` - Main bash script for starting PHP servers
- `php-localhost-screens` - Companion script for managing running PHP server instances
- `index.php` - Main PHP file browser interface 
- `sysman/filesys/filesys.php` - Core file browser implementation
- `sysman/filesys/index.php` - Entry point for file system module

## Build/Lint/Test Commands

- Run server: `./php-localhost [port] [directory]`
- Run with options: `./php-localhost --port 8080 --directory ./public --screen`
- Run server in background: `./php-localhost --bg`
- Run server with screen: `./php-localhost --screen`
- Manage servers: `./php-localhost-screens`
- Test PHP files: `php -l index.php`
- Lint shell scripts: `shellcheck php-localhost php-localhost-screens install.sh`
- Static analysis (PHP): `phpcs --standard=PSR12 index.php sysman/filesys/filesys.php`

## Key Architectural Components

1. **Bash Script Core (php-localhost):**
   - Command-line argument processing
   - PHP server process management
   - Port selection and validation
   - Directory detection
   - Browser integration
   - Server lifecycle management

2. **Management Interface (php-localhost-screens):**
   - Process discovery and listing
   - Interactive control menu
   - Process termination handling
   - Screen session management

3. **Web Interface (index.php, filesys.php):**
   - File system navigation and visualization
   - File content display with syntax highlighting
   - File operations (viewing, downloading, deleting)
   - System information display
   - Configuration management (sorting, filtering)

## Code Style

### Bash Scripts

- Use 2-space indentation
- Always set `-eEuo pipefail` for error handling
- Declare variables before use with explicit types when appropriate
- Use `[[` and `((` for conditionals and arithmetic operations
- Use lowercase for variable names, uppercase for constants
- Include trap handlers for clean signal handling
- Always check command availability with command -v
- Always end scripts with '\n#fin\n' to indicate the end of script

### PHP Code

- Use 2-space indentation
- Prefer PHP short tags (<? ... ?>) except at file start (use <?php)
- Always use <?=...?> for simple output; never <?php echo ...?>
- Follow PSR-12 coding standards
- Filter user inputs with filter_input() functions
- Sanitize output with htmlspecialchars() or similar
- Check file operations for errors
- Use realpath() for security validations
- Prevent directory traversal with path sanitization

### JavaScript

- Use ES6+ syntax and features
- Use 'use strict'
- Follow Bootstrap patterns for UI components
- Always sanitize dynamic content before insertion
- Use encodeURIComponent() for URL parameters

## Dependencies

- PHP CLI Server (8.3+)
- Bash (5.2+)
- Screen (for detached operation)
- lsof/netcat (for network service detection)
- Bootstrap 5.3 (UI framework)
- Highlight.js (syntax highlighting)
- FontAwesome (UI icons)

## Security Considerations

- The project is designed for local development use only
- The file browser interface has limited access controls
- Always validate paths to prevent directory traversal
- Use realpath() validation for file operations