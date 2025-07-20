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

// Fetch transactions specific to the logged-in user
$transactionsQuery = "SELECT transaction_code, payment_method, phone_number, name, amount, date 
                      FROM transactions 
                      WHERE tenant_id = ?";
$stmt = $conn->prepare($transactionsQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactionsResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Transactions</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-6 font-sans">

  <h1 class="text-[18px] font-semibold text-gray-800 mb-6">My Transactions</h1>

  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left">
      <thead>
        <tr class="text-gray-600 bg-gray-100">
          <th class="px-4 py-3">Transaction Code</th>
          <th class="px-4 py-3">Payment Method</th>
          <th class="px-4 py-3">Phone Number</th>
          <th class="px-4 py-3">Name</th>
          <th class="px-4 py-3">Amount</th>
          <th class="px-4 py-3">Date</th>
        </tr>
      </thead>
      <tbody class="text-gray-700">
        <?php if ($transactionsResult->num_rows > 0): ?>
          <?php while ($transaction = $transactionsResult->fetch_assoc()): ?>
            <tr>
              <td class="px-4 py-3"><?= htmlspecialchars($transaction['transaction_code']) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($transaction['payment_method']) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($transaction['phone_number']) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($transaction['name']) ?></td>
              <td class="px-4 py-3"><?= number_format($transaction['amount'], 2) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($transaction['date']) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center py-4 text-gray-500">No transactions found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</body>
</html>