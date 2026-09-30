<?php
require_once __DIR__ . '/config.php';

$data = loadData();
$portalUrl = $data['portal_url'] ?? 'https://loanportal.example.com';

$agentCode = trim($_GET['agent'] ?? '');
$qrCode = trim($_GET['qr'] ?? '');

$agent = null;
$errorMessage = '';

if ($qrCode !== '') {
    $qr = findQRByCode($qrCode, $data);
    if (!$qr) {
        $errorMessage = 'Invalid QR code.';
    } else {
        if (!empty($qr['assigned_agent_code'])) {
            $agent = findAgentByCode($qr['assigned_agent_code'], $data);
        } else {
            $errorMessage = 'This QR code is not assigned to any agent yet.';
        }
    }
} elseif ($agentCode !== '') {
    $agent = findAgentByCode($agentCode, $data);
    if (!$agent) {
        $errorMessage = 'Invalid agent code.';
    }
} else {
    $errorMessage = 'No QR or agent code provided.';
}

$formError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $agent) {
    $customerName = trim($_POST['customer_name'] ?? '');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $loanAmount = trim($_POST['loan_amount'] ?? '');

    if ($customerName === '') {
        $formError = 'Please enter your full name.';
    } elseif (!isValidPhone($customerPhone)) {
        $formError = 'Please enter a valid 10-digit mobile number.';
    } elseif ($loanAmount === '' || !is_numeric($loanAmount) || (float)$loanAmount <= 0) {
        $formError = 'Please enter a valid loan amount.';
    } else {
        $redirectParams = [
            'customer_name' => $customerName,
            'customer_phone' => preg_replace('/\D+/', '', $customerPhone),
            'loan_amount' => $loanAmount,
            'agent_code' => $agent['code'],
            'agent_name' => $agent['name']
        ];

        $redirectUrl = $portalUrl;
        if (strpos($redirectUrl, '?') !== false) {
            $redirectUrl .= '&' . http_build_query($redirectParams);
        } else {
            $redirectUrl .= '?' . http_build_query($redirectParams);
        }

        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'agent_code' => $agent['code'],
            'agent_name' => $agent['name'],
            'customer_name' => $customerName,
            'customer_phone' => preg_replace('/\D+/', '', $customerPhone),
            'loan_amount' => $loanAmount,
            'qr_code' => $qrCode ?: null
        ];

        logApplication($logData);

        header('Location: ' . $redirectUrl);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easy Loan Application</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .card {
            width: min(520px, 100%);
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 20px 60px rgba(0,0,0,.25);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: white;
            padding: 26px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 30px;
        }
        .content {
            padding: 28px 24px 24px;
        }
        .agent-box {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
        }
        .agent-box strong {
            display: block;
            font-size: 12px;
            color: #475569;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .agent-box span {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }
        label {
            display: block;
            margin: 14px 0 8px;
            color: #374151;
            font-weight: 700;
        }
        input {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 15px;
        }
        input[readonly] {
            background: #f3f4f6;
            color: #374151;
        }
        button {
            margin-top: 18px;
            width: 100%;
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
        }
        .error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: 600;
        }
        .small {
            font-size: 12px;
            color: #6b7280;
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <h1>💰 Easy Loan</h1>
        </div>

        <div class="content">
            <?php if ($errorMessage !== ''): ?>
                <div class="error"><?= sanitize($errorMessage) ?></div>
            <?php else: ?>
                <form method="POST">
                    <div class="agent-box">
                        <strong>Referred By</strong>
                        <span><?= sanitize($agent['name'] ?? '') ?></span>
                    </div>

                    <?php if ($formError !== ''): ?>
                        <div class="error"><?= sanitize($formError) ?></div>
                    <?php endif; ?>

                    <label for="agent_name">Agent Name</label>
                    <input type="text" id="agent_name" value="<?= sanitize($agent['name'] ?? '') ?>" readonly>

                    <label for="customer_name">Customer Name</label>
                    <input type="text" id="customer_name" name="customer_name" placeholder="Enter full name" value="<?= sanitize($_POST['customer_name'] ?? '') ?>" required>

                    <label for="customer_phone">Mobile Number</label>
                    <input type="tel" id="customer_phone" name="customer_phone" placeholder="Enter 10-digit mobile number" value="<?= sanitize($_POST['customer_phone'] ?? '') ?>" required>

                    <label for="loan_amount">Loan Amount</label>
                    <input type="number" id="loan_amount" name="loan_amount" min="1" step="1" placeholder="Enter loan amount" value="<?= sanitize($_POST['loan_amount'] ?? '') ?>" required>

                    <button type="submit">Submit Application</button>

                    <div class="small">By submitting, you agree to proceed to the loan portal with your data.</div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>