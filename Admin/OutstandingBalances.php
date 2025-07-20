<?php include 'auth_admin.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch distinct properties for dropdown (for JS filter)
$propertyList = [];
$propertyRes = $conn->query("SELECT DISTINCT property FROM users WHERE property IS NOT NULL AND property != ''");
while ($row = $propertyRes->fetch_assoc()) {
    $propertyList[] = $row['property'];
}

// Fetch all due tenants at once (no PHP-side filter)
$dueTenantsQuery = "
    SELECT 
        u.id AS tenant_id,
        u.name,
        u.property,
        u.unit,
        u.rent_amount,
        u.security_deposit,
        u.move_in_date,
        IFNULL(b.wifi, 0) AS wifi,
        IFNULL(b.water, 0) AS water,
        IFNULL(b.electricity, 0) AS electricity
    FROM users u
    LEFT JOIN billing b ON u.property = b.property
    WHERE u.id != 1 AND u.id NOT IN (SELECT id FROM caretaker)
";
$dueTenantsResult = $conn->query($dueTenantsQuery);
$dueTenants = [];

if ($dueTenantsResult->num_rows > 0) {
    while ($tenant = $dueTenantsResult->fetch_assoc()) {
        // Logic from UserProfile.php to calculate amount due
        $wifiAmount = floatval($tenant['wifi']);
        $waterAmount = floatval($tenant['water']);
        $electricityAmount = floatval($tenant['electricity']);
        $rentAmount = floatval($tenant['rent_amount']);
        $securityDeposit = floatval($tenant['security_deposit']);
        $moveInDate = new DateTime($tenant['move_in_date']);
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
        $transactionStmt->bind_param("i", $tenant['tenant_id']);
        $transactionStmt->execute();
        $transactionStmt->bind_result($totalPaid);
        $transactionStmt->fetch();
        $transactionStmt->close();
        $totalPaid = floatval($totalPaid ?? 0);

        // Calculate amount due
        $amountDue = max(0, $required - $totalPaid);

        // Add tenant to the list if they have a positive balance due
        if ($amountDue > 0) {
            $tenant['balance_due'] = $amountDue;
            $dueTenants[] = $tenant;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <title>Due Tenants</title>
    <style>
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .download-btn {
            background-color: rgb(0,0,130);
        }
        .property-dropdown {
            position: absolute;
            z-index: 20;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            min-width: 180px;
            margin-top: 0.5rem;
            display: none;
        }
        .property-dropdown.show {
            display: block;
        }
        .property-dropdown-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: background 0.15s;
        }
        .property-dropdown-item:hover, .property-dropdown-item.active {
            background: #f1f5f9;
        }
        .relative-table-header {
            position: relative;
        }
    </style>
</head>
<body class="bg-gray-100 p-6">
    <div class="bg-white p-6 shadow-md rounded-xl max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-xl font-bold text-gray-800">
                <i class="fas fa-exclamation-circle text-blue-500 mr-2"></i>
                Tenants with Outstanding Balances
            </h1>
            <button onclick="downloadExcel()" class="download-btn text-white px-4 py-2 rounded-md hover:opacity-90 text-sm">
                <i class="fas fa-file-excel mr-2"></i>Download Excel
            </button>
        </div>

        <table id="tenantsTable" class="min-w-full text-sm border border-gray-200 rounded-md">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="py-2 px-4 border-b">Name</th>
                    <th class="py-2 px-4 border-b relative-table-header">
                        Property(Unit)
                        <button id="propertyDropdownBtn" onclick="togglePropertyDropdown(event)" style="background:transparent;border:none;outline:none;padding:0;margin-left:4px;vertical-align:middle;cursor:pointer;">
                            <svg class="inline w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="propertyDropdown" class="property-dropdown">
                            <div class="property-dropdown-item" data-property="">All Properties</div>
                            <?php foreach ($propertyList as $property): ?>
                                <div class="property-dropdown-item" data-property="<?= htmlspecialchars($property, ENT_QUOTES) ?>">
                                    <?= htmlspecialchars($property) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </th>
                    <th class="py-2 px-4 border-b">Balance Due (KES)</th>
                </tr>
            </thead>
            <tbody id="tenantsTableBody">
                <!-- Rows will be rendered by JS for instant filtering -->
            </tbody>
        </table>
        <p id="noResultsMsg" class="text-gray-600 mt-4" style="display:none;">All tenants are currently settled. No outstanding balances found.</p>
    </div>

    <script>
        // Prepare all tenants data for instant JS filtering
        const allTenants = <?= json_encode($dueTenants) ?>;
        const tenantsTableBody = document.getElementById('tenantsTableBody');
        const noResultsMsg = document.getElementById('noResultsMsg');
        let selectedProperty = "";

        function renderTable(property = "") {
            tenantsTableBody.innerHTML = "";
            let filtered = property
                ? allTenants.filter(t => t.property === property)
                : allTenants;
            if (filtered.length === 0) {
                noResultsMsg.style.display = "";
                return;
            }
            noResultsMsg.style.display = "none";
            filtered.forEach(tenant => {
                const tr = document.createElement('tr');
                tr.className = "hover:bg-gray-50";
                tr.innerHTML = `
                    <td class="py-2 px-4 border-b">
                        <i class="fas fa-id-card text-gray-400 mr-1"></i>
                        ${escapeHtml(tenant.name)}
                    </td>
                    <td class="py-2 px-4 border-b">
                        <i class="fas fa-building text-gray-400 mr-1"></i>
                        ${escapeHtml(tenant.property)} (${escapeHtml(tenant.unit)})
                    </td>
                    <td class="py-2 px-4 border-b text-black-600 font-medium">
                        <i class="fas fa-coins text-yellow-500 mr-1"></i>
                        ${Number(tenant.balance_due).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}
                    </td>
                `;
                tenantsTableBody.appendChild(tr);
            });
        }

        // Escape HTML for safe rendering
        function escapeHtml(text) {
            return text == null ? "" : String(text)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }

        // Dropdown logic
        function togglePropertyDropdown(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('propertyDropdown');
            dropdown.classList.toggle('show');
        }
        document.querySelectorAll('.property-dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                selectedProperty = this.getAttribute('data-property');
                renderTable(selectedProperty);
                // Highlight selected
                document.querySelectorAll('.property-dropdown-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('propertyDropdown').classList.remove('show');
            });
        });
        // Hide dropdown on outside click
        document.addEventListener('click', function(e) {
            document.getElementById('propertyDropdown').classList.remove('show');
        });

        // Initial render
        renderTable();

        function downloadExcel() {
            const rows = Array.from(tenantsTableBody.children);
            if (!rows.length) return;
            let csv = '"Name","Property(Unit)","Balance Due (KES)"\n';
            rows.forEach(row => {
                const cols = Array.from(row.children).map(cell => `"${cell.textContent.trim()}"`);
                csv += cols.join(",") + "\n";
            });
            const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = "due_tenants.csv";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>