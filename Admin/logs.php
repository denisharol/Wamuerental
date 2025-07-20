<?php include 'auth_admin.php'; ?>
<?php
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch recent logs
$logsQuery = "SELECT * FROM logs ORDER BY created_at DESC LIMIT 100";
$logsResult = $conn->query($logsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f6f8; margin: 0; padding: 0; }
        .container { max-width: 1100px; margin: auto; padding: 20px; }
        h1 { margin-bottom: 20px; }
        .logs-table-container { background-color: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { padding: 12px 10px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background-color: #2980b9; color: white; }
        pre { margin: 0; font-size: 12px; white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Activity Logs</h1>
        <div class="logs-table-container">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User Type</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Page</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logsResult->num_rows > 0): ?>
                        <?php while ($log = $logsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['created_at']) ?></td>
                                <td><?= htmlspecialchars($log['user_type']) ?></td>
                                <td><?= htmlspecialchars($log['user_identifier']) ?></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= htmlspecialchars($log['page']) ?></td>
                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td><pre><?= htmlspecialchars($log['details']) ?></pre></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>