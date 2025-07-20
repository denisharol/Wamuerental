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

// Fetch filtered properties
$propertyConditions = [];
if (!empty($propertyFilter)) {
    $propertyConditions[] = "property = '$propertyFilter'";
}
$propertyWhereClause = !empty($propertyConditions) ? 'WHERE ' . implode(' AND ', $propertyConditions) : '';
$filteredPropertiesQuery = "SELECT * FROM properties $propertyWhereClause";
$filteredPropertiesResult = $conn->query($filteredPropertiesQuery);
$filteredProperties = [];
if ($filteredPropertiesResult->num_rows > 0) {
    while ($row = $filteredPropertiesResult->fetch_assoc()) {
        $filteredProperties[] = $row;
    }
}

// Fetch property data
$propertyData = [];
foreach ($filteredProperties as $property) {
    $propertyName = $property['property'];

    // Amount collected from transactions
    $amountCollectedQuery = "SELECT SUM(amount) AS total_collected FROM transactions WHERE property = '$propertyName'";
    if (!empty($startDate) && !empty($endDate)) {
        $amountCollectedQuery .= " AND date BETWEEN '$startDate' AND '$endDate'";
    }
    $amountCollectedResult = $conn->query($amountCollectedQuery);
    $amountCollected = $amountCollectedResult->fetch_assoc()['total_collected'] ?? 0;

    // Expenses
    $expensesQuery = "SELECT SUM(amount) AS total_expenses FROM expenses WHERE property = '$propertyName'";
    if (!empty($startDate) && !empty($endDate)) {
        $expensesQuery .= " AND date BETWEEN '$startDate' AND '$endDate'";
    }
    $expensesResult = $conn->query($expensesQuery);
    $totalExpenses = $expensesResult->fetch_assoc()['total_expenses'] ?? 0;

    // Units
    $unitsQuery = "SELECT COUNT(*) AS total_units FROM units WHERE property = '$propertyName'";
    $unitsResult = $conn->query($unitsQuery);
    $totalUnits = $unitsResult->fetch_assoc()['total_units'] ?? 0;

    $occupiedQuery = "SELECT COUNT(*) AS occupied_units FROM units WHERE property = '$propertyName' AND status = 'Occupied'";
    $occupiedResult = $conn->query($occupiedQuery);
    $occupiedUnits = $occupiedResult->fetch_assoc()['occupied_units'] ?? 0;

    $vacantUnits = $totalUnits - $occupiedUnits;

    $percentageOccupancy = $totalUnits > 0 ? ($occupiedUnits / $totalUnits) * 100 : 0;
    $percentageVacancy = $totalUnits > 0 ? ($vacantUnits / $totalUnits) * 100 : 0;

    $propertyData[] = [
        'property' => $propertyName,
        'amount_collected' => $amountCollected,
        'expenses' => $totalExpenses,
        'total_units' => $totalUnits,
        'percentage_occupancy' => $percentageOccupancy,
        'percentage_vacancy' => $percentageVacancy,
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Property Statements</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
            <button type="button" id="downloadExcelBtn" class="text-indigo-700 font-medium px-5 py-2 rounded-md">
              Download Excel
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Property Statements Section -->
  <div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-lg font-semibold text-gray-800">Property Statements</h2>
      <button onclick="toggleSection('property-content', this)" class="toggle-button" id="property-toggle">−</button>
    </div>

    <div class="overflow-x-auto" id="property-content">
      <table class="min-w-full text-sm text-left" id="yourTableId">
        <thead>
          <tr class="text-gray-600 bg-gray-100">
            <th class="px-4 py-3">Property</th>
            <th class="px-4 py-3">Amount Collected (KES)</th>
            <th class="px-4 py-3">Expenses (KES)</th>
            <th class="px-4 py-3">Number of Units</th>
            <th class="px-4 py-3">Percentage Occupancy (%)</th>
            <th class="px-4 py-3">Percentage Vacancy (%)</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php foreach ($propertyData as $data): ?>
            <tr>
              <td class="px-4 py-3"><?= htmlspecialchars($data['property']) ?></td>
              <td class="px-4 py-3"><?= number_format($data['amount_collected'], 2) ?></td>
              <td class="px-4 py-3"><?= number_format($data['expenses'], 2) ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($data['total_units']) ?></td>
              <td class="px-4 py-3"><?= number_format($data['percentage_occupancy'], 2) ?>%</td>
              <td class="px-4 py-3"><?= number_format($data['percentage_vacancy'], 2) ?>%</td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
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
        margin:       0.5,
        filename:     'property-statements.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 },
        jsPDF:        { unit: 'in', format: 'letter', orientation: 'landscape' }
      };
      html2pdf().set(opt).from(element).save();
    }

    document.getElementById('downloadExcelBtn').addEventListener('click', function () {
        const table = document.getElementById('yourTableId');
        const rows = Array.from(table.querySelectorAll('tr'));
        const data = rows.map(row =>
            Array.from(row.querySelectorAll('th,td')).map(cell => cell.innerText)
        );
        const ws = XLSX.utils.aoa_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, "FilteredData");
        XLSX.writeFile(wb, "propertystatement.xlsx");
    });
  </script>
</body>
</html>
