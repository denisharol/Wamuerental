<?php include 'auth_admin.php'; ?>
<?php
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$submitted = isset($_GET['submit']) || isset($_GET['download']);
$propertyFilter = $_GET['property'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Fetch properties for dropdown
$propertiesQuery = "SELECT DISTINCT property FROM maintenance";
$propertiesResult = $conn->query($propertiesQuery);
$propertiesList = [];
if ($propertiesResult->num_rows > 0) {
    while ($row = $propertiesResult->fetch_assoc()) {
        $propertiesList[] = $row['property'];
    }
}

// Build WHERE clause
$conditions = [];
if (!empty($propertyFilter)) {
    $conditions[] = "property = '" . $conn->real_escape_string($propertyFilter) . "'";
}
if (!empty($startDate) && !empty($endDate)) {
    $conditions[] = "date BETWEEN '" . $conn->real_escape_string($startDate) . "' AND '" . $conn->real_escape_string($endDate) . "'";
}
$whereClause = !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';

// --- Excel Download Handler ---
if (isset($_GET['download']) && $_GET['download'] === 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=expenses-report.csv');
    $output = fopen('php://output', 'w');
    // CSV Header
    fputcsv($output, ['Property', 'Total Expense Amount (KES)', 'Date', 'Status', 'Narration']);

    $maintenanceQuery = "
        SELECT 
            property,
            shortsummary AS narration,
            SUM(expense) AS total_expense,
            MAX(date) AS latest_date,
            CASE 
                WHEN SUM(expense) > 0 THEN 'Paid'
                ELSE 'Unpaid'
            END AS status
        FROM maintenance
        $whereClause
        GROUP BY property, shortsummary
        ORDER BY property, latest_date DESC
    ";
    $maintenanceResult = $conn->query($maintenanceQuery);
    $totalAmount = 0;
    if ($maintenanceResult && $maintenanceResult->num_rows > 0) {
        while ($row = $maintenanceResult->fetch_assoc()) {
            fputcsv($output, [
                $row['property'],
                number_format($row['total_expense'], 2),
                $row['latest_date'],
                $row['status'],
                $row['narration']
            ]);
            $totalAmount += $row['total_expense'];
        }
        // Total row
        fputcsv($output, ['TOTAL', number_format($totalAmount, 2), '', '', '']);
    }
    fclose($output);
    exit;
}

// Pagination Query
$countQuery = "
    SELECT COUNT(*) AS total 
    FROM (SELECT 1 FROM maintenance $whereClause GROUP BY property, shortsummary) AS temp
";
$countResult = $conn->query($countQuery);
$totalRows = ($countResult && $row = $countResult->fetch_assoc()) ? (int)$row['total'] : 0;
$totalPages = ceil($totalRows / $limit);

// Fetch Maintenance Data with LIMIT
$maintenanceData = [];
$totalAmount = 0;

if ($submitted) {
    $maintenanceQuery = "
        SELECT 
            property,
            shortsummary AS narration,
            SUM(expense) AS total_expense,
            MAX(date) AS latest_date,
            CASE 
                WHEN SUM(expense) > 0 THEN 'Paid'
                ELSE 'Unpaid'
            END AS status
        FROM maintenance
        $whereClause
        GROUP BY property, shortsummary
        ORDER BY property, latest_date DESC
        LIMIT $limit OFFSET $offset
    ";
    $maintenanceResult = $conn->query($maintenanceQuery);
    if ($maintenanceResult && $maintenanceResult->num_rows > 0) {
        while ($row = $maintenanceResult->fetch_assoc()) {
            $maintenanceData[] = $row;
            $totalAmount += $row['total_expense'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Expenses Report</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <style>
    .btn-primary {
      background-color: rgb(0, 0, 130);
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
  </style>
</head>
<body class="bg-gray-100 p-4 font-sans">

  <!-- Filters Section -->
  <div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold text-indigo-700">Filters</h2>
      <button onclick="toggleSection('filters-content', this)" class="toggle-button" id="filters-toggle">−</button>
    </div>

    <div id="filters-content">
      <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Property Filter -->
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

        <!-- Date Range Filter -->
        <div class="col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full border border-gray-300 rounded-md px-3 py-2">
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full border border-gray-300 rounded-md px-3 py-2">
          </div>
        </div>

        <!-- Actions -->
        <div class="col-span-3 flex justify-between items-center mt-4">
          <button type="submit" name="submit" class="btn-primary text-white font-semibold px-12 py-2 rounded-md">
            Submit
          </button>

          <div class="flex gap-4">
            <button type="button" onclick="downloadPDF()" class="text-indigo-700 font-medium px-5 py-2 rounded-md">
              Download PDF
            </button>
            <a href="?<?= http_build_query(['property' => $propertyFilter, 'start_date' => $startDate, 'end_date' => $endDate, 'download' => 'excel']) ?>" class="text-indigo-700 font-medium px-5 py-2 rounded-md">
              Download Excel
            </a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Expenses Report Section -->
  <div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold text-gray-800">Expenses Report</h2>
      <button onclick="toggleSection('property-content', this)" class="toggle-button" id="property-toggle">−</button>
    </div>

    <div class="overflow-x-auto" id="property-content">
      <?php if (!$submitted): ?>
        <p class="text-gray-500 text-center">No Expense Records Selected</p>
      <?php elseif (empty($maintenanceData)): ?>
        <p class="text-gray-500 text-center">No expenses found for the selected filters.</p>
      <?php else: ?>
        <table class="min-w-full text-sm text-left">
          <thead>
            <tr class="text-gray-600 bg-gray-100">
              <th class="px-4 py-3">Property</th>
              <th class="px-4 py-3">Total Expense Amount (KES)</th>
              <th class="px-4 py-3">Date</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3">Narration</th>
            </tr>
          </thead>
          <tbody class="text-gray-700">
            <?php foreach ($maintenanceData as $data): ?>
              <tr>
                <td class="px-4 py-3"><?= htmlspecialchars($data['property']) ?></td>
                <td class="px-4 py-3"><?= number_format($data['total_expense'], 2) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($data['latest_date']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($data['status']) ?></td>
                <td class="px-4 py-3"><?= htmlspecialchars($data['narration']) ?></td>
              </tr>
            <?php endforeach; ?>
            <!-- Total row -->
            <tr class="font-semibold text-indigo-800 bg-gray-50">
              <td class="px-4 py-3">TOTAL</td>
              <td class="px-4 py-3"><?= number_format($totalAmount, 2) ?></td>
              <td colspan="3"></td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination controls -->
        <div class="mt-6 flex justify-center gap-4">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
               class="px-3 py-1 border rounded <?= $i == $page ? 'bg-indigo-600 text-white' : 'text-indigo-700' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function toggleSection(sectionId, button) {
      const section = document.getElementById(sectionId);
      section.classList.toggle('hidden');
      button.textContent = section.classList.contains('hidden') ? '+' : '−';
    }

    function downloadPDF() {
      const element = document.getElementById('property-content');
      const opt = {
        margin: 0.5,
        filename: 'expenses-report.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
      };
      html2pdf().set(opt).from(element).save();
    }
  </script>
</body>
</html>
