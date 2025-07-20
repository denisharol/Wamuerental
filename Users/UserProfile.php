<?php
session_start();

$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check for accepted report and update security deposit
$reportStmt = $conn->prepare("
    SELECT remaining_deposit 
    FROM assesment_report 
    WHERE tenant_id = ? AND status = 'Accepted'
    LIMIT 1
");
$reportStmt->bind_param("i", $user_id);
$reportStmt->execute();
$reportStmt->bind_result($remainingDeposit);

if ($reportStmt->fetch()) {
    $reportStmt->close();

    $updateStmt = $conn->prepare("
        UPDATE users 
        SET security_deposit = ? 
        WHERE id = ?
    ");
    $updateStmt->bind_param("di", $remainingDeposit, $user_id);
    $updateStmt->execute();
    $updateStmt->close();
} else {
    $reportStmt->close();
}

// Fetch tenant details
$stmt = $conn->prepare("SELECT name, property, unit, rent_amount, security_deposit, move_in_date FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($name, $property, $unit, $rentAmount, $securityDeposit, $moveInDate);
$stmt->fetch();
$stmt->close();

// Fetch billing amounts
$billingStmt = $conn->prepare("SELECT wifi, water, electricity FROM billing WHERE property = ?");
$billingStmt->bind_param("s", $property);
$billingStmt->execute();
$billingStmt->bind_result($wifiAmount, $waterAmount, $electricityAmount);
$billingStmt->fetch();
$billingStmt->close();

$wifiAmount = floatval($wifiAmount ?? 0);
$waterAmount = floatval($waterAmount ?? 0);
$electricityAmount = floatval($electricityAmount ?? 0);

// Monthly total (rent + utilities)
$monthlyCharge = $rentAmount + $wifiAmount + $waterAmount + $electricityAmount;

// Calculate months elapsed
$moveIn = new DateTime($moveInDate);
$now = new DateTime();
$months = ($now->format('Y') - $moveIn->format('Y')) * 12 + ($now->format('n') - $moveIn->format('n')) + 1;

// Total required amount
$required = $months * $monthlyCharge;

// Add security deposit once for first month
if ($months >= 1) {
    $required += $rentAmount;
}

// Get total paid by tenant
$transactionStmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE tenant_id = ?");
$transactionStmt->bind_param("i", $user_id);
$transactionStmt->execute();
$transactionStmt->bind_result($totalPaid);
$transactionStmt->fetch();
$transactionStmt->close();
$totalPaid = floatval($totalPaid ?? 0);

// Calculate overpayment and amount due
$overpayment = $totalPaid - $required;
$amountDue = max(0, $required - $totalPaid);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">
    <title>Property Dashboard</title>
</head>
<body class="bg-gray-100 flex justify-center min-h-screen pt-6 px-4 md:px-0">
    <div class="w-full max-w-4xl bg-white shadow-lg rounded-lg p-6 md:p-10">
        <div class="border-l-4 border-blue-500 pl-4 md:pl-6 mb-6 md:mb-8">
            <h2 class="text-xl md:text-2xl font-semibold">Welcome, <?= htmlspecialchars($name) ?></h2>
            <p class="text-gray-600 text-base md:text-lg">Property: <span class="font-medium"><?= htmlspecialchars($property) ?></span> | Unit: <span class="font-medium"><?= htmlspecialchars($unit) ?></span></p>
        </div>

        <div class="bg-gray-50 p-4 md:p-8 shadow-md rounded-md space-y-6">
            <p class="text-gray-500 text-sm font-semibold">Current Property Details:</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-8">
                <div class="bg-white p-6 md:p-8 shadow-lg rounded-md border border-gray-300">
                    <h3 class="text-gray-600 font-semibold text-base md:text-lg">Over-Payment</h3>
                    <p class="text-3xl md:text-4xl font-light text-black-700 mt-2 md:mt-4"><?= $overpayment > 0 ? number_format($overpayment, 2) : '0.00' ?> KES</p>
                </div>
                <div class="bg-white p-6 md:p-8 shadow-lg rounded-md border border-gray-300">
                    <h3 class="text-gray-600 font-semibold text-base md:text-lg">Amount Due</h3>
                    <p class="text-3xl md:text-4xl font-light text-red-700 mt-2 md:mt-4"><?= number_format($amountDue, 2) ?> KES</p>
                </div>
                <div class="bg-white p-6 md:p-8 shadow-lg rounded-md border border-gray-300">
                    <h3 class="text-gray-600 font-semibold text-base md:text-lg">Security Deposit</h3>
                    <p class="text-3xl md:text-4xl font-light text-green-700 mt-2 md:mt-4"><?= number_format($securityDeposit, 2) ?> KES</p>
                </div>
            </div>
        </div>

        <div class="border-l-4 border-blue-500 pl-4 md:pl-6 mt-6 md:mt-8">
            <h3 class="text-lg md:text-xl font-semibold">Official Property Contacts</h3>
            <p class="text-gray-600 flex items-center text-base md:text-lg"><span class="mr-2">📞 +254798674509 / +254797194764</span></p>
            <p class="text-gray-600 flex items-center text-base md:text-lg"><span class="mr-2">✉️ reservations@topnotchguesthouse.com</span></p>
            <p class="text-gray-600 flex items-center text-base md:text-lg"><span class="mr-2">📍 Kwa Vonza, Kitui</span></p>
        </div>
    </div>
</body>
</html>
