# PHP File Explorer

A secure, feature-rich file browser and manager that runs in any browser via PHP's built-in server. This module is a core component of the PHP-Localhost project but can also be used independently.

## Overview

This application provides an enhanced directory listing, file viewing, and file management interface with the following features:

- **Directory Navigation**: Browse through directories with breadcrumb navigation
- **File Viewing**: View text files with syntax highlighting based on file type
- **File Management**: Delete files and directories with confirmation
- **Bulk Operations**: Select multiple files for download or deletion
- **Sorting & Filtering**: Sort by name, size, or date; filter by file type
- **Theme Support**: Toggle between dark and light mode with cookie persistence
- **File Download**: Download individual or multiple files
- **Binary File Handling**: Automatically distinguishes between text and binary files
- **Security Features**: Restricts navigation within the HOME directory with path sanitization
- **Permissions Display**: Shows file permissions and readability status
- **Comprehensive System Info**: Displays detailed PHP configuration and server details
- **Performance Optimization**: Limits maximum directory entries to prevent browser slowdown
- **Visual Feedback**: Grays out parent directory link when at home directory root
- **UI Optimizations**: Consistent button alignment, reduced spacing, smaller action icons
- **Symlink Navigation**: Proper handling of symbolic links with permission validation

## Security Features

- Path sanitization to prevent directory traversal attacks
- Restricted navigation to prevent accessing files outside HOME directory
- Proper content-type handling for file downloads and viewing
- File operation permission checks
- Input validation for all user-provided parameters
- Protection against directory deletion when not empty
- Home directory containment with visual feedback
- Secure handling of symlinks with group permission checks

## Usage

### Standalone Usage

Run the server with:

```bash
php -S localhost:8420 -t /path/to/directory
```

Then access the file explorer by opening a browser and navigating to:

```
http://localhost:8420
```

### Integration with PHP-Localhost

This module is automatically used when you access the root URL of a PHP-Localhost server instance. No additional configuration is required.

## Technical Details

- **Implementation**: Core functionality in `filesys.php`, entry point in `index.php`
- **Configuration**: Set `$allow_listing = false` to disable directory listing in production
- **Entry Limiting**: Set `MAX_PATH_ENTRIES` constant (default: 2000) to prevent browser slowdown
- **Cross-Platform Support**: Automatically detects user's HOME directory with cross-platform fallbacks
- **File Type Detection**: Uses MIME type detection and extension checking for proper handling
- **State Persistence**: Session-based persistence for sorting, filtering preferences
- **Theme Persistence**: Cookie-based persistence for dark/light theme preference
- **Responsive UI**: Bootstrap 5.3 with dark/light theming based on user preference
- **Advanced Filtering**: Filter by file types, including dot files, directories, and regular files
- **Smart Sorting**: Sort by name, size, or modification date while maintaining directory structure
- **Code Highlighting**: Uses highlight.js for syntax highlighting based on file extension
- **Bulk Operations**: Form-based bulk operations with proper validation and error handling
- **Optimized Layout**: Reduced container gutters, optimized action button spacing and sizing

## Dependencies

- Bootstrap 5.3
- Font Awesome 6.4
- Highlight.js for syntax highlighting
- PHP 8.3+ with fileinfo extension

## Architecture

The module follows a single-page application model where all operations are performed via GET and POST requests to the same script. Key components include:

1. **Path Resolution and Security**
   - Handles path navigation with symlink awareness
   - Prevents directory traversal attacks
   - Validates access permissions

2. **File Operations**
   - Viewing with syntax highlighting
   - Downloading with proper headers
   - Deletion with validation

3. **Directory Listing**
   - Sorting and filtering options
   - Icon selection based on file type
   - Permission display

4. **UI Components**
   - Bootstrap 5 integration
   - Theme switching
   - Responsive design

## Performance Considerations

- Directory listings are limited to MAX_PATH_ENTRIES (2000) entries by default
- MIME type detection may impact performance with large files
- Consider resource usage when handling large files or directories
- Symlink resolution may add overhead in complex directory structures

## Contributing

When contributing to this module, please follow the coding standards in CLAUDE.md:

- 2-space indentation
- PHP short tags `<?` except at file start (use `<?php`)
- Always use `<?=...?>` for simple output
- Follow PSR-12 standards
- Filter user inputs
- Sanitize output
- Check file operations for errors
- Use proper HTTP status codes

## License

This project is internal use only. No license is granted for external use or distribution.