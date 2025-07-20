<?php
session_start();

// Connect to the database
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch tenant details
$stmt = $conn->prepare("SELECT name, unit, phone, rent_amount, property FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($tenant_name, $house_number, $phone_number, $rent_amount, $property);
$stmt->fetch();
$stmt->close();

// Fetch billing details
$billingStmt = $conn->prepare("SELECT wifi, water, electricity FROM billing WHERE property = ?");
$billingStmt->bind_param("s", $property);
$billingStmt->execute();
$billingStmt->bind_result($wifi, $water, $electricity);
$billingStmt->fetch();
$billingStmt->close();

$wifi = floatval($wifi ?? 0);
$water = floatval($water ?? 0);
$electricity = floatval($electricity ?? 0);

// Calculate total required amount
$total = $rent_amount + $wifi + $water + $electricity;

// Fetch overdue amount from UserProfile.php logic
$transactionStmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE tenant_id = ?");
$transactionStmt->bind_param("i", $user_id);
$transactionStmt->execute();
$transactionStmt->bind_result($totalPaid);
$transactionStmt->fetch();
$transactionStmt->close();

$totalPaid = floatval($totalPaid ?? 0);
$amountDue = max(0, $total - $totalPaid);

// Adjust rent and breakdown if overdue amount exists
if ($amountDue > 0) {
    $rent_amount = $total - $amountDue;
    $wifi = $water = $electricity = 0;
}

// Calculate required balance
$required_balance = max(0, $total - $totalPaid);

// Fetch distinct months for dropdown
$monthsQuery = $conn->prepare("
    SELECT DISTINCT DATE_FORMAT(date, '%M %Y') AS month, DATE_FORMAT(date, '%Y-%m') AS month_key
    FROM transactions
    WHERE tenant_id = ?
    ORDER BY month_key DESC
");
$monthsQuery->bind_param("i", $user_id);
$monthsQuery->execute();
$monthsResult = $monthsQuery->get_result();
$months = [];
while ($row = $monthsResult->fetch_assoc()) {
    $months[] = $row;
}
$monthsQuery->close();

$selected_month = $_GET['month'] ?? date('Y-m');
$selected_date = $_GET['date'] ?? null;

// Fetch all transactions for the selected month
$datesQuery = $conn->prepare("
    SELECT DISTINCT DATE_FORMAT(date, '%Y-%m-%d') AS paid_on
    FROM transactions
    WHERE tenant_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
    ORDER BY paid_on DESC
");
$datesQuery->bind_param("is", $user_id, $selected_month);
$datesQuery->execute();
$datesResult = $datesQuery->get_result();
$available_dates = [];
while ($row = $datesResult->fetch_assoc()) {
    $available_dates[] = $row['paid_on'];
}
$datesQuery->close();

// Fetch transactions
if ($selected_date) {
    $transactionsQuery = $conn->prepare("
        SELECT DATE_FORMAT(date, '%M %Y') AS month_paid, DATE_FORMAT(date, '%Y-%m-%d') AS paid_on, amount
        FROM transactions
        WHERE tenant_id = ? AND DATE(date) = ?
    ");
    $transactionsQuery->bind_param("is", $user_id, $selected_date);
} else {
    $transactionsQuery = $conn->prepare("
        SELECT DATE_FORMAT(date, '%M %Y') AS month_paid, DATE_FORMAT(date, '%Y-%m-%d') AS paid_on, amount
        FROM transactions
        WHERE tenant_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
        ORDER BY date ASC
    ");
    $transactionsQuery->bind_param("is", $user_id, $selected_month);
}
$transactionsQuery->execute();
$transactionsResult = $transactionsQuery->get_result();
$transactions = [];
while ($row = $transactionsResult->fetch_assoc()) {
    $transactions[] = $row;
}
$transactionsQuery->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tenant Receipt</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @media print {
      .no-print {
        display: none;
      }
    }
  </style>
</head>
<body class="bg-gray-100 p-6 font-sans text-sm text-gray-800">

<div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-sm border border-gray-200">
  <div class="flex justify-between items-center mb-6">
    <div>
      <h2 class="text-lg font-semibold">Tenant Payment Receipt</h2>
      <p class="text-xs text-gray-500">This serves as proof of payment for the stated period.</p>
    </div>
    <div class="text-right text-sm">
      <p><strong>Date:</strong> <?= htmlspecialchars($transactions[0]['paid_on'] ?? 'N/A') ?></p>
      <p><strong>Receipt #:</strong> <?= uniqid('REC-') ?></p>
    </div>
  </div>

  <div class="mb-6">
    <p><strong>Tenant Name:</strong> <?= htmlspecialchars($tenant_name) ?></p>
    <p><strong>House Number:</strong> <?= htmlspecialchars($house_number) ?></p>
    <p><strong>Phone Number:</strong> <?= htmlspecialchars($phone_number) ?></p>
    <p><strong>Month Paid For:</strong> <?= htmlspecialchars($transactions[0]['month_paid'] ?? 'N/A') ?></p>
  </div>

  <div class="mb-6">
    <table class="w-full text-left">
      <thead class="bg-gray-100 text-gray-600">
        <tr>
          <th class="py-2 px-4">Description</th>
          <th class="py-2 px-4">Amount (KES)</th>
        </tr>
      </thead>
      <tbody class="text-gray-700">
        <tr>
          <td class="py-2 px-4">Rent</td>
          <td class="py-2 px-4"><?= number_format($rent_amount, 2) ?></td>
        </tr>
        <tr>
          <td class="py-2 px-4">Wi-Fi</td>
          <td class="py-2 px-4"><?= number_format($wifi, 2) ?></td>
        </tr>
        <tr>
          <td class="py-2 px-4">Water</td>
          <td class="py-2 px-4"><?= number_format($water, 2) ?></td>
        </tr>
        <tr>
          <td class="py-2 px-4">Electricity</td>
          <td class="py-2 px-4"><?= number_format($electricity, 2) ?></td>
        </tr>
        <tr class="border-t border-gray-300 font-semibold">
          <td class="py-2 px-4">Total</td>
          <td class="py-2 px-4"><?= number_format($total, 2) ?></td>
        </tr>
        <tr>
          <td class="py-2 px-4">Required Balance</td>
          <td class="py-2 px-4"><?= number_format($required_balance, 2) ?></td>
        </tr>
      </tbody>
    </table>
  </div>

  <div class="mb-6">
    <p><strong>Payment Method:</strong> Mpesa</p>
    <p><strong>Paid On:</strong> <?= htmlspecialchars($transactions[0]['paid_on'] ?? 'N/A') ?></p>
  </div>

  <div class="flex justify-between items-end pt-8">
    <div>
      <p class="text-xs text-gray-500">This receipt is system-generated and does not require a signature.</p>
      <p class="mt-1 text-xs text-gray-500">Please keep this copy for your records.</p>
    </div>
    <div class="text-right">
      <p><strong>Authorized By:</strong></p>
      <p class="mt-2">Admin</p>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="mt-6 max-w-3xl mx-auto no-print">
  <form method="GET" class="mb-4">
    <label for="month" class="block text-sm font-medium text-gray-700 mb-1">Select Month</label>
    <select name="month" id="month" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" onchange="this.form.submit()">
      <option value="" disabled>Select a Month</option>
      <?php foreach ($months as $month): ?>
        <option value="<?= htmlspecialchars($month['month_key']) ?>" <?= $selected_month === $month['month_key'] ? 'selected' : '' ?>>
          <?= htmlspecialchars($month['month']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if (!empty($available_dates)): ?>
    <form method="GET" class="mb-4">
      <input type="hidden" name="month" value="<?= htmlspecialchars($selected_month) ?>">
      <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Select Transaction Date</label>
      <select name="date" id="date" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" onchange="this.form.submit()">
        <option value="">All Dates in <?= date("F Y", strtotime($selected_month . "-01")) ?></option>
        <?php foreach ($available_dates as $date): ?>
          <option value="<?= htmlspecialchars($date) ?>" <?= $selected_date === $date ? 'selected' : '' ?>>
            <?= htmlspecialchars($date) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>

  <div class="text-center">
    <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
      Print Receipt
    </button>
  </div>
</div>

</body>
</html>