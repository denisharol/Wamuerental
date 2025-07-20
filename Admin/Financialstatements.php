<?php include 'auth_admin.php'; ?>
<?php
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Excel Download Logic ---
if (isset($_GET['download']) && $_GET['download'] === 'excel') {
    $propertyFilter = $_GET['property'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';

    $transactionsQuery = "SELECT transaction_code, phone_number, name, amount, date FROM transactions WHERE 1=1";
    if (!empty($propertyFilter)) {
        $propertyFilterEscaped = $conn->real_escape_string($propertyFilter);
        $transactionsQuery .= " AND property = '$propertyFilterEscaped'";
    }
    if (!empty($startDate) && !empty($endDate)) {
        $startDateEscaped = $conn->real_escape_string($startDate);
        $endDateEscaped = $conn->real_escape_string($endDate);
        $transactionsQuery .= " AND date BETWEEN '$startDateEscaped' AND '$endDateEscaped'";
    }
    $transactionsQuery .= " ORDER BY date DESC";
    $transactionsResult = $conn->query($transactionsQuery);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=transactions_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    // Output column headings
    fputcsv($output, ['Transaction Code', 'Phone Number', 'Name', 'Amount', 'Date']);
    if ($transactionsResult && $transactionsResult->num_rows > 0) {
        while ($row = $transactionsResult->fetch_assoc()) {
            fputcsv($output, [
                $row['transaction_code'],
                $row['phone_number'],
                $row['name'],
                number_format($row['amount'], 2),
                $row['date']
            ]);
        }
    }
    fclose($output);
    exit;
}
// --- End Excel Download Logic ---

// Fetch properties for the dropdown
$propertiesQuery = "SELECT DISTINCT property FROM transactions";
$propertiesResult = $conn->query($propertiesQuery);
$properties = [];
if ($propertiesResult->num_rows > 0) {
    while ($row = $propertiesResult->fetch_assoc()) {
        $properties[] = $row['property'];
    }
}

// Fetch transactions based on filters
$propertyFilter = $_GET['property'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$showTransactions = isset($_GET['submit']);

$transactions = [];
if ($showTransactions) {
    $transactionsQuery = "SELECT transaction_code, phone_number, name, amount, date FROM transactions WHERE 1=1";
    if (!empty($propertyFilter)) {
        $transactionsQuery .= " AND property = '$propertyFilter'";
    }
    if (!empty($startDate) && !empty($endDate)) {
        $transactionsQuery .= " AND date BETWEEN '$startDate' AND '$endDate'";
    }
    $transactionsQuery .= " ORDER BY date DESC";

    $transactionsResult = $conn->query($transactionsQuery);
    if ($transactionsResult->num_rows > 0) {
        while ($row = $transactionsResult->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Financial Statements</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .btn-primary {
      background-color: rgb(0, 0, 130);
    }
    .btn-primary:hover {
      background-color: rgb(0, 0, 100);
    }
    .btn-outline {
      border-color: rgb(0, 0, 130);
      color: rgb(0, 0, 130);
    }
    .btn-outline:hover {
      background-color: rgba(0, 0, 130, 0.05);
    }
  </style>
</head>
<body class="bg-gray-100 p-4 font-sans">

  <!-- Filters Section -->
  <div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold text-indigo-700">Filters</h2>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-4">
      <!-- Property Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Select Property</label>
        <select name="property" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
          <option value="">All Properties</option>
          <?php foreach ($properties as $property): ?>
            <option value="<?= htmlspecialchars($property) ?>" <?= $propertyFilter === $property ? 'selected' : '' ?>>
              <?= htmlspecialchars($property) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Date Range Filter -->
      <div class="col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>
          <div>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>
      </div>

      <!-- Submit and Download Buttons -->
      <div class="col-span-3 flex justify-between items-center mt-4">
        <button type="submit" name="submit" class="btn-primary text-white font-semibold px-12 py-2 rounded-md hover:bg-blue-900">
          Submit
        </button>

        <div class="flex gap-4">
          <button type="button" onclick="downloadPDF()" class="btn-outline border font-medium px-5 py-2 rounded-md">
            Download PDF
          </button>
          <a href="?property=<?= urlencode($propertyFilter) ?>&start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>&download=excel" class="btn-outline border font-medium px-5 py-2 rounded-md">
            Download Excel
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Transactions Section -->
  <div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold text-gray-800">Transactions</h2>
    </div>

    <?php if ($showTransactions && count($transactions) > 0): ?>
      <div class="overflow-x-auto">
        <table class="min-w-full text-sm text-left">
          <thead>
            <tr class="text-gray-600 bg-gray-100">
              <th class="px-4 py-3">Transaction Code</th>
              <th class="px-4 py-3">Phone Number</th>
              <th class="px-4 py-3">Name</th>
              <th class="px-4 py-3">Amount</th>
              <th class="px-4 py-3">Date</th>
            </tr>
          </thead>
          <tbody class="text-gray-700">
            <?php foreach ($transactions as $transaction): ?>
              <tr>
                <td class="px-4 py-3"><?= htmlspecialchars($transaction['transaction_code']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($transaction['phone_number']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($transaction['name']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars(number_format($transaction['amount'], 2)) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($transaction['date']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="text-center text-gray-500 py-10">
        There are no records to display.
      </div>
    <?php endif; ?>
  </div>

  <div id="printable-area" class="hidden">
    <h2 style="text-align: center; font-weight: bold; margin-bottom: 10px;">Transactions Report</h2>
    <table border="1" cellspacing="0" cellpadding="8" style="width: 100%; border-collapse: collapse;">
      <thead>
        <tr>
          <th>Transaction Code</th>
          <th>Phone Number</th>
          <th>Name</th>
          <th>Amount</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($transactions as $txn): ?>
        <tr>
          <td><?= htmlspecialchars($txn['transaction_code']) ?></td>
          <td><?= htmlspecialchars($txn['phone_number']) ?></td>
          <td><?= htmlspecialchars($txn['name']) ?></td>
          <td><?= number_format($txn['amount'], 2) ?></td>
          <td><?= htmlspecialchars($txn['date']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Scripts -->
  <script>
    function downloadPDF() {
      const printWindow = window.open('', '', 'width=800,height=600');
      const styles = `
        <style>
          body { font-family: sans-serif; padding: 20px; }
          table { width: 100%; border-collapse: collapse; }
          th, td { border: 1px solid #999; padding: 8px; text-align: left; }
          th { background: #f3f3f3; }
          h2 { text-align: center; }
        </style>
      `;
      const content = document.getElementById('printable-area').innerHTML;
      printWindow.document.write(`<html><head><title>Transactions Report</title>${styles}</head><body>${content}</body></html>`);
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    }
  </script>
</body>
</html>