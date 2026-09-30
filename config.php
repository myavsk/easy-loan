<?php
/**
 * Configuration & Database Helper Functions
 * Easy Loan System - Advanced QR Management
 */

session_start();

// Configuration Constants
define('DEFAULT_PASSWORD', 'admin@123');
define('DATA_FILE', __DIR__ . '/data.json');
define('QR_IMAGES_DIR', __DIR__ . '/qr_codes');
define('SESSION_TIMEOUT', 3600);
define('QR_IMAGES_URL', 'qr_codes/');

// Ensure QR images directory exists
if (!is_dir(QR_IMAGES_DIR)) {
    @mkdir(QR_IMAGES_DIR, 0755, true);
}

/**
 * Load data from JSON file
 */
function loadData() {
    if (!file_exists(DATA_FILE)) {
        $default_data = array(
            'portal_url' => 'https://loanportal.example.com',
            'qr_codes' => array(),
            'agents' => array(),
            'applications' => array()
        );
        saveData($default_data);
        return $default_data;
    }
    
    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json, true);
    
    // Validate structure
    if (!isset($data['portal_url'])) $data['portal_url'] = 'https://loanportal.example.com';
    if (!isset($data['qr_codes'])) $data['qr_codes'] = array();
    if (!isset($data['agents'])) $data['agents'] = array();
    if (!isset($data['applications'])) $data['applications'] = array();
    
    return $data;
}

/**
 * Save data to JSON file
 */
function saveData($data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents(DATA_FILE, $json, LOCK_EX) === false) {
        return false;
    }
    return true;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    if (!isset($_SESSION['admin_login'])) {
        return false;
    }
    if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
        unset($_SESSION['admin_login']);
        unset($_SESSION['login_time']);
        return false;
    }
    $_SESSION['login_time'] = time();
    return true;
}

/**
 * Generate QR code image using external API
 */
function generateQRImage($text, $filename) {
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($text);
    
    $image_data = @file_get_contents($url);
    if ($image_data === false) {
        return false;
    }
    
    $file_path = QR_IMAGES_DIR . '/' . $filename . '.png';
    if (file_put_contents($file_path, $image_data) === false) {
        return false;
    }
    
    return $file_path;
}

/**
 * Generate random unique code
 */
function generateRandomCode($prefix = 'QR') {
    return $prefix . '_' . strtoupper(bin2hex(random_bytes(8)));
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    if (substr($base_url, -1) !== '/') {
        $base_url .= '/';
    }
    return $base_url;
}

/**
 * Find QR code by code
 */
function findQRByCode($qr_code, $data) {
    foreach ($data['qr_codes'] as $qr) {
        if ($qr['code'] === $qr_code) {
            return $qr;
        }
    }
    return null;
}

/**
 * Find agent by code
 */
function findAgentByCode($agent_code, $data) {
    foreach ($data['agents'] as $agent) {
        if ($agent['code'] === $agent_code) {
            return $agent;
        }
    }
    return null;
}

/**
 * Find agent by name
 */
function findAgentByName($agent_name, $data) {
    foreach ($data['agents'] as $agent) {
        if ($agent['name'] === $agent_name) {
            return $agent;
        }
    }
    return null;
}

/**
 * Check if file exists
 */
function getQRImageFile($filename) {
    $file_path = QR_IMAGES_DIR . '/' . $filename . '.png';
    return file_exists($file_path) ? $file_path : null;
}

/**
 * Create ZIP file from QR images
 */
function createQRZip($qr_codes) {
    $zip_file = tempnam(sys_get_temp_dir(), 'qr_');
    $zip = new ZipArchive();
    
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }
    
    foreach ($qr_codes as $qr) {
        $image_file = getQRImageFile($qr['code']);
        if ($image_file && file_exists($image_file)) {
            $zip->addFile($image_file, $qr['code'] . '.png');
        }
    }
    
    $zip->close();
    return $zip_file;
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate URL
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate phone number
 */
function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) === 10;
}

/**
 * Log application submission
 */
function logApplication($data) {
    $log_file = __DIR__ . '/applications.log';
    $log_entry = json_encode($data) . "\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

?>
