<?php include 'auth_admin.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Invoices</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        .download-link {
            color: grey;
            text-decoration: underline;
            background: none;
            border: none;
            cursor: pointer;
            font-size: inherit;
            padding: 0;
        }

        .download-link:hover {
            color: darkgrey;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>All Tenant Invoices</h1>
        </header>

        <div class="filters">
            <h3>Filter Invoices</h3>
            <form method="GET" action="">
                <label for="property">Property</label>
                <select id="property" name="property" onchange="this.form.submit()">
                    <option value="">All properties</option>
                    <?php
                    $conn = new mysqli("localhost", "root", "", "Demo1");
                    if ($conn->connect_error) {
                        die("Connection failed: " . $conn->connect_error);
                    }

                    // Fetch distinct properties from the database
                    $propertyQuery = $conn->query("SELECT DISTINCT property FROM properties");
                    while ($row = $propertyQuery->fetch_assoc()) {
                        $selected = (isset($_GET['property']) && $_GET['property'] === $row['property']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($row['property']) . "' $selected>" . htmlspecialchars($row['property']) . "</option>";
                    }
                    ?>
                </select>
            </form>
        </div>

        <?php
        // Filter logic
        $selectedProperty = isset($_GET['property']) ? $_GET['property'] : '';

        // Query to fetch users based on the selected property, excluding caretaker ID
        $usersQuery = "SELECT * FROM users WHERE id != 1 AND id NOT IN (SELECT id FROM caretaker)";
        if (!empty($selectedProperty)) {
            $usersQuery .= " AND property = '" . $conn->real_escape_string($selectedProperty) . "'";
        }
        $users = $conn->query($usersQuery);

        $invoiceMonth = date("Ym");
        $invoiceDate = date("Y-m-01");

        $paidCount = 0;
        $unpaidCount = 0;

        // --- 1. Build arrears array for all tenants ---
        $arrears = [];
        $arrearsQuery = "
            SELECT 
                u.id AS tenant_id,
                u.rent_amount,
                u.move_in_date,
                IFNULL(b.wifi, 0) AS wifi,
                IFNULL(b.water, 0) AS water
            FROM users u
            LEFT JOIN billing b ON u.property = b.property
            WHERE u.id != 1 AND u.id NOT IN (SELECT id FROM caretaker)
        ";
        $arrearsResult = $conn->query($arrearsQuery);
        if ($arrearsResult->num_rows > 0) {
            while ($tenant = $arrearsResult->fetch_assoc()) {
                $userId = $tenant['tenant_id'];
                $rentAmount = floatval($tenant['rent_amount']);
                $wifiAmount = floatval($tenant['wifi']);
                $waterAmount = floatval($tenant['water']);
                $moveInDate = new DateTime($tenant['move_in_date']);
                $now = new DateTime();

                $monthlyCharge = $rentAmount + $wifiAmount + $waterAmount;
                $months = ($now->format('Y') - $moveInDate->format('Y')) * 12 + ($now->format('n') - $moveInDate->format('n')) + 1;
                $required = $months * $monthlyCharge;
                if ($months >= 1) {
                    $required += $rentAmount;
                }

                // Get total paid
                $transactionStmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE tenant_id = ?");
                $transactionStmt->bind_param("i", $userId);
                $transactionStmt->execute();
                $transactionStmt->bind_result($totalPaid);
                $transactionStmt->fetch();
                $transactionStmt->close();
                $totalPaid = floatval($totalPaid ?? 0);

                $amountDue = max(0, $required - $totalPaid);
                $arrears[$userId] = $amountDue;
            }
        }

        ob_start(); // Start output buffering to capture table rows
        while ($user = $users->fetch_assoc()) {
            $userId = $user['id'];
            $name = $user['name'];
            $property = $user['property'];
            $unit = $user['unit'];
            $rentAmount = $user['rent_amount'];
            $moveInDate = $user['move_in_date'];

            // Get Wi-Fi and Water
            $billingStmt = $conn->prepare("SELECT wifi, water FROM billing WHERE property = ?");
            $billingStmt->bind_param("s", $property);
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

            // Instead of calculating $amountDue here, use arrears array:
            $rentArrears = isset($arrears[$userId]) ? $arrears[$userId] : 0.00;

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
                <td>{$property} ({$unit})</td>
                <td>{$status}</td>
                <td>{$totalAmount}</td>
                <td>" . number_format($rentArrears, 2) . " KES</td>
                <td>
                    <button class='download-link' onclick='generatePDF(\"{$invoiceNo}\", \"{$name}\", \"{$property}\", \"{$unit}\", \"{$invoiceDate}\", \"{$status}\", \"{$rentAmount}\", \"{$wifiAmount}\", \"{$waterAmount}\", \"{$totalAmount}\", \"" . number_format($rentArrears, 2) . "\")'>
                        Download
                    </button>
                </td>
            </tr>";
        }
        $tableRows = ob_get_clean(); // Get the captured table rows
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
                        <th>Property (Unit)</th>
                        <th>Status</th>
                        <th>Rent Amount</th>
                        <th>Rent Arrears</th>
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
        function generatePDF(invoiceNo, tenantName, property, unit, invoiceDate, status, rentAmount, wifiAmount, waterAmount, totalAmount, amountDue) {
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
            doc.text(`Property: ${property}`, 14, 86);
            doc.text(`Unit: ${unit}`, 14, 94);

            doc.text("Charges:", 14, 110);
            doc.text(`Rent: ${parseFloat(rentAmount).toFixed(2)} KES`, 14, 118);
            doc.text(`Wi-Fi: ${parseFloat(wifiAmount).toFixed(2)} KES`, 14, 126);
            doc.text(`Water: ${parseFloat(waterAmount).toFixed(2)} KES`, 14, 134);
            doc.text(`Total: ${parseFloat(totalAmount).toFixed(2)} KES`, 14, 142);

            doc.text("Balance:", 14, 158);
            doc.text(`Amount Due: ${parseFloat(amountDue).toFixed(2)} KES`, 14, 166);

            doc.text("Thank you for your payment!", 14, 190);

            doc.save(`${invoiceNo}.pdf`);
        }
    </script>
</body>
</html>