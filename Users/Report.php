<?php
include 'auth_user.php'; // Ensure this file starts the session
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the logged-in user's ID from the session
$user_id = $_SESSION['user_id'];

$message = "";

// Handle Accept and Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    // Ensure the report belongs to the logged-in user
    $checkStmt = $conn->prepare("SELECT id FROM assesment_report WHERE id = ? AND tenant_id = ?");
    $checkStmt->bind_param("ii", $id, $user_id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        if ($action === 'reject') {
            // Set remaining_deposit to security_deposit and status to Rejected
            $stmt = $conn->prepare("
                UPDATE assesment_report 
                SET remaining_deposit = security_deposit, status = 'Rejected' 
                WHERE id = ?
            ");
        } elseif ($action === 'accept') {
            // Set status to Accepted
            $stmt = $conn->prepare("
                UPDATE assesment_report 
                SET status = 'Accepted' 
                WHERE id = ?
            ");
        }

        if (isset($stmt)) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "Report has been " . ($action === 'accept' ? "accepted" : "rejected") . ".";
            } else {
                $message = "Failed to update the report status.";
            }
            $stmt->close();
        }
    } else {
        $message = "You are not authorized to modify this report.";
    }

    $checkStmt->close();
}

// Fetch reports that belong to the logged-in user
$reports = $conn->prepare("
    SELECT id, unit, security_deposit, damages, repair_amount, payment_method, remaining_deposit, status
    FROM assesment_report
    WHERE tenant_id = ?
");
$reports->bind_param("i", $user_id);
$reports->execute();
$result = $reports->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            display: flex;
            justify-content: center;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        .action-form {
            display: inline;
        }

        .action-button {
            color: grey;
            text-decoration: underline;
            cursor: pointer;
            border: none;
            background: none;
            padding: 0;
            font-size: inherit;
        }

        .action-button:hover {
            color: darkgrey;
        }

        .message {
            background-color: #e0ffe0;
            color: #2b662b;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #a3d9a5;
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>Assessment Report</h1>
    </header>

    <?php if (!empty($message)): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Security Deposit</th>
                    <th>Damages</th>
                    <th>Repair Amount</th>
                    <th>Payment Method</th>
                    <th>Remaining Deposit</th>
                    <th>Status</th>
                    <th>Options</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($report = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($report['unit']) ?></td>
                        <td><?= htmlspecialchars($report['security_deposit']) ?></td>
                        <td><?= htmlspecialchars($report['damages']) ?></td>
                        <td><?= htmlspecialchars($report['repair_amount']) ?></td>
                        <td><?= htmlspecialchars($report['payment_method']) ?></td>
                        <td><?= htmlspecialchars($report['remaining_deposit']) ?></td>
                        <td><?= htmlspecialchars($report['status']) ?></td>
                        <td>
                            <?php if ($report['status'] === 'Pending'): ?>
                                <form class="action-form" method="POST">
                                    <input type="hidden" name="id" value="<?= $report['id'] ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="action-button">Accept</button>
                                </form>
                                |
                                <form class="action-form" method="POST">
                                    <input type="hidden" name="id" value="<?= $report['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="action-button">Reject</button>
                                </form>
                            <?php else: ?>
                                <?= htmlspecialchars($report['status']) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>