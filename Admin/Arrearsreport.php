<?php include 'auth_admin.php'; ?>
<?php
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Filters
$propertyFilter = $_GET['property'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Fetch properties for dropdown
$propertiesQuery = "SELECT DISTINCT property FROM properties";
$propertiesResult = $conn->query($propertiesQuery);
$propertiesList = [];
if ($propertiesResult->num_rows > 0) {
    while ($row = $propertiesResult->fetch_assoc()) {
        $propertiesList[] = $row['property'];
    }
}

// Fetch arrears and amount collected data for each property
$propertyData = [];
$grandTotalArrears = 0;
$grandTotalCollected = 0;

if (isset($_GET['submit'])) {
    foreach ($propertiesList as $propertyName) {
        if (!empty($propertyFilter) && $propertyFilter !== $propertyName) continue;

        // Fetch all users for the property
        $usersQuery = "
            SELECT 
                u.id AS tenant_id,
                u.name,
                u.rent_amount,
                u.security_deposit,
                u.move_in_date,
                IFNULL(b.wifi, 0) AS wifi,
                IFNULL(b.water, 0) AS water,
                IFNULL(b.electricity, 0) AS electricity
            FROM users u
            LEFT JOIN billing b ON u.property = b.property
            WHERE u.property = '$propertyName'
        ";
        $usersResult = $conn->query($usersQuery);

        $totalArrears = 0;

        if ($usersResult->num_rows > 0) {
            while ($user = $usersResult->fetch_assoc()) {
                // Logic from UserProfile.php to calculate amount due
                $wifiAmount = floatval($user['wifi']);
                $waterAmount = floatval($user['water']);
                $electricityAmount = floatval($user['electricity']);
                $rentAmount = floatval($user['rent_amount']);
                $securityDeposit = floatval($user['security_deposit']);
                $moveInDate = new DateTime($user['move_in_date']);
                $now = new DateTime();

                // Monthly total (rent + utilities)
                $monthlyCharge = $rentAmount + $wifiAmount + $waterAmount + $electricityAmount;

                // Calculate months elapsed
                $months = ($now->format('Y') - $moveInDate->format('Y')) * 12 + ($now->format('n') - $moveInDate->format('n')) + 1;

                // Total required amount
                $required = $months * $monthlyCharge;

                // Add security deposit once for the first month
                if ($months >= 1) {
                    $required += $rentAmount;
                }

                // Get total paid by tenant
                $transactionStmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE tenant_id = ?");
                $transactionStmt->bind_param("i", $user['tenant_id']);
                $transactionStmt->execute();
                $transactionStmt->bind_result($totalPaid);
                $transactionStmt->fetch();
                $transactionStmt->close();
                $totalPaid = floatval($totalPaid ?? 0);

                // Calculate amount due
                $amountDue = max(0, $required - $totalPaid);

                // Add to total arrears for the property
                $totalArrears += $amountDue;
            }
        }

        // Fetch total amount collected for the property
        $amountCollectedQuery = "
            SELECT SUM(amount) AS total_collected
            FROM transactions
            WHERE property = '$propertyName'
        ";
        if (!empty($startDate) && !empty($endDate)) {
            $amountCollectedQuery .= " AND date BETWEEN '$startDate' AND '$endDate'";
        }
        $amountCollectedResult = $conn->query($amountCollectedQuery);
        $totalCollected = $amountCollectedResult->fetch_assoc()['total_collected'] ?? 0;

        $grandTotalArrears += $totalArrears;
        $grandTotalCollected += $totalCollected;

        $propertyData[] = [
            'property' => $propertyName,
            'total_arrears' => $totalArrears,
            'total_collected' => $totalCollected,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Arrears Report</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <style>
    .btn-primary {
      background-color: rgb(0, 0, 130);
      width: 150px;
    }
    .btn-primary:hover {
      background-color: rgb(0, 0, 100);
    }
    .toggle-button {
      font-size: 1.2rem;
      background: none;
      padding: 0;
      margin-left: auto;
      color: rgb(0, 0, 130);
    }
    th.sortable {
      cursor: pointer;
    }
  </style>
</head>
<body class="bg-gray-100 p-4 font-sans">
  <div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold text-indigo-700">Filters</h2>
      <button onclick="toggleSection('filters-content', this)" class="toggle-button" id="filters-toggle">−</button>
    </div>
    <div id="filters-content">
      <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Select Property</label>
          <select name="property" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Properties</option>
            <?php foreach ($propertiesList as $prop): ?>
              <option value="<?= htmlspecialchars($prop) ?>" <?= $propertyFilter === $prop ? 'selected' : '' ?>>
                <?= htmlspecialchars($prop) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>
        </div>

        <div class="col-span-3 flex justify-between items-center mt-4">
          <button type="submit" name="submit" class="btn-primary text-white font-semibold py-2 rounded-md">
            Submit
          </button>
          <div class="flex space-x-4">
            <button type="button" onclick="downloadPDF()" class="btn-primary text-white font-semibold py-2 rounded-md">Download PDF</button>
            <button type="button" onclick="downloadExcel()" class="btn-primary text-white font-semibold py-2 rounded-md">Download Excel</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold text-gray-800">Arrears Report</h2>
      <button onclick="toggleSection('report-content', this)" class="toggle-button">−</button>
    </div>
    <div id="report-content">
      <?php if (!isset($_GET['submit'])): ?>
        <p class="text-gray-600">No Records selected</p>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table id="report-table" class="min-w-full text-sm text-left border">
            <thead>
              <tr class="text-gray-600 bg-gray-100">
                <th class="px-4 py-3 sortable" onclick="sortTable(0)">Property</th>
                <th class="px-4 py-3 sortable" onclick="sortTable(1)">Amount Collected (KES)</th>
                <th class="px-4 py-3 sortable" onclick="sortTable(2)">Total Arrears (KES)</th>
              </tr>
            </thead>
            <tbody class="text-gray-700">
              <?php foreach ($propertyData as $data): ?>
                <tr>
                  <td class="px-4 py-3"><?= htmlspecialchars($data['property']) ?></td>
                  <td class="px-4 py-3"><?= number_format($data['total_collected'], 2) ?></td>
                  <td class="px-4 py-3">
                    <?= number_format($data['total_arrears'], 2) ?>
                    <?php if ($data['total_arrears'] < 0): ?>
                      (Overpayment)
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <tr class="font-semibold border-t border-gray-300">
                <td class="px-4 py-3">Grand Total</td>
                <td class="px-4 py-3"><?= number_format($grandTotalCollected, 2) ?></td>
                <td class="px-4 py-3"><?= number_format($grandTotalArrears, 2) ?></td>
              </tr>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function toggleSection(id, btn) {
      const section = document.getElementById(id);
      section.style.display = section.style.display === 'none' ? 'block' : 'none';
      btn.textContent = btn.textContent === '+' ? '−' : '+';
    }

    function downloadExcel() {
      const table = document.getElementById('report-table');
      const wb = XLSX.utils.table_to_book(table, { sheet: 'Report' });
      XLSX.writeFile(wb, 'arrears_report.xlsx');
    }

    async function downloadPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      const table = document.getElementById('report-table');
      let row = 10;
      for (let r of table.rows) {
        let col = 10;
        for (let cell of r.cells) {
          doc.text(cell.innerText, col, row);
          col += 60;
        }
        row += 10;
      }
      doc.save('arrears_report.pdf');
    }

    function sortTable(colIndex) {
      const table = document.getElementById("report-table");
      const rows = Array.from(table.rows).slice(1, -1);
      let asc = table.getAttribute("data-sort-dir") !== "asc";
      rows.sort((a, b) => {
        let A = a.cells[colIndex].innerText.replace(/[^0-9.-]+/g,"");
        let B = b.cells[colIndex].innerText.replace(/[^0-9.-]+/g,"");
        return asc ? A - B : B - A;
      });
      table.setAttribute("data-sort-dir", asc ? "asc" : "desc");
      for (let row of rows) table.tBodies[0].appendChild(row);
    }
  </script>
</body>
</html>