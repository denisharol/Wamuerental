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
$assessmentData = [];

// Fetch tenants for the selected property
if (!empty($propertyFilter)) {
    $tenantsQuery = "SELECT id, name, unit FROM users WHERE property = '$propertyFilter'";
    $tenantsResult = $conn->query($tenantsQuery);
    if ($tenantsResult->num_rows > 0) {
        while ($row = $tenantsResult->fetch_assoc()) {
            $tenants[] = $row;
        }
    }
}

// Fetch assessment data based on filters
$assessmentQuery = "
    SELECT 
        u.name AS tenant_name,
        u.property,
        u.unit,
        ar.security_deposit,
        ar.remaining_deposit,
        ar.status,
        ar.damages
    FROM assesment_report ar
    JOIN users u ON ar.tenant_id = u.id
    WHERE 1=1
";

if (!empty($propertyFilter)) {
    $assessmentQuery .= " AND u.property = '$propertyFilter'";
}

if (!empty($tenantFilter)) {
    $assessmentQuery .= " AND u.id = '$tenantFilter'";
}

$assessmentResult = $conn->query($assessmentQuery);
if ($assessmentResult->num_rows > 0) {
    while ($row = $assessmentResult->fetch_assoc()) {
        $row['refundable_deposit'] = ($row['status'] === 'Accepted') ? $row['remaining_deposit'] : $row['security_deposit'];
        $row['reason_for_deduction'] = ($row['status'] === 'Accepted') ? 'Damages' : 'None';
        $assessmentData[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Assessment Overview</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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
              <option value="<?= htmlspecialchars($property) ?>" <?= $propertyFilter === $property ? 'selected' : '' ?>>
                <?= htmlspecialchars($property) ?>
              </option>
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

      <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <button type="submit" form="filtersForm" class="btn w-48">Submit</button>
        <div class="flex gap-4 ml-auto">
          <button onclick="downloadPDF()" class="btn">Download PDF</button>
          <button onclick="downloadExcel()" class="btn">Download Excel</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Assessment Data Table -->
  <div class="bg-white rounded-lg shadow-md p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Assessment Overview</h2>
    <?php if (!empty($assessmentData)): ?>
      <div id="assessmentTableWrapper">
        <table id="assessmentTable" class="min-w-full text-sm border border-gray-200 rounded-md">
          <thead class="bg-gray-50 text-left">
            <tr>
              <th class="py-2 px-4 border-b">Tenant Name</th>
              <th class="py-2 px-4 border-b">Property(Unit)</th>
              <th class="py-2 px-4 border-b">Refundable Security Deposit (KES)</th>
              <th class="py-2 px-4 border-b">Reason for Deduction</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($assessmentData as $data): ?>
              <tr class="hover:bg-gray-50">
                <td class="py-2 px-4 border-b"><?= htmlspecialchars($data['tenant_name']) ?></td>
                <td class="py-2 px-4 border-b"><?= htmlspecialchars($data['property']) ?> (<?= htmlspecialchars($data['unit']) ?>)</td>
                <td class="py-2 px-4 border-b"><?= number_format($data['refundable_deposit'], 2) ?></td>
                <td class="py-2 px-4 border-b"><?= htmlspecialchars($data['reason_for_deduction']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-gray-600">No assessment data found for the selected filters.</p>
    <?php endif; ?>
  </div>

  <script>
    function toggleSection(id, toggleId) {
      const section = document.getElementById(id);
      const toggleIcon = document.getElementById(toggleId);
      section.classList.toggle('hidden');
      toggleIcon.textContent = section.classList.contains('hidden') ? '+' : '−';
    }

    function downloadPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      const table = document.getElementById('assessmentTable');
      let rowIndex = 10;

      doc.setFontSize(12);
      doc.text("Assessment Overview", 14, 10);

      Array.from(table.rows).forEach((row, i) => {
        let rowData = "";
        Array.from(row.cells).forEach(cell => {
          rowData += cell.textContent.trim() + " | ";
        });
        doc.text(rowData, 10, rowIndex + 10);
        rowIndex += 10;
      });

      doc.save("assessment_report.pdf");
    }

    function downloadExcel() {
      const table = document.getElementById("assessmentTable");
      const wb = XLSX.utils.table_to_book(table, { sheet: "Assessment Report" });
      XLSX.writeFile(wb, "assessment_report.xlsx");
    }
  </script>

</body>
</html>
