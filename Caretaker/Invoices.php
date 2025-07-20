<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch caretaker's property
$caretaker_id = $_SESSION['caretaker_id'];
$stmt = $conn->prepare("SELECT property FROM caretaker WHERE id = ?");
$stmt->bind_param("i", $caretaker_id);
$stmt->execute();
$stmt->bind_result($caretaker_property);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Invoices</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        header h1 {
            font-size: 24px;
            color: rgb(0, 0, 130);
            margin-bottom: 20px;
        }

        .table-container {
            margin-top: 20px;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px 16px;
            border: 1px solid #ddd;
            text-align: left;
        }

        table th {
            background-color: rgb(0, 0, 130);
            color: white;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .summary {
            margin-top: 20px;
            padding: 20px;
            background: #f1f1f1;
            border-radius: 8px;
        }

        .summary h2 {
            margin: 0 0 10px;
            color: rgb(0, 0, 130);
        }

        .download-link {
            color: rgb(0, 0, 130);
            text-decoration: underline;
            background: none;
            border: none;
            cursor: pointer;
            font-size: inherit;
            padding: 0;
        }

        .download-link:hover {
            color: rgb(0, 0, 100);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>All Tenant Invoices</h1>
        </header>
        
        <?php
        // Filter logic
        $selectedProperty = isset($_GET['property']) ? $_GET['property'] : $caretaker_property;

        // Query to fetch users based on the selected property, excluding the caretaker
        $usersQuery = "SELECT * FROM users WHERE property = ? AND id != ?";
        $stmt = $conn->prepare($usersQuery);
        $stmt->bind_param("si", $selectedProperty, $caretaker_id);
        $stmt->execute();
        $users = $stmt->get_result();

        $invoiceMonth = date("Ym");
        $invoiceDate = date("Y-m-01");

        $paidCount = 0;
        $unpaidCount = 0;

        ob_start(); // Start output buffering to capture table rows
        while ($user = $users->fetch_assoc()) {
            $userId = $user['id'];
            $name = $user['name'];
            $unit = $user['unit'];
            $rentAmount = $user['rent_amount'];
            $moveInDate = $user['move_in_date'];

            // Get Wi-Fi and Water
            $billingStmt = $conn->prepare("SELECT wifi, water FROM billing WHERE property = ?");
            $billingStmt->bind_param("s", $selectedProperty);
            $billingStmt->execute();
            $billingStmt->bind_result($wifiAmount, $waterAmount);
            $billingStmt->fetch();
            $billingStmt->close();

            $wifiAmount = $wifiAmount ?? 0;
            $waterAmount = $waterAmount ?? 0;

            // Calculate time in property
            $moveIn = new DateTime($moveInDate);
            $now = new DateTime();
            $months = $moveIn->diff($now)->y * 12 + $moveIn->diff($now)->m + 1;

            $required = $months * ($rentAmount + $wifiAmount + $waterAmount);

            // Get Total Paid
            $transactionStmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE tenant_id = ?");
            $transactionStmt->bind_param("i", $userId);
            $transactionStmt->execute();
            $transactionStmt->bind_result($totalPaid);
            $transactionStmt->fetch();
            $transactionStmt->close();

            $totalPaid = $totalPaid ?? 0;
            $overpayment = $totalPaid > $required ? $totalPaid - $required : 0;
            $amountDue = $required > $totalPaid ? $required - $totalPaid : 0;

            $status = ($totalPaid >= $required) ? 'Paid' : 'Unpaid';
            $invoiceNo = "INV{$invoiceMonth}{$userId}";
            $totalAmount = number_format($rentAmount + $wifiAmount + $waterAmount, 2);

            // Increment counters based on status
            if ($status === 'Paid') {
                $paidCount++;
            } else {
                $unpaidCount++;
            }

            echo "<tr>
                <td>{$invoiceDate}</td>
                <td>{$invoiceNo}</td>
                <td>{$name}</td>
                <td>{$unit}</td>
                <td>{$status}</td>
                <td>{$totalAmount}</td>
                <td>" . number_format($amountDue, 2) . " KES</td>
                <td>
                    <button class='download-link' onclick='generatePDF(\"{$invoiceNo}\", \"{$name}\", \"{$unit}\", \"{$invoiceDate}\", \"{$status}\", \"{$rentAmount}\", \"{$wifiAmount}\", \"{$waterAmount}\", \"{$totalAmount}\", \"{$amountDue}\")'>
                        Download
                    </button>
                </td>
            </tr>";
        }
        $tableRows = ob_get_clean(); // Get the captured table rows
        $stmt->close();
        $conn->close();
        ?>

        <div class="summary">
            <h2>Summary</h2>
            <p>Paid: <?= $paidCount ?></p>
            <p>Unpaid: <?= $unpaidCount ?></p>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice Number</th>
                        <th>Tenant</th>
                        <th>Unit</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Balances</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>
                    <?= $tableRows ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function generatePDF(invoiceNo, tenantName, unit, invoiceDate, status, rentAmount, wifiAmount, waterAmount, totalAmount, amountDue) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setFontSize(20);
            doc.text("INVOICE", 105, 20, null, null, "center");

            doc.setFontSize(12);
            doc.text(`Invoice Number: ${invoiceNo}`, 14, 40);
            doc.text(`Date: ${invoiceDate}`, 14, 48);
            doc.text(`Status: ${status}`, 14, 56);

            doc.text("Tenant Details:", 14, 70);
            doc.text(`Name: ${tenantName}`, 14, 78);
            doc.text(`Unit: ${unit}`, 14, 86);

            doc.text("Charges:", 14, 100);
            doc.text(`Rent: ${parseFloat(rentAmount).toFixed(2)} KES`, 14, 108);
            doc.text(`Wi-Fi: ${parseFloat(wifiAmount).toFixed(2)} KES`, 14, 116);
            doc.text(`Water: ${parseFloat(waterAmount).toFixed(2)} KES`, 14, 124);
            doc.text(`Total: ${parseFloat(totalAmount).toFixed(2)} KES`, 14, 132);

            doc.text("Balance:", 14, 148);
            doc.text(`Amount Due: ${parseFloat(amountDue).toFixed(2)} KES`, 14, 156);

            doc.text("Thank you for your payment!", 14, 180);

            doc.save(`${invoiceNo}.pdf`);
        }
    </script>
</body>
</html>