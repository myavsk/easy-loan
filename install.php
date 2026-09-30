<?php
/**
 * Installation & Setup Script for Easy Loan System
 * Run once to initialize the application
 */

header('Content-Type: text/html; charset=UTF-8');

$base_dir = __DIR__;
$data_file = $base_dir . '/data.json';
$qr_dir = $base_dir . '/qr_codes';

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$message = '';
$error = '';

// Step 1: Check prerequisites
if ($step === 1) {
    $php_ok = version_compare(PHP_VERSION, '7.0', '>=');
    $zip_ok = extension_loaded('zip');
    $curl_ok = extension_loaded('curl');
    $write_ok = is_writable($base_dir);
    
    if ($php_ok && $write_ok) {
        $can_proceed = true;
    } else {
        $can_proceed = false;
    }
}

// Step 2: Create directories and files
if ($step === 2) {
    @mkdir($qr_dir, 0755, true);
    
    if (!file_exists($data_file)) {
        $default_data = [
            'portal_url' => 'https://loanportal.example.com',
            'agents' => [],
            'qr_codes' => [],
            'applications' => []
        ];
        file_put_contents($data_file, json_encode($default_data, JSON_PRETTY_PRINT));
    }
    
    $message = 'Setup completed successfully!';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easy Loan - Setup</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            width: min(600px, 100%);
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
        }
        .content {
            padding: 30px;
        }
        .check-item {
            padding: 12px;
            margin: 8px 0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .check-item.ok {
            background: #dcfce7;
            color: #166534;
        }
        .check-item.fail {
            background: #fee2e2;
            color: #991b1b;
        }
        .icon {
            font-size: 20px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            margin-top: 16px;
        }
        .message {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }
        .message.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Easy Loan Setup</h1>
        </div>
        <div class="content">
            <?php if ($step === 1): ?>
                <h2>System Requirements Check</h2>
                <div class="check-item <?= $php_ok ? 'ok' : 'fail' ?>">
                    <span class="icon"><?= $php_ok ? '✓' : '✗' ?></span>
                    <span>PHP 7.0+ (Current: <?= PHP_VERSION ?>)</span>
                </div>
                <div class="check-item <?= $zip_ok ? 'ok' : 'fail' ?>">
                    <span class="icon"><?= $zip_ok ? '✓' : '✗' ?></span>
                    <span>ZIP Extension</span>
                </div>
                <div class="check-item <?= $curl_ok ? 'ok' : 'fail' ?>">
                    <span class="icon"><?= $curl_ok ? '✓' : '✗' ?></span>
                    <span>CURL Extension</span>
                </div>
                <div class="check-item <?= $write_ok ? 'ok' : 'fail' ?>">
                    <span class="icon"><?= $write_ok ? '✓' : '✗' ?></span>
                    <span>Write Permissions</span>
                </div>
                
                <?php if (!$can_proceed): ?>
                    <div class="message error">
                        ⚠️ Some requirements are not met. Please fix them before proceeding.
                    </div>
                <?php else: ?>
                    <a href="?step=2" class="button">Continue to Setup →</a>
                <?php endif; ?>
            
            <?php elseif ($step === 2): ?>
                <?php if ($message): ?>
                    <div class="message success">
                        ✓ <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <h2>Setup Completed!</h2>
                <p style="line-height: 1.6; color: #666;">
                    Your Easy Loan System is ready to use.
                </p>
                
                <h3 style="margin-top: 20px;">Next Steps:</h3>
                <ol style="color: #666;">
                    <li>Go to <a href="admin.php" style="color: #1d4ed8;">Admin Panel</a></li>
                    <li>Login with password: <strong>admin@123</strong></li>
                    <li>Update Portal URL</li>
                    <li>Add your first agent</li>
                    <li>Generate QR codes</li>
                    <li>Assign QR to agents</li>
                </ol>
                
                <a href="admin.php" class="button">Go to Admin Panel →</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
