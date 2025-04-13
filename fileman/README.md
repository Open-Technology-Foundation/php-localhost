# PHP File Explorer

A secure, feature-rich file browser and manager that runs in any browser via PHP's built-in server.

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

## Security Features

- Path sanitization to prevent directory traversal attacks
- Restricted navigation to prevent accessing files outside HOME directory
- Proper content-type handling for file downloads and viewing
- File operation permission checks
- Input validation for all user-provided parameters
- Protection against directory deletion when not empty
- Home directory containment with visual feedback

## Usage

Run the server with:

```bash
php -S localhost:8420 -t /path/to/directory
```

Then access the file explorer by opening a browser and navigating to:

```
http://localhost:8420
```

## Technical Details

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

## Contributing

When contributing to this project, please follow the coding standards in CLAUDE.md.

## License

This project is internal use only. No license is granted for external use or distribution.