<?php
session_start();


if (!isset($_SESSION['admin_id'])) {
    header("Location: Admin/login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Calculate total due balances
$totalDueBalances = 0;
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

if ($dueTenantsResult && $dueTenantsResult->num_rows > 0) {
    while ($tenant = $dueTenantsResult->fetch_assoc()) {
        $wifiAmount = floatval($tenant['wifi']);
        $waterAmount = floatval($tenant['water']);
        $electricityAmount = floatval($tenant['electricity']);
        $rentAmount = floatval($tenant['rent_amount']);
        $securityDeposit = floatval($tenant['security_deposit']);
        $moveInDate = new DateTime($tenant['move_in_date']);
        $now = new DateTime();

        $monthlyCharge = $rentAmount + $wifiAmount + $waterAmount + $electricityAmount;
        $months = ($now->format('Y') - $moveInDate->format('Y')) * 12 + ($now->format('n') - $moveInDate->format('n')) + 1;
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

        $amountDue = max(0, $required - $totalPaid);

        $totalDueBalances += $amountDue;
    }
}

$tenantsQuery = "SELECT COUNT(*) AS total_tenants FROM users WHERE id != 1";
$tenantsResult = $conn->query($tenantsQuery);
$totalTenants = $tenantsResult->fetch_assoc()['total_tenants'] ?? 0;

$expensesQuery = "SELECT SUM(amount) AS total_amount FROM expenses";
$expensesResult = $conn->query($expensesQuery);
$totalExpenses = $expensesResult->fetch_assoc()['total_amount'] ?? 0;

$messagesQuery = "SELECT COUNT(*) AS unread_messages FROM messages WHERE is_read = 0 AND receiver_id = 1";
$messagesResult = $conn->query($messagesQuery);
$totalUnreadMessages = $messagesResult->fetch_assoc()['unread_messages'] ?? 0;

$vacatingQuery = "SELECT COUNT(*) AS vacating_count FROM approved_vacating_notices";
$vacatingResult = $conn->query($vacatingQuery);
$totalVacating = $vacatingResult->fetch_assoc()['vacating_count'] ?? 0;

$revenueQuery = "SELECT SUM(amount) AS total_revenue FROM transactions";
$revenueResult = $conn->query($revenueQuery);
$totalRevenue = $revenueResult->fetch_assoc()['total_revenue'] ?? 0;

$occupiedQuery = "SELECT COUNT(*) AS occupied_units FROM units WHERE status = 'Occupied'";
$vacantQuery = "SELECT COUNT(*) AS vacant_units FROM units WHERE status = 'Vacant'";
$occupiedResult = $conn->query($occupiedQuery);
$vacantResult = $conn->query($vacantQuery);
$occupiedUnits = $occupiedResult->fetch_assoc()['occupied_units'] ?? 0;
$vacantUnits = $vacantResult->fetch_assoc()['vacant_units'] ?? 0;

// Build full month range and align revenue data
$rangeQuery = "SELECT MIN(DATE(date)) AS start_date, MAX(DATE(date)) AS end_date FROM transactions";
$rangeResult = $conn->query($rangeQuery);
$range = $rangeResult->fetch_assoc();

$start = new DateTime(date('Y-m-01', strtotime($range['start_date'])));
$end = new DateTime(date('Y-m-01', strtotime($range['end_date'])));
$end->modify('+1 month');

$allMonths = [];
while ($start < $end) {
    $allMonths[] = $start->format('Y-m');
    $start->modify('+1 month');
}

$monthlyRevenueQuery = "
    SELECT DATE_FORMAT(date, '%Y-%m') AS month, SUM(amount) AS total_revenue
    FROM transactions
    GROUP BY DATE_FORMAT(date, '%Y-%m')
";
$monthlyRevenueResult = $conn->query($monthlyRevenueQuery);

$revenueByMonth = [];
while ($row = $monthlyRevenueResult->fetch_assoc()) {
    $revenueByMonth[$row['month']] = $row['total_revenue'];
}

$monthlyLabels = [];
$monthlyRevenueData = [];
foreach ($allMonths as $month) {
    $monthlyLabels[] = $month;
    $monthlyRevenueData[] = isset($revenueByMonth[$month]) ? $revenueByMonth[$month] : 0;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Admin Dashboard</title>
    <style>
        .hover-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .hover-lift:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col items-center min-h-screen p-6">
    <div class="bg-gray-50 p-6 shadow-md rounded-2xl w-full max-w-screen-2xl">
        <h1 class="text-xl font-bold text-gray-800 mb-8">Admin Dashboard</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <?php
            $cards = [
                ["fas fa-hand-holding-dollar", "Total Due Balances", number_format($totalDueBalances, 2) . " KES"],
                ["fas fa-users", "Tenants", $totalTenants],
                ["fas fa-receipt", "Operational Expenses", number_format($totalExpenses, 2) . " KES"],
                ["fas fa-envelope-open-text", "Unread Messages", $totalUnreadMessages],
                ["fas fa-door-open", "Approved Vacating Requests", $totalVacating],
                ["fas fa-money-bill-wave", "Revenue Collected", number_format($totalRevenue, 2) . " KES"]
            ];
            foreach ($cards as [$icon, $label, $value]) {
                echo "<div class='hover-lift bg-white p-6 shadow-lg rounded-xl border border-gray-200 flex items-center space-x-4'>
                        <i class='{$icon} text-blue-700 text-3xl'></i>
                        <div>
                            <h3 class='text-gray-600 font-semibold text-sm'>{$label}</h3>
                            <p class='text-black-800 font-medium text-lg mt-2'>{$value}</p>
                        </div>
                    </div>";
            }
            ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 shadow-lg rounded-2xl border border-gray-200 flex flex-col items-center">
                <h3 class="text-gray-700 font-semibold text-md mb-4">Unit Occupancy Overview</h3>
                <div class="relative w-48 h-48">
                    <canvas id="donutChart"></canvas>
                    <div class="absolute inset-0 flex flex-col items-center justify-center text-sm text-gray-700 font-semibold" id="donutCenterText"></div>
                </div>
                <div class="mt-4 text-sm text-gray-600 flex justify-around w-full">
                    <div><span class="font-bold text-indigo-600">Occupied:</span> <?= $occupiedUnits ?></div>
                    <div><span class="font-bold text-gray-400">Vacant:</span> <?= $vacantUnits ?></div>
                </div>
            </div>

            <div class="bg-white p-6 shadow-lg rounded-2xl border border-gray-200 flex flex-col items-center">
                <h3 class="text-gray-700 font-semibold text-md mb-4">Monthly Revenue Trend</h3>
                <div class="w-full overflow-x-auto">
                    <canvas id="barChart" class="!h-56"></canvas> <!-- FIX: Removed height attribute, added Tailwind height -->
                </div>
            </div>
        </div>
    </div>

<script>
    const donutCtx = document.getElementById('donutChart').getContext('2d');
    const totalUnits = <?= $occupiedUnits + $vacantUnits ?>;
    const occupiedUnits = <?= $occupiedUnits ?>;
    const vacantUnits = <?= $vacantUnits ?>;
    const occupiedPercentage = ((occupiedUnits / totalUnits) * 100).toFixed(1);
    const vacantPercentage = ((vacantUnits / totalUnits) * 100).toFixed(1);
    document.getElementById("donutCenterText").innerText = `${occupiedPercentage}% / ${vacantPercentage}%`;

    new Chart(donutCtx, {
        type: 'doughnut',
        data: {
            labels: ['Occupied Units', 'Vacant Units'],
            datasets: [{
                data: [occupiedUnits, vacantUnits],
                backgroundColor: ['#4F46E5', '#E5E7EB'],
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 10,
                cutout: '70%',
            }]
        },
        options: {
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let value = context.raw;
                            let percent = ((value / totalUnits) * 100).toFixed(1);
                            return `${context.label}: ${value} (${percent}%)`;
                        }
                    }
                }
            }
        }
    });

    const barCtx = document.getElementById('barChart').getContext('2d');
    const labels = <?= json_encode(array_map(fn($m) => date("M Y", strtotime($m . "-01")), $monthlyLabels)) ?>;
    const data = <?= json_encode($monthlyRevenueData) ?>;

    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Revenue (KES)',
                data: data,
                backgroundColor: '#4F46E5',
                borderRadius: 4,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: context => `KES ${parseFloat(context.raw).toLocaleString()}`
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => 'KES ' + value.toLocaleString()
                    },
                    grid: { color: '#E5E7EB' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
</script>
</body>
</html>
