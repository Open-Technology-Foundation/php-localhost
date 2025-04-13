<?php
// Start session for state persistence (sorting, filtering)
session_start();

// Enhanced directory listing script with directory navigation, file viewing, and file management
// Includes sorting, filtering, selection and bulk operations
// Set this to false in production to prevent directory listing
$allow_listing = true;

// Maximum number of directory entries to display
define('MAX_PATH_ENTRIES', 2000);

// Get theme from cookie or set default theme (dark)
$theme = isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'dark';

// Toggle theme if requested
if (isset($_GET['toggle_theme'])) {
  $theme = ($theme === 'dark') ? 'light' : 'dark';
  setcookie('theme', $theme, time() + (86400 * 30), "/"); // Cookie expires in 30 days
}

// Set up sorting parameters
$sort_columns = ['name', 'size', 'modified'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $sort_columns) ? $_GET['sort'] : 'name';
$sort_order = isset($_GET['order']) && in_array($_GET['order'], ['asc', 'desc']) ? $_GET['order'] : 'asc';

// Store sort state in session
if (isset($_GET['sort']) || isset($_GET['order'])) {
  $_SESSION['sort_column'] = $sort_column;
  $_SESSION['sort_order'] = $sort_order;
} else if (isset($_SESSION['sort_column']) && isset($_SESSION['sort_order'])) {
  // Retrieve sort state from session
  $sort_column = $_SESSION['sort_column'];
  $sort_order = $_SESSION['sort_order'];
}

// Set up filter parameters with defaults (.dot files hidden by default)
$hide_dotfiles = isset($_GET['hide_dotfiles']) ? filter_var($_GET['hide_dotfiles'], FILTER_VALIDATE_BOOLEAN) : 
                (isset($_SESSION['hide_dotfiles']) ? $_SESSION['hide_dotfiles'] : true);
$hide_dirs = isset($_GET['hide_dirs']) ? filter_var($_GET['hide_dirs'], FILTER_VALIDATE_BOOLEAN) : 
            (isset($_SESSION['hide_dirs']) ? $_SESSION['hide_dirs'] : false);
$hide_files = isset($_GET['hide_files']) ? filter_var($_GET['hide_files'], FILTER_VALIDATE_BOOLEAN) : 
             (isset($_SESSION['hide_files']) ? $_SESSION['hide_files'] : false);

// Store filter state in session
$_SESSION['hide_dotfiles'] = $hide_dotfiles;
$_SESSION['hide_dirs'] = $hide_dirs;
$_SESSION['hide_files'] = $hide_files;

// Function to generate sort URL (preserves current filters)
function getSortUrl($column, $current_column, $current_order, $current_dir) {
  global $hide_dotfiles, $hide_dirs, $hide_files;
  
  $new_order = ($column === $current_column && $current_order === 'asc') ? 'desc' : 'asc';
  $params = [
    'sort' => $column,
    'order' => $new_order,
    'hide_dotfiles' => $hide_dotfiles ? '1' : '0',
    'hide_dirs' => $hide_dirs ? '1' : '0',
    'hide_files' => $hide_files ? '1' : '0'
  ];
  if ($current_dir) {
    $params['dir'] = $current_dir;
  }
  return '?' . http_build_query($params);
}

// Function to generate filter URLs (preserves current sort and other filters)
function getFilterUrl($filter_name, $current_value, $current_dir) {
  global $hide_dotfiles, $hide_dirs, $hide_files, $sort_column, $sort_order;
  
  $params = [
    'sort' => $sort_column,
    'order' => $sort_order,
    $filter_name => $current_value ? '0' : '1'  // Toggle the current value
  ];
  
  // Add all other filter states
  if ($filter_name !== 'hide_dotfiles') {
    $params['hide_dotfiles'] = $hide_dotfiles ? '1' : '0';
  }
  if ($filter_name !== 'hide_dirs') {
    $params['hide_dirs'] = $hide_dirs ? '1' : '0';
  }
  if ($filter_name !== 'hide_files') {
    $params['hide_files'] = $hide_files ? '1' : '0';
  }
  
  if ($current_dir) {
    $params['dir'] = $current_dir;
  }
  
  return '?' . http_build_query($params);
}

// Function to get sort icon
function getSortIcon($column, $current_column, $current_order) {
  if ($column !== $current_column) {
    return '<i class="fas fa-sort text-muted"></i>';
  }
  return $current_order === 'asc' ? 
    '<i class="fas fa-sort-up"></i>' : 
    '<i class="fas fa-sort-down"></i>';
}

// Get user's HOME directory - cross-platform detection
$home_dir = getenv('HOME');
if (empty($home_dir)) {
  // Fallbacks for different environments
  $home_dir = getenv('USERPROFILE'); // Windows alternative
  if (empty($home_dir)) {
    $home_dir = posix_getpwuid(posix_geteuid())['dir'] ?? $_SERVER['DOCUMENT_ROOT'];
  }
}

// Make sure home_dir exists and is readable
if (!is_dir($home_dir) || !is_readable($home_dir)) {
  $home_dir = $_SERVER['DOCUMENT_ROOT'];
}

// Basic security: Restrict navigation to within HOME directory
$current_dir = isset($_GET['dir']) ? $_GET['dir'] : '';

// Sanitize the current_dir input to prevent directory traversal
$current_dir = str_replace(['../', '..\\', './', '.\\'], '', $current_dir);

// CRITICAL: The main issue with symlink navigation is that realpath() resolves symlinks to their targets
// which breaks relative path navigation. Let's completely rewrite this section.

// First check if the path exists physically as provided (without resolving symlinks)
$physical_path = $home_dir;
if ($current_dir) {
  $physical_path = $home_dir . '/' . $current_dir;
}

// Start with the original paths for debugging
$original_path = $physical_path;
$is_symlink_path = false;

// Log the path we're checking for debugging
error_log("Checking path: " . $physical_path);

// Special handling for symlink paths
if (is_link($physical_path)) {
  $is_symlink_path = true;
  $symlink_target = readlink($physical_path);
  error_log("Found symlink, target: " . $symlink_target);
  
  // If it's a relative symlink, we need to resolve it relative to its location
  if ($symlink_target[0] !== '/') {
    $symlink_dir = dirname($physical_path);
    $symlink_target = $symlink_dir . '/' . $symlink_target;
    error_log("Relative symlink, resolved to: " . $symlink_target);
  }
  
  // Use the symlink target for further checks
  $target_dir = $symlink_target;
  
  // Check if the target exists and is a directory
  if (!file_exists($target_dir) || !is_dir($target_dir)) {
    error_log("Symlink target doesn't exist or isn't a directory: " . $target_dir);
    $target_dir = $home_dir;
    $current_dir = '';
  }
} else {
  // For regular directories, use the physical path
  $target_dir = $physical_path;
  
  // Make sure it exists and is a directory
  if (!file_exists($target_dir) || !is_dir($target_dir)) {
    error_log("Path doesn't exist or isn't a directory: " . $target_dir);
    $target_dir = $home_dir;
    $current_dir = '';
  }
}

// Security check - restrict access to paths outside home or system directories
// We need to ensure both the symlink and its target are secure
if (!$current_dir) {
  // Home directory is always allowed
  // No extra checks needed
} else if (strpos($target_dir, $home_dir) !== 0) {
  // For targets outside home, do advanced permission checks
  $dir_stats = @stat($target_dir);
  $access_allowed = false;
  
  if ($dir_stats && function_exists('posix_getgrgid') && function_exists('posix_getgroups')) {
    // Get directory's group
    $dir_group = $dir_stats['gid'];
    
    // Get current user's groups
    $user_groups = posix_getgroups();
    
    // Check if user is in the directory's group and group has read permissions
    $group_has_read = ($dir_stats['mode'] & 0040) > 0; // Check group read bit
    $user_in_group = in_array($dir_group, $user_groups);
    
    if ($group_has_read && $user_in_group) {
      // Allow access if user has group permissions
      $access_allowed = true;
      error_log("Access allowed to symlink target via group permissions");
    }
  }
  
  if (!$access_allowed) {
    // Fallback to home directory for security
    error_log("Access denied to path outside home: " . $target_dir);
    $target_dir = $home_dir;
    $current_dir = '';
  }
}

// For symlinks, maintain the current_dir from the URL instead of recalculating
// This is crucial for proper navigation within symlinked paths
if (!$is_symlink_path) {
  // Get the relative path for display (only for non-symlink paths)
  if ($target_dir == $home_dir) {
    $current_dir = '';
  } else {
    // Only update current_dir if it's not a symlink path
    // We use the relative path from the home directory
    if (strpos($target_dir, $home_dir) === 0) {
      $current_dir = substr($target_dir, strlen($home_dir) + 1);
    }
    // For paths outside home (allowed via group permissions), keep current_dir as is
  }
}

// Add to debug information
$debug_info = [
  'detected_home' => $home_dir,
  'target_dir' => $target_dir,
  'original_path' => $original_path,
  'physical_path' => $physical_path,
  'current_dir' => $current_dir,
  'is_symlink_path' => $is_symlink_path ? 'Yes' : 'No',
  'is_readable' => is_readable($target_dir) ? 'Yes' : 'No',
  'is_dir' => is_dir($target_dir) ? 'Yes' : 'No'
];

// Function to format file size (bytes to readable format)
function formatFileSize($bytes) {
  if ($bytes >= 1073741824) {
    return number_format($bytes / 1073741824, 2) . ' GB';
  } elseif ($bytes >= 1048576) {
    return number_format($bytes / 1048576, 2) . ' MB';
  } elseif ($bytes >= 1024) {
    return number_format($bytes / 1024, 2) . ' KB';
  } else {
    return $bytes . ' bytes';
  }
}

// Function to get emoji icon based on file/directory type
function getIcon($path) {
  if (is_link($path) && is_dir($path)) {
    return '🔗📁';
  } else if (is_dir($path)) {
    return '📁';
  } else if (is_link($path)) {
    return '🔗';
  } else {
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    switch ($ext) {
      case 'php':
        return '🐘';
      case 'html':
      case 'htm':
        return '🌐';
      case 'jpg':
      case 'jpeg':
      case 'png':
      case 'gif':
        return '🖼️';
      case 'pdf':
        return '📄';
      case 'zip':
      case 'rar':
      case 'gz':
      case 'tar':
        return '🗜️';
      case 'txt':
      case 'md':
        return '📝';
      case 'mp3':
      case 'wav':
      case 'ogg':
        return '🎵';
      case 'mp4':
      case 'avi':
      case 'mkv':
        return '🎬';
      default:
        return '📄';
    }
  }
}

// Function to determine if a file is binary
function isBinaryFile($file) {
  $finfo = new finfo(FILEINFO_MIME);
  $mime = $finfo->file($file);

  // List of text MIME types
  $text_mimes = [
    'text/plain', 'text/html', 'text/css', 'text/javascript', 'text/xml',
    'application/json', 'application/xml', 'application/javascript',
    'application/x-httpd-php', 'application/x-sh'
  ];

  // Check if the MIME type contains 'text/' or is in our text_mimes list
  foreach ($text_mimes as $text_mime) {
    if (strpos($mime, $text_mime) !== false) {
      return false;
    }
  }

  // Additional check for common text file extensions
  $text_extensions = ['txt', 'html', 'htm', 'css', 'js', 'json', 'xml', 'md', 'csv', 'log', 'php', 'sh', 'py', 'rb', 'c', 'cpp', 'h', 'java'];
  $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

  if (in_array($ext, $text_extensions)) {
    return false;
  }

  // If we can't determine for sure, check the first 1000 bytes for null bytes
  $handle = fopen($file, 'r');
  $block = fread($handle, 1000);
  fclose($handle);

  return strpos($block, "\0") !== false;
}

// Function to get MIME type
function getMimeType($file) {
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  return $finfo->file($file);
}

// Handle file viewing if requested
if (isset($_GET['view']) && $_GET['view']) {
  $file = $target_dir . '/' . basename($_GET['view']);

  if (file_exists($file) && !is_dir($file) && strpos(realpath($file), $home_dir) === 0 && is_readable($file)) {
    $mime = getMimeType($file);
    $is_binary = isBinaryFile($file);
    $file_path = realpath($file);
    $file_url = 'file://' . $file_path;

    // If it's a text file or we can't determine, display it
    if (!$is_binary) {
      // For text files, display content
      $content = file_get_contents($file);
      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

      // Determine syntax highlighting based on extension
      $language = '';
      switch ($ext) {
        case 'php': $language = 'php'; break;
        case 'js': $language = 'javascript'; break;
        case 'html': case 'htm': $language = 'html'; break;
        case 'css': $language = 'css'; break;
        case 'py': $language = 'python'; break;
        case 'sh': $language = 'bash'; break;
        case 'json': $language = 'json'; break;
        case 'xml': $language = 'xml'; break;
        case 'md': $language = 'markdown'; break;
        default: $language = 'plaintext';
      }

      // Output the file viewer page
      ?>
      <!DOCTYPE html>
      <html lang="en" data-bs-theme="<?=$theme;?>" translate="no" class="notranslate">
      <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Viewing: <?=htmlspecialchars(basename($file));?></title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/<?=$theme === 'dark' ? 'github-dark' : 'github';?>.min.css" integrity="sha512-0aPQyyeZrWj9QzDngeKjaco+/ICTkRoN/SA+2pEYIFQ1clz2qwkYZ3LD0TJ+gyXA6R+pKK5K8IvviQQgFw/nGA==" crossorigin="anonymous">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
        <style>
          body {
            padding: 20px;
          }
          .container {
            border-radius: 8px;
            padding: 20px;
            padding-left: 10px;
            padding-right: 10px;
            max-width: 1920px;
          }
          pre {
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin-top: 20px;
          }
          .file-info {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
          }
          .back-link {
            margin-bottom: 20px;
            display: inline-block;
          }
          .modal-body {
            word-break: break-all;
          }
          .copy-path {
            cursor: pointer;
          }
          .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
          }
          .action-buttons .btn {
            padding: 0.1rem 0.25rem;
            font-size: 0.75rem;
            vertical-align: top;
          }
          .action-buttons .btn-group {
            display: inline-flex;
            vertical-align: top;
          }
          .action-buttons .fas {
            font-size: 0.75rem;
          }
        </style>
      </head>
      <body>
        <div class="container">
          <div class="theme-toggle"  title="<?=$theme === 'dark' ? "I knew you'd be back." : 'Come over to the dark side.';?>">
            <a href="?toggle_theme=1&dir=<?=urlencode($current_dir);?><?=isset($_GET['view']) ? '&view=' . urlencode($_GET['view']) : '';?>" class="btn btn-sm btn-outline-secondary border-0">
              <i class="fas <?=$theme === 'dark' ? 'fa-sun' : 'fa-moon';?>"></i> <?=$theme === 'dark' ? 'Light' : 'Dark';?>
            </a>
          </div>
          
          <a href="?dir=<?=urlencode($current_dir);?>" class="back-link btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Directory
          </a>

          <?php
            // Get username and hostname for shell-like prompt
            $username = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
            $hostname = gethostname() ?: 'localhost';
            $filepath = basename($file);
          ?>
          <h1><code><?=htmlspecialchars("$username@$hostname:$current_dir/$filepath");?></code></h1>

          <div class="file-info">
            <p><strong>File:</strong> <?=htmlspecialchars(basename($file));?></p>
            <p><strong>Path:</strong> <?=htmlspecialchars($file);?></p>
            <p><strong>Size:</strong> <span class="nowrap"><?=formatFileSize(filesize($file));?></span></p>
            <p><strong>MIME Type:</strong> <?=htmlspecialchars($mime);?></p>
            <p><strong>Last Modified:</strong> <span class="timestamp"><?=date('Y-m-d H:i:s', filemtime($file));?></span></p>
          </div>

          <div class="btn-group mb-3">
            <a href="?dir=<?=urlencode($current_dir);?>&download=<?=urlencode(basename($file));?>" class="btn btn-primary">
              <i class="fas fa-download"></i> Download
            </a>
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#openExternalModal">
              <i class="fas fa-external-link-alt"></i> Open Externally
            </button>
          </div>

          <h3>File Contents:</h3>
          <pre><code class="<?=$language;?>"><?=htmlspecialchars($content);?></code></pre>

          <!-- Modal for Open Externally instructions -->
          <div class="modal fade" id="openExternalModal" tabindex="-1" aria-labelledby="openExternalModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="openExternalModalLabel">Open File with External Application</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <p>To open this file with your default application, you can:</p>

                  <h6>Option 1: Copy the file path</h6>
                  <div class="input-group mb-3">
                    <input type="text" class="form-control" id="filePath" value="<?=htmlspecialchars($file_path);?>" readonly>
                    <button class="btn btn-outline-secondary copy-path" type="button" data-clipboard-target="#filePath">
                      <i class="fas fa-copy"></i> Copy
                    </button>
                  </div>
                  <p class="small text-muted">Paste this path into your file manager or application's "Open" dialog.</p>

                  <h6>Option 2: Use file URL (may not work in all browsers)</h6>
                  <div class="input-group mb-3">
                    <input type="text" class="form-control" id="fileUrl" value="<?=htmlspecialchars($file_url);?>" readonly>
                    <button class="btn btn-outline-secondary copy-path" type="button" data-clipboard-target="#fileUrl">
                      <i class="fas fa-copy"></i> Copy
                    </button>
                  </div>
                  <p class="small text-muted">Copy and paste this URL into your browser's address bar.</p>

                  <h6>Option 3: Download and open</h6>
                  <p>Click the Download button, save the file to your computer, then open it with your preferred application.</p>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <a href="<?=htmlspecialchars($file_url);?>" class="btn btn-primary" target="_blank">Try Direct Open</a>
                </div>
              </div>
            </div>
          </div>

              <script>
            document.addEventListener('DOMContentLoaded', (event) => {
              // Syntax highlighting
              document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightBlock(block);
              });

              // Copy path functionality
              document.querySelectorAll('.copy-path').forEach(button => {
                button.addEventListener('click', () => {
                  const input = document.querySelector(button.dataset.clipboardTarget);
                  input.select();
                  document.execCommand('copy');

                  // Show feedback
                  const originalText = button.innerHTML;
                  button.innerHTML = '<i class="fas fa-check"></i> Copied!';
                  setTimeout(() => {
                    button.innerHTML = originalText;
                  }, 2000);
                });
              });
            });
          </script>
        </div>
      </body>
      </html>
      <?php
      exit;
    } else {
      // For binary files, serve with proper MIME type
      header('Content-Type: ' . $mime);
      header('Content-Disposition: inline; filename="' . basename($file) . '"');
      header('Content-Length: ' . filesize($file));
      readfile($file);
      exit;
    }
  }
}

// Handle file download if requested
if (isset($_GET['download']) && $_GET['download']) {
  $file = $target_dir . '/' . basename($_GET['download']);
  if (file_exists($file) && !is_dir($file) && strpos(realpath($file), $home_dir) === 0) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file) . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
  }
}

// Handle single file/directory deletion if requested
if (isset($_GET['delete']) && $_GET['delete']) {
  $path = $target_dir . '/' . basename($_GET['delete']);
  
  // Security check to ensure path is within target directory
  if (file_exists($path) && strpos(realpath($path), $home_dir) === 0 && is_writable($path)) {
    $is_directory = is_dir($path);
    $deletion_success = false;
    
    // Use appropriate function based on whether it's a file or directory
    if ($is_directory) {
      // Check if directory is empty
      $dir_contents = scandir($path);
      // Remove . and .. from the count
      $dir_contents = array_diff($dir_contents, array('.', '..'));
      
      if (count($dir_contents) === 0) {
        // Directory is empty, safe to delete
        $deletion_success = rmdir($path);
      } else {
        // Directory not empty
        header('Location: ?dir=' . urlencode($current_dir) . '&error=notempty');
        exit;
      }
    } else {
      // It's a file, use unlink
      $deletion_success = unlink($path);
    }
    
    if ($deletion_success) {
      // Redirect back to the current directory to prevent resubmission
      $type = $is_directory ? 'directory' : 'file';
      header('Location: ?dir=' . urlencode($current_dir) . '&deleted=' . urlencode(basename($path)) . '&type=' . $type);
      exit;
    } else {
      header('Location: ?dir=' . urlencode($current_dir) . '&error=delete');
      exit;
    }
  } else {
    // Path doesn't exist or not writable
    header('Location: ?dir=' . urlencode($current_dir) . '&error=invalid');
    exit;
  }
}

// Handle phpinfo request
if (isset($_GET['phpinfo'])) {
  phpinfo();
  exit;
}

// Handle bulk actions (download and delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Handle bulk download
  if (isset($_GET['bulk_download']) && isset($_POST['files']) && is_array($_POST['files'])) {
    $files = $_POST['files'];
    
    // Only handle single file downloads for now
    // For multiple files, we'd need to create a zip file
    if (count($files) === 1) {
      $file = $target_dir . '/' . basename($files[0]);
      
      // Security check
      if (file_exists($file) && !is_dir($file) && strpos(realpath($file), $home_dir) === 0 && is_readable($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
      }
    } else {
      // TO DO: Add zip file support for multiple files
      header('Location: ?dir=' . urlencode($current_dir) . '&error=bulkdownload');
      exit;
    }
  }
  
  // Handle bulk delete
  if (isset($_GET['bulk_delete']) && isset($_POST['items']) && is_array($_POST['items'])) {
    $items = $_POST['items'];
    $success_count = 0;
    $error_count = 0;
    
    foreach ($items as $item) {
      $path = $target_dir . '/' . basename($item);
      
      // Security check
      if (file_exists($path) && strpos(realpath($path), $home_dir) === 0 && is_writable($path)) {
        $is_directory = is_dir($path);
        
        if ($is_directory) {
          // Check if directory is empty
          $dir_contents = scandir($path);
          $dir_contents = array_diff($dir_contents, array('.', '..'));
          
          if (count($dir_contents) === 0 && rmdir($path)) {
            $success_count++;
          } else {
            $error_count++;
          }
        } else {
          // It's a file
          if (unlink($path)) {
            $success_count++;
          } else {
            $error_count++;
          }
        }
      } else {
        $error_count++;
      }
    }
    
    // Redirect with results
    $params = [
      'dir' => $current_dir,
      'bulk_deleted' => $success_count
    ];
    
    if ($error_count > 0) {
      $params['bulk_errors'] = $error_count;
    }
    
    header('Location: ?' . http_build_query($params));
    exit;
  }
}

// Get directory contents
$items = [];
if ($allow_listing && is_dir($target_dir) && is_readable($target_dir)) {
  $dir_handle = @opendir($target_dir);
  if ($dir_handle) {
    // Count for limiting entries
    $entry_count = 0;
    
    while (($file = readdir($dir_handle)) !== false && $entry_count < MAX_PATH_ENTRIES) {
      if ($file != '.') { // Include .. for navigation but exclude .
        $full_path = $target_dir . '/' . $file;

        // Skip if we can't read the file/directory, but check deeper for symlinks with group permissions
        if (!is_readable($full_path)) {
          // For symlinks, do a more thorough check of group permissions
          if (is_link($full_path)) {
            $path_stats = @stat($full_path);
            
            if ($path_stats && function_exists('posix_getgrgid') && function_exists('posix_getgroups')) {
              // Get path's group
              $path_group = $path_stats['gid'];
              
              // Get current user's groups
              $user_groups = posix_getgroups();
              
              // Check if user is in the directory's group and group has read permissions
              $group_has_read = ($path_stats['mode'] & 0040) > 0; // Check group read bit
              $user_in_group = in_array($path_group, $user_groups);
              
              if (!($group_has_read && $user_in_group)) {
                continue; // Skip if no group read access
              }
              // Otherwise proceed with including this item
            } else {
              continue; // Skip without posix functions
            }
          } else {
            continue; // Skip non-symlinks that aren't readable
          }
        }

        $is_dir = is_dir($full_path);
        // If it's a symlink that passed our group permission check but still shows as not readable
        $is_symlink_with_group_access = is_link($full_path) && !is_readable($full_path);
        
        // Handle size determination
        $size = $is_dir ? '' : (is_readable($full_path) || $is_symlink_with_group_access ? 
                               @filesize($full_path) : 'N/A');
        $size = $size === 'N/A' ? 'N/A' : ($size === '' ? '' : formatFileSize($size));

        $items[] = [
          'name' => $file,
          'path' => $full_path,
          'is_dir' => $is_dir,
          'size' => $size,
          'modified' => date('Y-m-d H:i:s', @filemtime($full_path)),
          'icon' => getIcon($full_path),
          'is_readable' => is_readable($full_path) || $is_symlink_with_group_access,
          'is_writable' => is_writable($full_path),
          'is_symlink' => is_link($full_path),
          'is_symlink_with_group_access' => $is_symlink_with_group_access
        ];
        
        $entry_count++;
      }
    }
    closedir($dir_handle);
  }
}

// Apply filters to items array (preserves parent directory '..' regardless of filters)
$filtered_items = [];

foreach ($items as $item) {
  // Skip if it's a dot file and hide_dotfiles is true
  if ($hide_dotfiles && $item['name'] !== '..' && substr($item['name'], 0, 1) === '.') {
    continue;
  }
  
  // Skip if it's a directory and hide_dirs is true, but always keep '..' for navigation
  if ($hide_dirs && $item['is_dir'] && $item['name'] !== '..') {
    continue;
  }
  
  // Skip if it's a file and hide_files is true
  if ($hide_files && !$item['is_dir']) {
    continue;
  }
  
  // Always include '..' for navigation
  if ($item['name'] === '..') {
    $filtered_items[] = $item;
    continue;
  }
  
  // Add to filtered items
  $filtered_items[] = $item;
}

// Sort filtered items: '..' directory first, then directories, then files with user-selected sorting
usort($filtered_items, function($a, $b) use ($sort_column, $sort_order) {
  // Special case for .. directory - always first
  if ($a['name'] === '..') return -1;
  if ($b['name'] === '..') return 1;

  // Directories before files
  if ($a['is_dir'] && !$b['is_dir']) {
    return -1;
  } elseif (!$a['is_dir'] && $b['is_dir']) {
    return 1;
  } else {
    // Within same type (dir/file), apply the user's sort
    $modifier = ($sort_order === 'asc') ? 1 : -1;
    
    switch ($sort_column) {
      case 'size':
        // Compare sizes for files
        if (!$a['is_dir'] && !$b['is_dir']) {
          // Convert human-readable size back to comparable value
          // For simplicity, we'll just compare the raw string values
          return strcasecmp($a['size'], $b['size']) * $modifier;
        }
        // For directories, fall back to name
        return strcasecmp($a['name'], $b['name']) * $modifier;
      
      case 'modified':
        return strcmp($a['modified'], $b['modified']) * $modifier;
      
      case 'name':
      default:
        return strcasecmp($a['name'], $b['name']) * $modifier;
    }
  }
});

// Build breadcrumb navigation
$breadcrumbs = [];
$path_parts = $current_dir ? explode('/', $current_dir) : [];
$breadcrumb_path = '';

$breadcrumbs[] = [
  'name' => 'Home',
  'path' => ''
];

foreach ($path_parts as $part) {
  $breadcrumb_path .= ($breadcrumb_path ? '/' : '') . $part;
  $breadcrumbs[] = [
    'name' => $part,
    'path' => $breadcrumb_path
  ];
}

// Get permissions in human-readable format
function getPermissions($file) {
  if (!file_exists($file)) {
    return '???????';
  }

  $perms = @fileperms($file);
  if ($perms === false) {
    return '???????';
  }

  $info = '';

  // Owner
  $info .= (($perms & 0x0100) ? 'r' : '-');
  $info .= (($perms & 0x0080) ? 'w' : '-');
  $info .= (($perms & 0x0040) ? 'x' : '-');

  // Group
  $info .= (($perms & 0x0020) ? 'r' : '-');
  $info .= (($perms & 0x0010) ? 'w' : '-');
  $info .= (($perms & 0x0008) ? 'x' : '-');

  // World
  $info .= (($perms & 0x0004) ? 'r' : '-');
  $info .= (($perms & 0x0002) ? 'w' : '-');
  $info .= (($perms & 0x0001) ? 'x' : '-');

  return $info;
}

// Debug information
$debug = $debug_info;

// Alert messages for file operations
$alert_message = '';
$alert_type = '';

// Single item deletion messages
if (isset($_GET['deleted'])) {
  $deleted_item = htmlspecialchars($_GET['deleted']);
  $item_type = isset($_GET['type']) && $_GET['type'] === 'directory' ? 'Directory' : 'File';
  $alert_type = 'success';
  $alert_message = "$item_type <strong>$deleted_item</strong> was successfully deleted.";
} 
// Bulk delete messages
else if (isset($_GET['bulk_deleted'])) {
  $count = intval($_GET['bulk_deleted']);
  $alert_type = 'success';
  $alert_message = "<strong>$count</strong> item(s) were successfully deleted.";
  
  if (isset($_GET['bulk_errors']) && intval($_GET['bulk_errors']) > 0) {
    $error_count = intval($_GET['bulk_errors']);
    $alert_message .= " However, <strong>$error_count</strong> item(s) could not be deleted.";
  }
} 
// Error messages
else if (isset($_GET['error'])) {
  $alert_type = 'danger';
  switch ($_GET['error']) {
    case 'delete':
      $alert_message = "Error deleting item. Check permissions.";
      break;
    case 'invalid':
      $alert_message = "Cannot delete item. It may not exist or not be writable.";
      break;
    case 'notempty':
      $alert_message = "Cannot delete directory because it is not empty.";
      break;
    case 'bulkdownload':
      $alert_message = "Bulk download of multiple files is not supported yet.";
      break;
    default:
      $alert_message = "An error occurred.";
  }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?=$theme;?>" translate="no" class="notranslate">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Directory Listing: <?=$current_dir ? $current_dir : 'Home';?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous">
  <script>
    // Function to confirm and delete a single item
    function confirmDelete(filename, isDirectory) {
      var itemType = isDirectory ? 'directory' : 'file';
      var message = 'Are you sure you want to delete this ' + itemType + ':\n"' + filename + '"?\n\nThis action cannot be undone.';
      
      if (isDirectory) {
        message += '\n\nNote: Directory must be empty to be deleted.';
      }
      
      if (confirm(message)) {
        window.location.href = '?dir=<?=urlencode($current_dir);?>&delete=' + encodeURIComponent(filename);
      }
    }
    
    // Function to select all checkboxes
    function selectAll() {
      document.querySelectorAll('.item-select').forEach(function(checkbox) {
        checkbox.checked = true;
      });
    }
    
    // Function to deselect all checkboxes
    function selectNone() {
      document.querySelectorAll('.item-select').forEach(function(checkbox) {
        checkbox.checked = false;
      });
    }
    
    // Function to get all selected items
    function getSelectedItems() {
      var selected = [];
      document.querySelectorAll('.item-select:checked').forEach(function(checkbox) {
        selected.push({
          name: checkbox.getAttribute('data-name'),
          isDir: checkbox.getAttribute('data-is-dir') === 'true'
        });
      });
      return selected;
    }
    
    // Function for bulk download
    function bulkDownload() {
      var selected = getSelectedItems();
      if (selected.length === 0) {
        alert('Please select at least one file to download.');
        return;
      }
      
      // Count directories
      var dirCount = selected.filter(function(item) { return item.isDir; }).length;
      if (dirCount > 0) {
        alert('Cannot download directories. Please select only files.');
        return;
      }
      
      // Create a form to post the selected files
      var form = document.createElement('form');
      form.method = 'post';
      form.action = '?dir=<?=urlencode($current_dir);?>&bulk_download=1';
      
      // Add selected files as hidden inputs
      selected.forEach(function(item, index) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'files[]';
        input.value = item.name;
        form.appendChild(input);
      });
      
      // Submit the form
      document.body.appendChild(form);
      form.submit();
    }
    
    // Function for bulk delete
    function bulkDelete() {
      var selected = getSelectedItems();
      if (selected.length === 0) {
        alert('Please select at least one item to delete.');
        return;
      }
      
      // Count files and directories
      var fileCount = selected.filter(function(item) { return !item.isDir; }).length;
      var dirCount = selected.filter(function(item) { return item.isDir; }).length;
      
      var message = 'Are you sure you want to delete the selected items?\n\n';
      if (fileCount > 0) {
        message += '- ' + fileCount + ' file(s)\n';
      }
      if (dirCount > 0) {
        message += '- ' + dirCount + ' directory/directories\n';
        message += '\nNote: Directories must be empty to be deleted.';
      }
      message += '\n\nThis action cannot be undone.';
      
      if (confirm(message)) {
        // Create a form to post the selected files
        var form = document.createElement('form');
        form.method = 'post';
        form.action = '?dir=<?=urlencode($current_dir);?>&bulk_delete=1';
        
        // Add selected files as hidden inputs
        selected.forEach(function(item, index) {
          var input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'items[]';
          input.value = item.name;
          form.appendChild(input);
        });
        
        // Submit the form
        document.body.appendChild(form);
        form.submit();
      }
    }
  </script>
  <style>
    body {
      padding: 20px;
    }
    .container {
      border-radius: 8px;
      padding: 20px;
      padding-left: 10px;
      padding-right: 10px;
      max-width: 1920px;
    }
    .breadcrumb {
      padding: 10px 15px;
      border-radius: 4px;
    }
    .table {
      margin-top: 20px;
    }
    .file-icon {
      margin-right: 10px;
      font-size: 1.2em;
    }
    .timestamp, .nowrap {
      white-space: nowrap;
    }
    .file-link {
      text-decoration: none;
    }
    .file-link:hover {
      text-decoration: underline;
    }
    .dir-link {
      font-weight: bold;
    }
    .symlink-dir {
      font-style: italic;
      text-decoration: underline dotted;
    }
    .action-buttons {
      white-space: nowrap;
    }
    .action-buttons .btn {
      padding: 0.1rem 0.25rem;
      font-size: 0.75rem;
      vertical-align: top;
    }
    .action-buttons .btn-group {
      display: inline-flex;
      vertical-align: top;
    }
    .action-buttons .fas {
      font-size: 0.75rem;
    }
    .path-info {
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
      word-break: break-all;
    }
    .permission-tag {
      font-family: monospace;
      padding: 2px 5px;
      border-radius: 3px;
      font-size: 0.85em;
    }
    .debug-info {
      margin-top: 20px;
      padding: 10px;
      border-radius: 4px;
      font-family: monospace;
      font-size: 0.9em;
    }
    .theme-toggle {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1000;
    }
    th a.text-decoration-none {
      color: inherit;
      width: 100%;
    }
    th .fas {
      margin-left: 5px;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="theme-toggle" title="<?=$theme === 'dark' ? "I knew you'd be back." : 'Come over to the dark side.';?>">
      <a href="?toggle_theme=1<?=isset($_GET['dir']) ? '&dir=' . urlencode($_GET['dir']) : '';?>" class="btn btn-sm btn-outline-secondary border-0">
        <i class="fas <?=$theme === 'dark' ? 'fa-sun' : 'fa-moon';?>"></i> <?=$theme === 'dark' ? 'Light' : 'Dark';?> </a>
    </div>
    <?php
      // Get username and hostname for shell-like prompt
      $username = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
      $hostname = gethostname() ?: 'localhost';
      $pwd = $current_dir ?: '~';
    ?>
    <h1><code><?=htmlspecialchars("$username@$hostname:$pwd");?></code></h1>

    <?php if (!$allow_listing): ?>
      <div class="alert alert-danger">
        Directory listing is disabled. Set $allow_listing to true to enable.
      </div>
    <?php else: ?>
      <!-- Alert messages -->
      <?php if ($alert_message): ?>
        <div class="alert alert-<?=$alert_type;?> alert-dismissible fade show" role="alert">
          <?=$alert_message;?>
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif;?>

      <!-- Current path info -->
      <div class="path-info">
        <strong>Current Path:</strong> <?=htmlspecialchars($target_dir);?>
      </div>

      <!-- Filters row -->
      <div class="card mb-3">
        <div class="card-body py-2">
          <div class="d-flex flex-wrap align-items-center">
            <small class="text-muted me-2">Filters:</small>
            
            <div class="form-check form-check-inline m-0 me-3">
              <input class="form-check-input" type="checkbox" id="hideDotFiles" <?=$hide_dotfiles ? 'checked' : '';?>
                    onclick="window.location.href='<?=getFilterUrl('hide_dotfiles', $hide_dotfiles, $current_dir);?>'">
              <label class="form-check-label small" for="hideDotFiles">Hide .dot files</label>
            </div>
            
            <div class="form-check form-check-inline m-0 me-3">
              <input class="form-check-input" type="checkbox" id="hideDirs" <?=$hide_dirs ? 'checked' : '';?>
                    onclick="window.location.href='<?=getFilterUrl('hide_dirs', $hide_dirs, $current_dir);?>'">
              <label class="form-check-label small" for="hideDirs">Hide directories</label>
            </div>
            
            <div class="form-check form-check-inline m-0 me-3">
              <input class="form-check-input" type="checkbox" id="hideFiles" <?=$hide_files ? 'checked' : '';?>
                    onclick="window.location.href='<?=getFilterUrl('hide_files', $hide_files, $current_dir);?>'">
              <label class="form-check-label small" for="hideFiles">Hide files</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Breadcrumb navigation -->
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          <?php foreach ($breadcrumbs as $index => $crumb): ?>
            <?php if ($index === count($breadcrumbs) - 1): ?>
              <li class="breadcrumb-item active"><?=htmlspecialchars($crumb['name']);?></li>
            <?php else: ?>
              <li class="breadcrumb-item">
                <a href="?dir=<?=urlencode($crumb['path']);?>"><?=htmlspecialchars($crumb['name']);?></a>
              </li>
            <?php endif;?>
          <?php endforeach;?>
        </ol>
      </nav>

      <!-- Directory contents -->
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Actions</th>
              <th><a href="<?=getSortUrl('name', $sort_column, $sort_order, $current_dir);?>" class="text-decoration-none d-flex align-items-center justify-content-between">Name <?=getSortIcon('name', $sort_column, $sort_order);?></a></th>
              <th><a href="<?=getSortUrl('size', $sort_column, $sort_order, $current_dir);?>" class="text-decoration-none d-flex align-items-center justify-content-between">Size <?=getSortIcon('size', $sort_column, $sort_order);?></a></th>
              <th>Permissions</th>
              <th><a href="<?=getSortUrl('modified', $sort_column, $sort_order, $current_dir);?>" class="text-decoration-none d-flex align-items-center justify-content-between">Last Modified <?=getSortIcon('modified', $sort_column, $sort_order);?></a></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filtered_items as $item): ?>
              <tr>
                <td class="action-buttons">
                  <?php if ($item['name'] == '..'): ?>
                    <!-- All/None buttons in parent directory row, with Bulk dropdown -->
                    <div class="d-inline-flex align-items-start">
                      <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary border-0<?=$target_dir === $home_dir ? ' text-muted' : '';?>" onclick="selectAll()" title="Select All">
                          <i class="fas fa-check-square"></i> All
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary border-0<?=$target_dir === $home_dir ? ' text-muted' : '';?>" onclick="selectNone()" title="Select None">
                          <i class="fas fa-square"></i> None
                        </button>
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle border-0<?=$target_dir === $home_dir ? ' text-muted' : '';?>" type="button" id="bulkActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                          Bulk
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="bulkActionsDropdown">
                          <li><a class="dropdown-item" href="#" onclick="bulkDownload(); return false;">Download Selected</a></li>
                          <li><a class="dropdown-item" href="#" onclick="bulkDelete(); return false;">Delete Selected</a></li>
                        </ul>
                      </div>
                    </div>
                  <?php else: ?>
                    <!-- Selection checkbox for each file/directory -->
                    <div class="d-inline-flex align-items-start">
                      <div class="form-check d-inline-block me-1">
                        <input class="form-check-input item-select" type="checkbox" id="select-<?=htmlspecialchars($item['name']);?>"
                              data-name="<?=htmlspecialchars($item['name']);?>"
                              data-is-dir="<?=$item['is_dir'] ? 'true' : 'false';?>">
                      </div>
                      
                      <div class="btn-group">
                        <?php if (!$item['is_dir'] && $item['is_readable']): ?>
                          <a href="?dir=<?=urlencode($current_dir);?>&view=<?=urlencode($item['name']);?>" class="btn btn-sm btn-outline-primary" title="View">
                            <i class="fas fa-eye"></i>
                          </a>
                          <a href="?dir=<?=urlencode($current_dir);?>&download=<?=urlencode($item['name']);?>" class="btn btn-sm btn-outline-success" title="Download">
                            <i class="fas fa-download"></i>
                          </a>
                        <?php endif;?>
                        <?php if ($item['is_writable'] && isset($item['path'])): ?>
                          <button onclick="confirmDelete('<?=addslashes(htmlspecialchars($item['name']));?>', <?=$item['is_dir'] ? 'true' : 'false';?>)" class="btn btn-sm btn-outline-danger" title="Delete">
                            <i class="fas fa-trash"></i>
                          </button>
                        <?php endif;?>
                      </div>
                    </div>
                  <?php endif;?>
                </td>
                <td>
                  <span class="file-icon<?=($item['name'] == '..' && $target_dir === $home_dir) ? ' text-muted' : '';?>"><?=$item['icon'];?></span>
                  <?php if ($item['is_dir']): ?>
                    <?php if ($item['name'] == '..'): ?>
                      <?php if ($target_dir === $home_dir): ?>
                        <span class="file-link dir-link text-muted">..</span>
                      <?php else: ?>
                        <a href="?dir=<?=urlencode(dirname($current_dir));?>" class="file-link dir-link">..</a>
                      <?php endif; ?>
                    <?php else: ?>
                      <?php
                        // Calculate the directory path for the URL 
                        $dir_path = $current_dir ? $current_dir . '/' . $item['name'] : $item['name'];
                        $full_path = $item['path'];
                        
                        // Special handling for directory symlinks
                        if ($item['is_symlink'] && $item['is_dir']) {
                          // Log that we're accessing a symlink for debugging
                          error_log("Accessing symlink directory: " . $full_path);
                          // For symlinks, we want to maintain the URL path structure rather than resolving
                          // the real path, so we use the name directly from the item
                        }
                      ?>
                      <a href="?dir=<?=urlencode($dir_path);?>" class="file-link dir-link<?=$item['is_symlink'] ? ' symlink-dir' : '';?>">
                        <?=htmlspecialchars($item['name']);?><?=$item['is_symlink'] ? ' (link)' : '';?>
                      </a>
                    <?php endif;?>
                  <?php else: ?>
                    <?php if ($item['is_readable']): ?>
                      <a href="?dir=<?=urlencode($current_dir);?>&view=<?=urlencode($item['name']);?>" class="file-link">
                        <?=htmlspecialchars($item['name']);?>
                      </a>
                    <?php else: ?>
                      <?=htmlspecialchars($item['name']);?>
                    <?php endif;?>
                  <?php endif;?>
                </td>
                <td class="nowrap"><?=$item['size'];?></td>
                <td>
                  <span class="permission-tag" title="<?=$item['is_readable'] ? 'Readable' : 'Not Readable';?>, <?=$item['is_writable'] ? 'Writable' : 'Not Writable';?><?=$item['is_symlink'] ? ', Symlink' : '';?><?=$item['is_symlink_with_group_access'] ? ' (Group Access)' : '';?>">
                    <?=getPermissions($item['path']);?><?=$item['is_symlink'] ? ' 🔗' : '';?>
                  </span>
                </td>
                <td class="timestamp"><?=$item['modified'];?></td>
              </tr>
            <?php endforeach;?>

            <?php if (empty($filtered_items)): ?>
              <tr>
                <td colspan="5" class="text-center">No files or directories found matching current filters.</td>
              </tr>
            <?php endif;?>
          </tbody>
        </table>
      </div>

      <!-- Debug information -->
      <div class="debug-info">
        <h5>Debug Information</h5>
        <pre><?php 
          // Add symlink info to debug
          $debug['using_posix'] = function_exists('posix_getgroups') ? 'Yes' : 'No';
          $debug['symlinks_checked'] = count(array_filter($items, function($i) { return isset($i['is_symlink']) && $i['is_symlink']; }));
          print_r($debug);
        ?></pre>
      </div>

      <!-- System info -->
      <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>System Information</span>
          <a href="?phpinfo=1" class="btn btn-sm btn-outline-info" target="_blank">View PHP Info</a>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6">
              <h6>Server Information</h6>
              <p><strong>PHP Version:</strong> <?=phpversion();?></p>
              <p><strong>Server Software:</strong> <?=$_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';?></p>
              <p><strong>User:</strong> <?=get_current_user();?></p>
              <p><strong>Hostname:</strong> <?=gethostname() ?: 'Unknown';?></p>
              <p><strong>Server OS:</strong> <?=PHP_OS;?></p>
              <p><strong>Interface:</strong> <?=php_sapi_name();?></p>
            </div>
            <div class="col-md-6">
              <h6>PHP Configuration</h6>
              <p><strong>Memory Limit:</strong> <?=ini_get('memory_limit');?></p>
              <p><strong>Upload Max Size:</strong> <?=ini_get('upload_max_filesize');?></p>
              <p><strong>Post Max Size:</strong> <?=ini_get('post_max_size');?></p>
              <p><strong>Max Execution Time:</strong> <?=ini_get('max_execution_time');?> seconds</p>
              <p><strong>Display Errors:</strong> <?=ini_get('display_errors') ? 'On' : 'Off';?></p>
              <p><strong>Extensions:</strong> <?php 
                $ext_list = get_loaded_extensions();
                $important_exts = ['mysqli', 'pdo', 'pdo_mysql', 'gd', 'json', 'curl', 'zip'];
                $ext_status = [];
                foreach ($important_exts as $ext) {
                  $ext_status[] = $ext . ': ' . (in_array($ext, $ext_list) ? '<span class="text-success">Loaded</span>' : '<span class="text-danger">Not Loaded</span>');
                }
                echo implode(', ', $ext_status);
              ?></p>
            </div>
          </div>
          <div class="mt-3">
            <h6>Path Information</h6>
            <p><strong>Document Root:</strong> <?=$_SERVER['DOCUMENT_ROOT'];?></p>
            <p><strong>Current Script:</strong> <?=$_SERVER['SCRIPT_FILENAME'];?></p>
            <p><strong>Temporary Directory:</strong> <?=sys_get_temp_dir();?></p>
          </div>
        </div>
      </div>
    <?php endif;?>

    <footer class="mt-4 text-muted">
      <small>Directory Listing Script © <?=date('Y');?></small>
    </footer>
  </div>
</body>
</html>
