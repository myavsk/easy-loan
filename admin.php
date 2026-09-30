<?php
require_once __DIR__ . '/config.php';

$data = loadData();
$message = '';
$messageType = 'success';

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['download_qr_zip']) && $_GET['download_qr_zip'] === '1') {
    $qrList = $data['qr_codes'] ?? [];
    if (empty($qrList)) {
        die('No QR codes found.');
    }

    $zipPath = createQRZip($qrList);
    if (!$zipPath) {
        die('ZIP creation failed.');
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="qr_codes.zip"');
    header('Content-Length: ' . filesize($zipPath));
    readfile($zipPath);
    unlink($zipPath);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_password'])) {
    if ($_POST['login_password'] === DEFAULT_PASSWORD) {
        $_SESSION['admin_login'] = true;
        $_SESSION['login_time'] = time();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $message = 'Invalid password.';
        $messageType = 'error';
    }
}

if (isLoggedIn() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_portal_url') {
        $url = trim($_POST['portal_url'] ?? '');
        if (!isValidUrl($url)) {
            $message = 'Invalid portal URL.';
            $messageType = 'error';
        } else {
            $data['portal_url'] = $url;
            if (saveData($data)) {
                $message = 'Portal URL updated successfully.';
                $messageType = 'success';
            } else {
                $message = 'Failed to save portal URL.';
                $messageType = 'error';
            }
        }
    }

    if ($action === 'add_agent') {
        $code = trim($_POST['agent_code'] ?? '');
        $name = trim($_POST['agent_name'] ?? '');

        if ($code === '' || $name === '') {
            $message = 'Agent code and name are required.';
            $messageType = 'error';
        } else {
            $exists = findAgentByCode($code, $data);

            if ($exists) {
                $message = 'Agent code already exists.';
                $messageType = 'error';
            } else {
                $data['agents'][] = [
                    'code' => strtoupper($code),
                    'name' => $name,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                if (saveData($data)) {
                    $message = 'Agent added successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to save agent.';
                    $messageType = 'error';
                }
            }
        }
    }

    if ($action === 'delete_agent') {
        $code = trim($_POST['agent_code'] ?? '');
        $filtered = [];

        foreach ($data['agents'] as $agent) {
            if (($agent['code'] ?? '') !== $code) {
                $filtered[] = $agent;
            }
        }

        $data['agents'] = $filtered;

        foreach ($data['qr_codes'] as &$qr) {
            if (($qr['assigned_agent_code'] ?? '') === $code) {
                $qr['assigned_agent_code'] = null;
                $qr['assigned_agent_name'] = null;
                $qr['status'] = 'unassigned';
                $qr['updated_at'] = date('Y-m-d H:i:s');
            }
        }
        unset($qr);

        if (saveData($data)) {
            $message = 'Agent deleted successfully.';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete agent.';
            $messageType = 'error';
        }
    }

    if ($action === 'generate_qr_batch') {
        $prefix = trim($_POST['prefix'] ?? 'QR');
        $prefix = $prefix === '' ? 'QR' : strtoupper($prefix);
        $start = (int)($_POST['start'] ?? 1);
        $end = (int)($_POST['end'] ?? 1);

        if ($start < 1 || $end < $start) {
            $message = 'Invalid QR range.';
            $messageType = 'error';
        } else {
            $generated = 0;

            for ($i = $start; $i <= $end; $i++) {
                $code = $prefix . '_' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);

                $exists = findQRByCode($code, $data);
                if ($exists) {
                    continue;
                }

                $qr = [
                    'code' => $code,
                    'status' => 'unassigned',
                    'assigned_agent_code' => null,
                    'assigned_agent_name' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                $data['qr_codes'][] = $qr;
                generateQRImage(getBaseUrl() . 'index.php?qr=' . urlencode($code), $code);
                $generated++;
            }

            if (saveData($data)) {
                $message = $generated . ' QR codes generated successfully.';
                $messageType = 'success';
            } else {
                $message = 'QR codes generated but failed to save.';
                $messageType = 'error';
            }
        }
    }

    if ($action === 'assign_qr') {
        $qrCode = trim($_POST['qr_code'] ?? '');
        $agentCode = trim($_POST['agent_code'] ?? '');

        if ($qrCode === '' || $agentCode === '') {
            $message = 'Please select QR code and agent.';
            $messageType = 'error';
        } else {
            $qr = findQRByCode($qrCode, $data);
            $agent = findAgentByCode($agentCode, $data);

            if (!$qr) {
                $message = 'Selected QR code not found.';
                $messageType = 'error';
            } elseif (!$agent) {
                $message = 'Selected agent not found.';
                $messageType = 'error';
            } else {
                $qr['assigned_agent_code'] = $agent['code'];
                $qr['assigned_agent_name'] = $agent['name'];
                $qr['status'] = 'assigned';
                $qr['updated_at'] = date('Y-m-d H:i:s');

                if (saveData($data)) {
                    $message = 'QR code assigned successfully.';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to assign QR code.';
                    $messageType = 'error';
                }
            }
        }
    }

    $data = loadData();
}

$baseUrl = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easy Loan Admin</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            color: #111827;
        }
        .wrap {
            width: min(1200px, 92%);
            margin: 40px auto;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 16px 50px rgba(0,0,0,.25);
        }
        .topbar {
            background: linear-gradient(135deg, #1e3a8a, #2563eb);
            color: white;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .topbar h1 {
            margin: 0;
            font-size: 28px;
        }
        .logout-btn, button, .btn {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
        }
        .logout-btn { background: #dc2626; }
        .section {
            padding: 24px 32px 12px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }
        .card {
            border: 1px solid #e5e7eb;
            background: #f9fafb;
            border-radius: 12px;
            padding: 18px;
        }
        .card h3 {
            margin-top: 0;
            font-size: 18px;
        }
        label {
            display: block;
            margin: 10px 0 6px;
            font-weight: 600;
            color: #374151;
        }
        input, select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
        }
        .message {
            margin: 18px 32px 0;
            padding: 12px 14px;
            border-radius: 8px;
            font-weight: 600;
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
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 18px;
        }
        th, td {
            padding: 12px 10px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
            vertical-align: top;
            font-size: 14px;
            word-break: break-word;
        }
        th {
            background: #f3f4f6;
            color: #374151;
        }
        .status {
            padding: 5px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            display: inline-block;
        }
        .assigned { background: #dcfce7; color: #166534; }
        .unassigned { background: #fef3c7; color: #92400e; }
        .link {
            color: #1d4ed8;
            text-decoration: none;
            word-break: break-all;
        }
        .small {
            font-size: 12px;
            color: #6b7280;
        }
        .btn-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .topbar {
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="topbar">
        <h1>💰 Easy Loan Admin</h1>
        <a href="?action=logout" class="logout-btn">Logout</a>
    </div>

    <?php if (!isLoggedIn()): ?>
        <div class="section" style="max-width: 420px; margin: 40px auto;">
            <div class="card">
                <h3>🔐 Admin Login</h3>
                <?php if ($message !== ''): ?>
                    <div class="message <?= sanitize($messageType) ?>"><?= sanitize($message) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <label for="login_password">Password</label>
                    <input type="password" name="login_password" id="login_password" required autofocus>
                    <div class="btn-row" style="margin-top: 16px;">
                        <button type="submit">Login</button>
                    </div>
                </form>
                <p class="small" style="margin-top: 14px;">Default password: <strong>admin@123</strong></p>
            </div>
        </div>
    <?php else: ?>
        <?php if ($message !== ''): ?>
            <div class="message <?= sanitize($messageType) ?>"><?= sanitize($message) ?></div>
        <?php endif; ?>

        <div class="section">
            <div class="grid">
                <div class="card">
                    <h3>🏷️ Set Target Portal URL</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_portal_url">
                        <label for="portal_url">Portal URL</label>
                        <input type="url" name="portal_url" id="portal_url" value="<?= sanitize($data['portal_url'] ?? '') ?>" required>
                        <div class="btn-row">
                            <button type="submit">Update URL</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3>👤 Add Agent</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_agent">
                        <label>Agent Code</label>
                        <input type="text" name="agent_code" placeholder="AG101" required>
                        <label>Agent Name</label>
                        <input type="text" name="agent_name" placeholder="Amit Kumar" required>
                        <div class="btn-row">
                            <button type="submit">Add Agent</button>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3>📦 Generate Bulk QR Codes</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="generate_qr_batch">
                        <label>Prefix</label>
                        <input type="text" name="prefix" value="QR" required>
                        <label>Start Number</label>
                        <input type="number" name="start" min="1" value="1" required>
                        <label>End Number</label>
                        <input type="number" name="end" min="1" value="100" required>
                        <div class="btn-row">
                            <button type="submit">Generate QR</button>
                            <a class="btn" href="?download_qr_zip=1">Download ZIP</a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3>🔗 Assign QR to Agent</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="assign_qr">
                        <label>QR Code</label>
                        <select name="qr_code" required>
                            <option value="">Select QR</option>
                            <?php foreach (($data['qr_codes'] ?? []) as $qr): ?>
                                <option value="<?= sanitize($qr['code']) ?>">
                                    <?= sanitize($qr['code']) ?> (<?= sanitize(($qr['status'] ?? 'unassigned')) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Agent</label>
                        <select name="agent_code" required>
                            <option value="">Select Agent</option>
                            <?php foreach (($data['agents'] ?? []) as $agent): ?>
                                <option value="<?= sanitize($agent['code']) ?>">
                                    <?= sanitize($agent['code']) ?> - <?= sanitize($agent['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="btn-row">
                            <button type="submit">Assign QR</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="section">
            <h3 style="margin: 0 0 12px;">📋 QR Code Management Table</h3>
            <table>
                <thead>
                <tr>
                    <th>QR Code</th>
                    <th>Assigned Agent</th>
                    <th>Status</th>
                    <th>Live Link</th>
                    <th>Image</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($data['qr_codes'] ?? []) as $qr): ?>
                    <?php
                    $assignedCode = $qr['assigned_agent_code'] ?? null;
                    $agent = $assignedCode ? findAgentByCode($assignedCode, $data) : null;
                    $link = $baseUrl . 'index.php?qr=' . urlencode($qr['code']);
                    $status = $qr['status'] ?? 'unassigned';
                    $statusClass = ($status === 'assigned') ? 'assigned' : 'unassigned';
                    $imgUrl = 'qr_codes/' . $qr['code'] . '.png';
                    ?>
                    <tr>
                        <td><strong><?= sanitize($qr['code']) ?></strong></td>
                        <td>
                            <?php if ($agent): ?>
                                <?= sanitize($agent['code']) ?> - <?= sanitize($agent['name']) ?>
                            <?php else: ?>
                                <span class="small">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status <?= $statusClass ?>"><?= sanitize($status) ?></span>
                        </td>
                        <td>
                            <a class="link" href="<?= sanitize($link) ?>" target="_blank"><?= sanitize($link) ?></a>
                        </td>
                        <td>
                            <?php if (file_exists(__DIR__ . '/qr_codes/' . $qr['code'] . '.png')): ?>
                                <img src="<?= sanitize($imgUrl) ?>" alt="<?= sanitize($qr['code']) ?>" width="50" height="50">
                            <?php else: ?>
                                <span class="small">No image</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="section">
            <h3 style="margin: 0 0 12px;">👥 Agent List</h3>
            <table>
                <thead>
                <tr>
                    <th>Agent Code</th>
                    <th>Agent Name</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach (($data['agents'] ?? []) as $agent): ?>
                    <tr>
                        <td><?= sanitize($agent['code']) ?></td>
                        <td><?= sanitize($agent['name']) ?></td>
                        <td>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="delete_agent">
                                <input type="hidden" name="agent_code" value="<?= sanitize($agent['code']) ?>">
                                <button type="submit" style="background:#dc2626;">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>