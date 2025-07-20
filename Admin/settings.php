<?php
include_once 'auth_admin.php';

$conn = new mysqli("localhost", "root", "", "demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = $error = "";

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $userId = intval($_POST['user_id']);
    $newPassword = $_POST['new_password'];

    if (!$userId || empty($newPassword)) {
        $error = "Please select a user and enter a new password.";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $userId);
        if ($stmt->execute()) {
            $success = "Password updated successfully.";
        } else {
            $error = "Failed to update password.";
        }
        $stmt->close();
    }
}

// Handle admin password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_admin_password'])) {
    $old = $_POST['old_admin_password'] ?? '';
    $new = $_POST['new_admin_password'] ?? '';
    $admin_id = $_SESSION['admin_id'];

    $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $stmt->bind_result($currentHash);
    $stmt->fetch();
    $stmt->close();

    if (!$old || !$new) {
        $error = "Please fill in all admin password fields.";
    } elseif (strlen($new) < 8) {
        $error = "Admin password must be at least 8 characters.";
    } elseif (!password_verify($old, $currentHash)) {
        $error = "Current admin password is incorrect.";
    } else {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $newHash, $admin_id);
        if ($stmt->execute()) {
            $success = "Admin password changed successfully.";
        } else {
            $error = "Failed to change admin password.";
        }
        $stmt->close();
    }
}

// Fetch all users for dropdown
$users = $conn->query("SELECT id, name, email FROM users WHERE id != 1 ORDER BY name ASC");

// Fetch system stats
$totalUsers = $conn->query("SELECT COUNT(*) as c FROM users WHERE id != 1")->fetch_assoc()['c'];
$totalProperties = $conn->query("SELECT COUNT(*) as c FROM properties")->fetch_assoc()['c'];
$totalUnits = $conn->query("SELECT COUNT(*) as c FROM units")->fetch_assoc()['c'];
$totalVacant = $conn->query("SELECT COUNT(*) as c FROM units WHERE status = 'Vacant'")->fetch_assoc()['c'];
$totalAdmins = $conn->query("SELECT COUNT(*) as c FROM admin")->fetch_assoc()['c'];

// Fetch last 5 admin actions (if you have a log_action table)
$recentActions = [];
if ($conn->query("SHOW TABLES LIKE 'log_action'")->num_rows) {
    $res = $conn->query("SELECT * FROM log_action WHERE user_type='admin' ORDER BY timestamp DESC LIMIT 5");
    while ($row = $res->fetch_assoc()) $recentActions[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings</title>
    <style>
        body {
            background: #fff;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .settings-container {
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.07);
            padding: 32px 28px 28px 28px;
        }
        .settings-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 28px;
            color: #222;
        }
        .form-row {
            display: flex;
            align-items: center;
            margin-bottom: 22px;
        }
        .form-row label {
            min-width: 110px;
            font-weight: 500;
            color: #333;
        }
        .readonly-input {
            background: #f3f3f3;
            border: 1px solid #ccc;
            color: #888;
            border-radius: 6px;
            padding: 8px 12px;
            width: 220px;
            margin-left: 10px;
        }
        .section-title {
            font-size: 1.1rem;
            font-weight: 500;
            margin: 30px 0 12px 0;
            color: #1a237e;
        }
        .password-form, .admin-password-form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .password-form select, .password-form input[type="password"],
        .admin-password-form input[type="password"] {
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }
        .password-form button, .admin-password-form button {
            background: #001f91;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.2s;
        }
        .password-form button:hover, .admin-password-form button:hover {
            background: #001870;
        }
        .success-message {
            background: #e6ffed;
            color: #1a7f37;
            padding: 10px 16px;
            border-radius: 6px;
            margin-bottom: 18px;
        }
        .error-message {
            background: #ffeaea;
            color: #c00;
            padding: 10px 16px;
            border-radius: 6px;
            margin-bottom: 18px;
        }
        .info-box {
            background: #f5f7fa;
            color: #555;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.97rem;
            margin-bottom: 18px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px 12px;
            text-align: center;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .stat-label {
            font-size: 0.98rem;
            color: #555;
        }
        .stat-value {
            font-size: 1.3rem;
            font-weight: 600;
            color: #001f91;
        }
        .recent-actions {
            margin-top: 18px;
        }
        .recent-actions table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.97rem;
        }
        .recent-actions th, .recent-actions td {
            padding: 7px 8px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }
        .recent-actions th {
            background: #f5f7fa;
            font-weight: 600;
        }
        .recent-actions tr:last-child td {
            border-bottom: none;
        }
    </style>
</head>
<body>
<div class="settings-container">
    <div class="settings-title">Settings</div>

    <?php if ($success): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="form-row">
        <label for="adminName">Name</label>
        <input type="text" id="adminName" class="readonly-input" value="Administrator" readonly tabindex="-1">
    </div>

    <div class="section-title">System Overview</div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= $totalUsers ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Properties</div>
            <div class="stat-value"><?= $totalProperties ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Units</div>
            <div class="stat-value"><?= $totalUnits ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Vacant Units</div>
            <div class="stat-value"><?= $totalVacant ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Admins</div>
            <div class="stat-value"><?= $totalAdmins ?></div>
        </div>
    </div>

    <div class="section-title">Change User Password</div>
    <div class="info-box">
        Select a user and set a new password. Password must be at least 8 characters.
    </div>
    <form method="POST" class="password-form" autocomplete="off">
        <select name="user_id" required>
            <option value="">Select User</option>
            <?php while($user = $users->fetch_assoc()): ?>
                <option value="<?= $user['id'] ?>">
                    <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                </option>
            <?php endwhile; ?>
        </select>
        <input type="password" name="new_password" placeholder="New Password" minlength="8" required>
        <button type="submit" name="change_password">Update</button>
    </form>

    <div class="section-title">Change Admin Password</div>
    <div class="info-box">
        Change your own admin password. Password must be at least 8 characters.
    </div>
    <form method="POST" class="admin-password-form" autocomplete="off">
        <input type="password" name="old_admin_password" placeholder="Current Password" required>
        <input type="password" name="new_admin_password" placeholder="New Password" minlength="8" required>
        <button type="submit" name="change_admin_password">Change</button>
    </form>

    <div class="section-title">Security</div>
    <div class="info-box">
        <ul style="margin:0 0 0 18px;padding:0;">
            <li>Only logged-in admins can access this page.</li>
            <li>All password changes are immediate and cannot be undone.</li>
            <li>For best security, use strong passwords for all users.</li>
            <li>Log out after making sensitive changes.</li>
        </ul>
    </div>

    <?php if ($recentActions): ?>
    <div class="section-title">Recent Admin Actions</div>
    <div class="recent-actions">
        <table>
            <tr>
                <th>Action</th>
                <th>Description</th>
                <th>Date</th>
            </tr>
            <?php foreach ($recentActions as $action): ?>
                <tr>
                    <td><?= htmlspecialchars($action['action']) ?></td>
                    <td><?= htmlspecialchars($action['description']) ?></td>
                    <td><?= date('Y-m-d H:i', strtotime($action['timestamp'])) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
</div>
</body>
</html>