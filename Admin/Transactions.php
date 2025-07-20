<?php 
include 'auth_admin.php';
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch tenants for the dropdown
$tenantsQuery = "SELECT id, name, property, unit FROM users";
$tenantsResult = $conn->query($tenantsQuery);
$tenants = [];
if ($tenantsResult->num_rows > 0) {
    while ($row = $tenantsResult->fetch_assoc()) {
        $tenants[] = $row;
    }
}

// Handle marking tenants as vacated in transactions if the tenant no longer exists
$conn->query("
    UPDATE transactions 
    SET name = CONCAT(name, ' (Vacated Tenant)') 
    WHERE tenant_id NOT IN (SELECT id FROM users) 
    AND name NOT LIKE '%(Vacated Tenant)%'
");

// Get next cash transaction code
$latestCashCode = "CASH001";
$result = $conn->query("SELECT transaction_code FROM transactions WHERE transaction_code LIKE 'CASH%' ORDER BY id DESC LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $lastNumber = intval(substr($row['transaction_code'], 4));
    $nextNumber = str_pad($lastNumber + 1, 3, "0", STR_PAD_LEFT);
    $latestCashCode = "CASH" . $nextNumber;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $transaction_code = $_POST['transaction_code'];
    $phone_number = $_POST['phone_number'];
    $name = $_POST['name'];
    $tenant_id = $_POST['tenant_id'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];

    // Fetch property and unit
    $stmt = $conn->prepare("SELECT property, unit FROM users WHERE id = ?");
    $stmt->bind_param("i", $tenant_id);
    $stmt->execute();
    $stmt->bind_result($property, $unit);
    $stmt->fetch();
    $stmt->close();

    // Insert transaction
    $stmt = $conn->prepare("INSERT INTO transactions (transaction_code, payment_method, phone_number, name, tenant_id, property, unit, amount, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssissds", $transaction_code, $payment_method, $phone_number, $name, $tenant_id, $property, $unit, $amount, $date);

    if ($stmt->execute()) {
        echo "<script>alert('Transaction saved successfully!'); window.location.href=window.location.href;</script>";
    } else {
        echo "<script>alert('Failed to save transaction. Please try again.');</script>";
    }

    $stmt->close();
}

// Fetch transactions to display, with vacated tenants moved to the bottom
$transactionsQuery = "
    SELECT * FROM transactions
    ORDER BY 
        CASE 
            WHEN name LIKE '%(Vacated Tenant)%' THEN 1 
            ELSE 0 
        END, 
        id ASC
";
$transactionsResult = $conn->query($transactionsQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Transactions</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    #transactionModal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      z-index: 50;
      justify-content: center;
      align-items: center;
      overflow-y: auto;
    }

    #transactionModal .modal-content {
      background: white;
      padding: 20px;
      border-radius: 8px;
      width: 90%;
      max-width: 800px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .grid-cols-2 > div {
      margin-bottom: 1rem;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen p-6 font-sans">
  <h1 class="text-[18px] font-semibold text-gray-800 mb-6">Transactions</h1>

  <div class="flex justify-end mb-4">
    <button onclick="openModal()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Add Transaction</button>
  </div>

  <div class="overflow-x-auto">
    <table class="min-w-full text-sm text-left">
      <thead>
        <tr class="text-gray-600 bg-gray-100">
          <th class="px-4 py-3">Transaction Code</th>
          <th class="px-4 py-3">Payment Method</th>
          <th class="px-4 py-3">Phone Number</th>
          <th class="px-4 py-3">Name</th>
          <th class="px-4 py-3">Tenant</th>
          <th class="px-4 py-3">Property</th>
          <th class="px-4 py-3">Unit</th>
          <th class="px-4 py-3">Amount</th>
          <th class="px-4 py-3">Date</th>
        </tr>
      </thead>
      <tbody class="text-gray-700">
        <?php while ($transaction = $transactionsResult->fetch_assoc()): ?>
          <tr>
            <td class="px-4 py-3"><?= htmlspecialchars($transaction['transaction_code']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($transaction['payment_method']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($transaction['phone_number']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($transaction['name']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($transaction['tenant_id']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($transaction['property']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($transaction['unit']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($transaction['amount']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($transaction['date']) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Modal -->
  <div id="transactionModal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center">
    <div class="modal-content">
      <h2 class="text-lg font-semibold text-gray-800 mb-4">Add Transaction</h2>
      <form id="transactionForm" method="POST">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700">Transaction Code</label>
            <input type="text" id="transactionCode" name="transaction_code" value="<?= $latestCashCode ?>" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Payment Method</label>
            <select name="payment_method" id="paymentMethod" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" required>
              <option value="" disabled selected>Select Method</option>
              <option value="Cash">Cash</option>
              <option value="Mpesa">Mpesa</option>
              <option value="Bank">Bank</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
            <input type="text" name="phone_number" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Tenant</label>
            <select id="tenantDropdown" name="tenant_id" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" required>
              <option value="" disabled selected>Select a Tenant</option>
              <?php foreach ($tenants as $tenant): ?>
                <option value="<?= $tenant['id'] ?>" data-property="<?= htmlspecialchars($tenant['property']) ?>" data-unit="<?= htmlspecialchars($tenant['unit']) ?>">
                  <?= htmlspecialchars($tenant['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Property</label>
            <input type="text" id="propertyField" class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-500" readonly />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Unit</label>
            <input type="text" id="unitField" class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-500" readonly />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Amount</label>
            <input type="number" name="amount" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Date</label>
            <input type="date" name="date" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400" required />
          </div>
        </div>
        <div class="mt-6 flex justify-end space-x-4">
          <button type="button" onclick="closeModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400 transition">Cancel</button>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openModal() {
      document.getElementById('transactionModal').style.display = 'flex';
    }

    function closeModal() {
      document.getElementById('transactionModal').style.display = 'none';
    }

    // Auto-fill Property and Unit fields based on selected tenant
    document.getElementById('tenantDropdown').addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      document.getElementById('propertyField').value = selectedOption.getAttribute('data-property') || '';
      document.getElementById('unitField').value = selectedOption.getAttribute('data-unit') || '';
    });
  </script>
</body>
</html>
