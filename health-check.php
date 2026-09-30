<?php
// Simple health check endpoint
header('Content-Type: application/json');

$data_file = __DIR__ . '/data.json';
$qr_dir = __DIR__ . '/qr_codes';

$status = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'data_file_exists' => file_exists($data_file),
    'qr_dir_exists' => is_dir($qr_dir),
    'data_file_writable' => is_writable($data_file),
    'qr_dir_writable' => is_writable($qr_dir)
];

if (!$status['data_file_exists'] || !$status['qr_dir_exists']) {
    $status['status'] = 'setup_needed';
}

http_response_code($status['status'] === 'ok' ? 200 : 503);
echo json_encode($status, JSON_PRETTY_PRINT);
?>
