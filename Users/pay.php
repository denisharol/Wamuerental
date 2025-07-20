<?php
session_start();
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch tenant info
$stmt = $conn->prepare("SELECT unit, property, rent_amount, move_in_date FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($unit, $property, $rentAmount, $moveInDate);
$stmt->fetch();
$stmt->close();

// Fetch billing
$billingStmt = $conn->prepare("SELECT wifi, water, electricity FROM billing WHERE property = ?");
$billingStmt->bind_param("s", $property);
$billingStmt->execute();
$billingStmt->bind_result($wifiAmount, $waterAmount, $electricityAmount);
$billingStmt->fetch();
$billingStmt->close();

// Monthly required amount
$requiredAmount = $rentAmount + $wifiAmount + $waterAmount + $electricityAmount;

// Calculate months since move-in
$startDate = new DateTime($moveInDate ?? date('Y-m-01'));
$currentDate = new DateTime();
$monthsElapsed = ($currentDate->format('Y') - $startDate->format('Y')) * 12 +
                 ($currentDate->format('n') - $startDate->format('n')) + 1;

// Total due with one-time security deposit
$totalDue = $monthsElapsed * $requiredAmount;
if ($monthsElapsed >= 1) {
    $totalDue += $rentAmount; // one-time security deposit
}

// Total amount paid
$transactionQuery = "SELECT SUM(amount) as total_paid FROM transactions WHERE tenant_id = ?";
$stmt = $conn->prepare($transactionQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($totalPaid);
$stmt->fetch();
$stmt->close();

$totalPaid = $totalPaid ?? 0;
$overpayment = $totalPaid - $totalDue;

// Determine total amount due
if ($overpayment > 0) {
    $totalAmount = 0; // If there is an overpayment, total amount due is 0
} else {
    $totalAmount = max(0, $totalDue - $totalPaid); // Otherwise, calculate the total amount due
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Method</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f8f8;
            display: flex;
            justify-content: center;
            padding: 40px;
        }
        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            width: 400px;
        }
        .title-container h2 {
            margin: 0;
            color: #333;
            font-size: 15px;
            text-align: left;
        }
        .title-container hr {
            margin: 10px 0;
            width: 90%;
            border: 0;
            border-top: 1px solid #ccc;
        }
        .form-group {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .form-group label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input[type="text"] {
            width: 48%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #f9f9f9;
        }
        input[readonly] {
            background-color: #f1f1f1;
            cursor: not-allowed;
        }
        .image-container {
            text-align: center;
            margin: 20px 0;
        }
        .image-container img {
            max-width: 100px;
        }
        button {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .button-container {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="title-container">
            <h2>Payment Method</h2>
            <hr>
        </div>
        <form id="mpesaForm">
            <h3>Billing Information</h3>
            <div class="form-group">
                <div>
                    <label for="rent">Rent</label>
                    <input type="text" id="rent" value="<?= number_format($rentAmount, 2) ?>" readonly>
                </div>
                <div>
                    <label for="wifi">Wi-Fi</label>
                    <input type="text" id="wifi" value="<?= number_format($wifiAmount, 2) ?>" readonly>
                </div>
            </div>
            <div class="form-group">
                <div>
                    <label for="water">Water</label>
                    <input type="text" id="water" value="<?= number_format($waterAmount, 2) ?>" readonly>
                </div>
                <div>
                    <label for="electricity">Electricity</label>
                    <input type="text" id="electricity" value="<?= number_format($electricityAmount, 2) ?>" readonly>
                </div>
            </div>

            <div class="form-group">
                <div>
                    <label for="overpayment">Overpayment</label>
                    <input type="text" id="overpayment" value="<?= number_format($overpayment, 2) ?>" readonly>
                </div>
            </div>

            <div class="form-group">
                <label for="total">Total Amount Due</label>
                <input type="text" id="total" value="<?= number_format($totalAmount, 2) ?>" readonly style="width: 100%;">
            </div>

            <div class="form-group">
                <label for="total">Pay</label>
                <input type="text" id="amount" placeholder="Enter Amount" required>
            </div>

            <div class="form-group image-container">
                <img src="/Demo/images/mpesa.png" alt="Mpesa">
            </div>
            <h3>Pay with Mpesa</h3>
            <div class="form-group">
                <input type="text" id="mpesa-phone" placeholder="Enter Phone Number" pattern="^(07|01)\d{8}$" required style="width: 100%;">
            </div>
            <div class="button-container">
                <button type="button" onclick="initiatePayment()">PAY</button>
            </div>
        </form>
    </div>

    <script>
        function initiatePayment() {
            const phone = document.getElementById('mpesa-phone').value.trim();
            const rawAmount = document.getElementById('total').value.replace(/,/g, '');
            const amount = parseFloat(rawAmount);

            if (!phone.match(/^(07|01)\d{8}$/)) {
                alert('Enter a valid Safaricom number starting with 07 or 01.');
                return;
            }

            if (isNaN(amount) || amount <= 0) {
                alert('Invalid amount to pay.');
                return;
            }

            fetch('/Demo/mpesa/stk_push.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ phone, amount: amount.toFixed(2) })
            })
            .then(res => res.json())
            .then(data => {
                alert('STK Push Sent');
                console.log(data);
            })
            .catch(err => {
                alert('STK Push Failed');
                console.error(err);
            });
        }
    </script>
</body>
</html>