<?php include 'auth_admin.php'; ?>
<?php
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$propertiesQuery = "SELECT DISTINCT property FROM users";
$propertiesResult = $conn->query($propertiesQuery);
$properties = [];
if ($propertiesResult->num_rows > 0) {
    while ($row = $propertiesResult->fetch_assoc()) {
        $properties[] = $row['property'];
    }
}

$propertyFilter = $_GET['property'] ?? '';
$tenantFilter = $_GET['tenant'] ?? '';
$tenants = [];
$tenantDetails = null;

if (!empty($propertyFilter)) {
    $tenantsQuery = "SELECT id, name, unit FROM users WHERE property = '$propertyFilter'";
    $tenantsResult = $conn->query($tenantsQuery);
    if ($tenantsResult->num_rows > 0) {
        while ($row = $tenantsResult->fetch_assoc()) {
            $tenants[] = $row;
        }
    }
}

if (!empty($tenantFilter)) {
    // Logic copied from UserProfile.php
    $reportStmt = $conn->prepare("
        SELECT remaining_deposit 
        FROM assesment_report 
        WHERE tenant_id = ? AND status = 'Accepted'
        LIMIT 1
    ");
    $reportStmt->bind_param("i", $tenantFilter);
    $reportStmt->execute();
    $reportStmt->bind_result($remainingDeposit);

    if ($reportStmt->fetch()) {
        $reportStmt->close();

        $updateStmt = $conn->prepare("
            UPDATE users 
            SET security_deposit = ? 
            WHERE id = ?
        ");
        $updateStmt->bind_param("di", $remainingDeposit, $tenantFilter);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        $reportStmt->close();
    }

    // Fetch tenant details
    $stmt = $conn->prepare("SELECT name, property, unit, rent_amount, security_deposit, move_in_date FROM users WHERE id = ?");
    $stmt->bind_param("i", $tenantFilter);
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
    $transactionStmt->bind_param("i", $tenantFilter);
    $transactionStmt->execute();
    $transactionStmt->bind_result($totalPaid);
    $transactionStmt->fetch();
    $transactionStmt->close();
    $totalPaid = floatval($totalPaid ?? 0);

    // Calculate overpayment and amount due
    $overpayment = $totalPaid - $required;
    $amountDue = max(0, $required - $totalPaid);

    // Update tenant details with calculated values
    $tenantDetails = [
        'name' => $name,
        'property' => $property,
        'unit' => $unit,
        'monthly_charge' => $monthlyCharge,
        'total_required' => $required,
        'total_paid' => $totalPaid,
        'overpayment' => $overpayment,
        'amount_due' => $amountDue,
        'security_deposit' => $securityDeposit,
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tenant Statement</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .btn {
      background-color: rgb(0, 0, 130);
      color: white;
      padding: 0.5rem 1.25rem;
      border-radius: 0.375rem;
      font-weight: 600;
    }
    .btn:hover {
      background-color: rgb(0, 0, 100);
    }
    .btn-outline {
      border: 2px solid rgb(0, 0, 130);
      color: rgb(0, 0, 130);
      padding: 0.5rem 1.25rem;
      border-radius: 0.375rem;
      font-weight: 600;
    }
    .btn-outline:hover {
      background-color: rgba(0, 0, 130, 0.05);
    }
  </style>
</head>
<body class="bg-gray-100 p-4 font-sans">

  <!-- Filters Card -->
  <div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4 cursor-pointer" onclick="toggleSection('filtersSection', 'filtersToggle')">
      <h2 class="text-lg font-semibold text-indigo-700">Filters</h2>
      <span id="filtersToggle" class="text-xl text-indigo-600">−</span>
    </div>
    <div id="filtersSection">
      <form method="GET" id="filtersForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Select Property</label>
          <select name="property" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Properties</option>
            <?php foreach ($properties as $property): ?>
              <?php if ($property !== null): ?>
                <option value="<?= htmlspecialchars((string)$property) ?>" <?= $propertyFilter === $property ? 'selected' : '' ?>>
                  <?= htmlspecialchars((string)$property) ?>
                </option>
              <?php endif; ?>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Select Tenant</label>
          <select name="tenant" onchange="this.form.submit()" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Tenants</option>
            <?php foreach ($tenants as $tenant): ?>
              <option value="<?= htmlspecialchars($tenant['id']) ?>" <?= $tenantFilter == $tenant['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($tenant['name']) ?> (Unit: <?= htmlspecialchars($tenant['unit']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>

      <div class="flex justify-between items-center">
        <button type="submit" form="filtersForm" class="btn w-48">Submit</button>
        <div class="flex gap-3">
          <button onclick="printPDF()" type="button" class="btn-outline">Download PDF</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Tenant Statement Card -->
  <div class="bg-white rounded-lg shadow-md mb-6">
    <div class="flex justify-between items-center mb-4 cursor-pointer p-6" onclick="toggleSection('statementSection', 'statementToggle')">
      <h2 class="text-lg font-semibold text-gray-800">Tenant Statement</h2>
      <span id="statementToggle" class="text-xl text-gray-600">−</span>
    </div>
    <div id="statementSection" class="px-6 pb-6">
      <?php if (!empty($tenantDetails)): ?>
        <div id="printable-content" class="text-gray-700">
          <p><strong>Name:</strong> <?= htmlspecialchars($tenantDetails['name']) ?></p>
          <p><strong>Property:</strong> <?= htmlspecialchars($tenantDetails['property']) ?></p>
          <p><strong>Unit:</strong> <?= htmlspecialchars($tenantDetails['unit']) ?></p>
          <p><strong>Monthly Charge:</strong> <?= number_format($tenantDetails['monthly_charge'], 2) ?> KES</p>
          <p><strong>Total Required:</strong> <?= number_format($tenantDetails['total_required'], 2) ?> KES</p>
          <p><strong>Total Paid:</strong> <?= number_format($tenantDetails['total_paid'], 2) ?> KES</p>
          <p><strong>Overpayment:</strong> <?= number_format($tenantDetails['overpayment'], 2) ?> KES</p>
          <p><strong>Amount Due:</strong> <?= number_format($tenantDetails['amount_due'], 2) ?> KES</p>
          <p><strong>Security Deposit:</strong> <?= number_format($tenantDetails['security_deposit'], 2) ?> KES</p>
        </div>
      <?php else: ?>
        <div class="text-center text-gray-500 py-10">
          There are no records to display.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function printPDF() {
      const content = document.getElementById('printable-content').innerHTML;
      const printWindow = window.open('', '', 'width=800,height=600');
      const styles = `
        <style>
          body { font-family: sans-serif; padding: 20px; }
          h2 { text-align: center; margin-bottom: 20px; }
          p { margin-bottom: 10px; }
        </style>
      `;
      printWindow.document.write(`<html><head><title>Tenant Statement</title>${styles}</head><body><h2>Tenant Statement</h2>${content}</body></html>`);
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    }

    function toggleSection(id, toggleId) {
      const section = document.getElementById(id);
      const toggleIcon = document.getElementById(toggleId);
      section.classList.toggle('hidden');
      toggleIcon.textContent = section.classList.contains('hidden') ? '+' : '−';
    }
  </script>

</body>
</html>