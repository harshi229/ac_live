<?php
// Image serving script to bypass rewrite issues
require_once __DIR__ . '/../includes/config/init.php';

// Disable output buffering for images to prevent race conditions
while (ob_get_level()) {
    ob_end_clean();
}

// Get the image filename from the URL
$image_file = isset($_GET['file']) ? $_GET['file'] : '';

if (empty($image_file)) {
    // Serve placeholder image instead of 404
    serve_fallback_image();
    exit;
}

// Security: Only allow image files
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$file_extension = strtolower(pathinfo($image_file, PATHINFO_EXTENSION));

if (!in_array($file_extension, $allowed_extensions)) {
    // Serve placeholder image instead of 403
    serve_fallback_image();
    exit;
}

// Construct the full file path
$file_path = UPLOAD_PATH . '/' . $image_file;

// Check if file exists - do this once and cache results
if (!file_exists($file_path)) {
    // Serve placeholder image instead of 404
    serve_fallback_image();
    exit;
}

// Get file stats once to avoid multiple system calls
$file_size = filesize($file_path);
$file_mtime = filemtime($file_path);

// Set appropriate headers
$mime_types = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'gif' => 'image/gif',
    'webp' => 'image/webp'
];

$mime_type = $mime_types[$file_extension] ?? 'application/octet-stream';

// Calculate ETag once
$etag = '"' . md5($file_path . $file_mtime . $file_size) . '"';
$last_modified_gmt = gmdate('D, d M Y H:i:s', $file_mtime) . ' GMT';

// Check if browser has cached version (do this BEFORE setting headers)
if (isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
    $if_none_match = trim($_SERVER['HTTP_IF_NONE_MATCH']);
    if ($if_none_match === $etag) {
        http_response_code(304);
        header('ETag: ' . $etag);
        header('Cache-Control: public, max-age=31536000');
        exit;
    }
}

if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
    $if_modified_since = trim($_SERVER['HTTP_IF_MODIFIED_SINCE']);
    // Proper date comparison using strtotime
    $if_modified_since_timestamp = strtotime($if_modified_since);
    if ($if_modified_since_timestamp !== false && $if_modified_since_timestamp >= $file_mtime) {
        http_response_code(304);
        header('Last-Modified: ' . $last_modified_gmt);
        header('Cache-Control: public, max-age=31536000');
        exit;
    }
}

// Set performance headers
header('Content-Type: ' . $mime_type);
header('Content-Length: ' . $file_size);
header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
header('Last-Modified: ' . $last_modified_gmt);
header('ETag: ' . $etag);

// Disable compression for images (can cause corruption)
if (extension_loaded('zlib')) {
    ini_set('zlib.output_compression', 'Off');
}

// Output the file - use readfile for better performance and reliability
readfile($file_path);
exit;

/**
 * Serve fallback placeholder image
 */
function serve_fallback_image() {
    $fallback_path = PUBLIC_PATH . '/img/placeholder-product.png';
    
    // If placeholder doesn't exist, create a simple one
    if (!file_exists($fallback_path)) {
        create_simple_placeholder();
        $fallback_path = PUBLIC_PATH . '/img/placeholder-product.png';
    }
    
    if (file_exists($fallback_path)) {
        header('Content-Type: image/png');
        header('Content-Length: ' . filesize($fallback_path));
        header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
        readfile($fallback_path);
    } else {
        // Last resort - return a 1x1 transparent pixel
        header('Content-Type: image/png');
        header('Content-Length: 68');
        echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    }
}

/**
 * Create a simple placeholder image if it doesn't exist
 */
function create_simple_placeholder() {
    $placeholder_path = PUBLIC_PATH . '/img/placeholder-product.png';
    
    // Create a simple 200x200 gray placeholder
    $image = imagecreate(200, 200);
    $bg_color = imagecolorallocate($image, 240, 240, 240);
    $text_color = imagecolorallocate($image, 150, 150, 150);
    
    // Fill background
    imagefill($image, 0, 0, $bg_color);
    
    // Add text
    imagestring($image, 5, 50, 90, 'No Image', $text_color);
    
    // Save as PNG
    imagepng($image, $placeholder_path);
    imagedestroy($image);
}
?>
