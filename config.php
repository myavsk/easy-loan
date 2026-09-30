<?php
require_once __DIR__ . '/config.php';

session_start();

define('DEFAULT_PASSWORD', 'admin@123');
define('DATA_FILE', __DIR__ . '/data.json');
define('QR_DIR', __DIR__ . '/qr_codes');
define('SESSION_TIMEOUT', 3600);

if (!is_dir(QR_DIR)) {
    @mkdir(QR_DIR, 0755, true);
}

function loadData()
{
    if (!file_exists(DATA_FILE)) {
        $default = [
            'portal_url' => 'https://loanportal.example.com',
            'agents' => [],
            'qr_codes' => [],
            'applications' => []
        ];
        saveData($default);
        return $default;
    }

    $json = @file_get_contents(DATA_FILE);
    $data = json_decode($json, true);

    if (!is_array($data)) {
        $data = [
            'portal_url' => 'https://loanportal.example.com',
            'agents' => [],
            'qr_codes' => [],
            'applications' => []
        ];
    }

    $data['portal_url'] = $data['portal_url'] ?? 'https://loanportal.example.com';
    $data['agents'] = $data['agents'] ?? [];
    $data['qr_codes'] = $data['qr_codes'] ?? [];
    $data['applications'] = $data['applications'] ?? [];

    return $data;
}

function saveData($data)
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return file_put_contents(DATA_FILE, $json, LOCK_EX) !== false;
}

function isLoggedIn()
{
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

function sanitize($value)
{
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function getBaseUrl()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['PHP_SELF']);

    if ($path === '/' || $path === '\\') {
        $path = '';
    }

    $base = $protocol . $host . $path . '/';
    return $base;
}

function isValidUrl($url)
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function isValidPhone($phone)
{
    $digits = preg_replace('/\D+/', '', $phone ?? '');
    return strlen($digits) === 10;
}

function findAgentByCode($code, $data)
{
    foreach ($data['agents'] as $agent) {
        if (($agent['code'] ?? '') === $code) {
            return $agent;
        }
    }
    return null;
}

function findQRByCode($code, $data)
{
    foreach ($data['qr_codes'] as $qr) {
        if (($qr['code'] ?? '') === $code) {
            return $qr;
        }
    }
    return null;
}

function generateQRImage($text, $file_name)
{
    $url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($text);
    $imageData = @file_get_contents($url);

    if ($imageData === false) {
        return false;
    }

    $path = QR_DIR . '/' . $file_name . '.png';
    if (file_put_contents($path, $imageData) === false) {
        return false;
    }

    return $path;
}

function getQRImagePath($code)
{
    $path = QR_DIR . '/' . $code . '.png';
    return file_exists($path) ? $path : null;
}

function createQRZip($qrCodes)
{
    if (!class_exists('ZipArchive')) {
        return false;
    }

    $zipPath = tempnam(sys_get_temp_dir(), 'easyloan_qr_') . '.zip';
    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    foreach ($qrCodes as $qr) {
        $code = $qr['code'] ?? null;
        if (!$code) {
            continue;
        }

        $imagePath = getQRImagePath($code);
        if ($imagePath && file_exists($imagePath)) {
            $zip->addFile($imagePath, $code . '.png');
        }
    }

    $zip->close();
    return $zipPath;
}

function logApplication($data)
{
    $entry = json_encode($data, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    @file_put_contents(__DIR__ . '/applications.log', $entry, FILE_APPEND | LOCK_EX);
}
?>