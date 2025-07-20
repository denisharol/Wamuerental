<?php include_once 'auth_admin.php'; ?>
<?php 
include_once 'log_action.php';

$conn = new mysqli("localhost", "root", "", "demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$admin_id = $_SESSION['admin_id'];

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $getUnit = $conn->prepare("SELECT property, unit FROM users WHERE id = ?");
    $getUnit->bind_param("i", $id);
    $getUnit->execute();
    $unitResult = $getUnit->get_result();
    $unitData = $unitResult->fetch_assoc();
    $getUnit->close();

    if ($unitData) {
        $property = $unitData['property'];
        $unit = $unitData['unit'];

        // Disable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=0");

        $deleteTenant = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteTenant->bind_param("i", $id);
        if ($deleteTenant->execute()) {
            $updateUnit = $conn->prepare("UPDATE units SET status = 'Vacant' WHERE property = ? AND unit = ?");
            $updateUnit->bind_param("ss", $property, $unit);
            $updateUnit->execute();
            $updateUnit->close();

            $success = "Tenant deleted successfully, and unit status updated to 'Vacant'.";
        } else {
            $error = "Failed to delete tenant.";
        }
        $deleteTenant->close();

        // Re-enable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=1");
    }
}

$filter = $_GET['filter'] ?? 'All';
$propertyQuery = "SELECT DISTINCT property FROM users WHERE property IS NOT NULL";
$properties = $conn->query($propertyQuery);

if ($filter !== 'All') {
    $tenantQuery = "SELECT * FROM users WHERE property = ? AND id != 1 AND id NOT IN (SELECT id FROM caretaker)";
    $stmt = $conn->prepare($tenantQuery);
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $tenants = $stmt->get_result();
} else {
    $tenantQuery = "SELECT * FROM users WHERE id != 1 AND id NOT IN (SELECT id FROM caretaker)";
    $tenants = $conn->query($tenantQuery);
}

$totalUnits = $conn->query("SELECT COUNT(*) as count FROM users WHERE id NOT IN (SELECT id FROM caretaker)")->fetch_assoc()['count'];
$vacancies = $conn->query("SELECT COUNT(*) as count FROM users WHERE rent_amount = 0 AND id NOT IN (SELECT id FROM caretaker)")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tenants</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            padding: 20px;
        }
        .filters, .summary {
            margin-bottom: 20px;
        }
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 800px;
            padding: 20px;
            border-radius: 8px;
            overflow-y: auto;
            max-height: 90vh;
        }
        .action-link {
            color: grey;
            text-decoration: none;
            border-bottom: 1px solid grey;
            cursor: pointer;
        }
        .action-link:hover {
            color: darkgrey;
            border-bottom: 1px solid darkgrey;
        }
        
        .modern-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(30, 41, 59, 0.7);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modern-modal-content {
            background: #fff;
            border-radius: 22px;
            padding: 38px 38px 32px 38px;
            max-width: 700px;
            width: 98%;
            box-shadow: 0 12px 40px rgba(30,41,59,0.18), 0 1.5px 4px rgba(30,41,59,0.07);
            position: relative;
            animation: fadeIn 0.3s;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            border: 1.5px solid #e5e7eb;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .close-btn {
            position: absolute;
            top: 18px;
            right: 22px;
            font-size: 2rem;
            color: #888;
            cursor: pointer;
            transition: color 0.2s;
            z-index: 10;
        }
        .close-btn:hover {
            color: #222;
        }
        .tenant-modal-title {
            font-size: 1.2rem;
            font-weight: 400;
            color:rgb(10, 10, 10);
            margin-bottom: 18px;
            letter-spacing: 0.5px;
            text-align: left;
            width: 100%;
            border-bottom: 1.5px solid #e5e7eb;
            padding-bottom: 8px;
        }
        .tenant-modal-body {
            display: flex;
            flex-direction: row;
            align-items: flex-start;
            width: 100%;
            gap: 36px;
        }
        .tenant-modal-img-wrap {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            min-width: 130px;
            max-width: 130px;
        }
        .tenant-modal-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #cbd5e1;
            background: #f1f5f9;
            box-shadow: 0 4px 18px rgba(30,41,59,0.10);
            margin-bottom: 0;
        }
        .tenant-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            gap: 10px;
        }
        .tenant-detail-row {
            display: flex;
            flex-direction: row;
            align-items: center;
            margin-bottom: 2px;
            font-size: 1.08rem;
            color:rgb(11, 11, 11);
            font-weight: 400;
        }
        .detail-label {
            font-weight: 400;
            color:rgb(10, 10, 10);
            min-width: 140px;
            display: inline-block;
        }
        .tenant-name-btn {
            background: none;
            border: none;
            color: inherit;
            font: inherit;
            font-size: inherit;
            font-weight: inherit;
            padding: 0;
            border-radius: 0;
            cursor: pointer;
            transition: color 0.2s;
            text-decoration: none;
            box-shadow: none;
        }
        .tenant-name-btn:hover {
            color: #2563eb;
            background: none;
        }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>Tenants</h1>
    </header>

    <?php if (!empty($success)): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

<div class="filters">
    <h3>Filter Tenants by Property</h3>
    <form method="get">
        <label for="property">Property</label>
        <select name="filter" onchange="this.form.submit()">
            <option value="All">All Properties</option>
            <?php while($p = $properties->fetch_assoc()): ?>
                <?php $property = $p['property']; ?>
                <option value="<?= htmlspecialchars($property) ?>" <?= ($filter == $property) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($property) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </form>
</div>

    <div class="summary">
        <h2>Summary</h2>
        <p>Number of Tenants: <?= $totalUnits ?></p>
    </div>

    <div class="table-container">
        <table border="1" cellpadding="5" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>Tenant Name</th>
                    <th>ID Number</th>
                    <th>Phone Number</th>
                    <th>Email Address</th>
                    <th>Property</th>
                    <th>Unit</th>
                    <th>Move-in Date</th>
                    <th>Rent</th>
                    <th>Options</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($tenants->num_rows > 0): ?>
                    <?php while($row = $tenants->fetch_assoc()): 
                        // Calculate duration as tenant
                        $moveIn = new DateTime($row['move_in_date']);
                        $now = new DateTime();
                        $interval = $moveIn->diff($now);
                        $duration = ($interval->y > 0 ? $interval->y . ' year(s) ' : '') .
                                    ($interval->m > 0 ? $interval->m . ' month(s) ' : '') .
                                    $interval->d . ' day(s)';
                        // Fetch password (plain text)
                        $password = $row['password'] ?? '';

                        // Fetch statement data for this tenant
                        $tenantId = $row['id'];

                        // Get security deposit from users table (already in $row if selected)
                        $securityDeposit = $row['security_deposit'] ?? 0;

                        // Get rent amount and move-in date
                        $rentAmount = $row['rent_amount'] ?? 0;
                        $moveInDate = $row['move_in_date'] ?? null;

                        // Get property for billing
                        $property = $row['property'] ?? '';

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
                        $moveIn = $moveInDate ? new DateTime($moveInDate) : null;
                        $now = new DateTime();
                        $months = $moveIn ? (($now->format('Y') - $moveIn->format('Y')) * 12 + ($now->format('n') - $moveIn->format('n')) + 1) : 0;

                        // Total required amount
                        $required = $months * $monthlyCharge;

                        // Add security deposit once for first month
                        if ($months >= 1) {
                            $required += $rentAmount;
                        }

                        // Get total paid by tenant
                        $transactionStmt = $conn->prepare("SELECT SUM(amount) FROM transactions WHERE tenant_id = ?");
                        $transactionStmt->bind_param("i", $tenantId);
                        $transactionStmt->execute();
                        $transactionStmt->bind_result($totalPaid);
                        $transactionStmt->fetch();
                        $transactionStmt->close();
                        $totalPaid = floatval($totalPaid ?? 0);

                        // Calculate overpayment and amount due
                        $overpayment = $totalPaid - $required;
                        $amountDue = max(0, $required - $totalPaid);
                    ?>
                        <tr>
                            <td>
                                <button 
                                    class="tenant-name-btn" 
                                    onclick='showTenantInfo(<?= json_encode([
                                        "name" => $row["name"],
                                        "id_number" => $row["id_number"],
                                        "phone" => $row["phone"],
                                        "email" => $row["email"],
                                        "property" => $row["property"],
                                        "unit" => $row["unit"],
                                        "move_in_date" => $row["move_in_date"],
                                        "duration" => $duration,
                                        "monthly_charge" => $monthlyCharge,
                                        "total_required" => $required,
                                        "total_paid" => $totalPaid,
                                        "overpayment" => $overpayment,
                                        "amount_due" => $amountDue,
                                        "security_deposit" => $securityDeposit,
                                    ]) ?>)'
                                    title="Show Detailed Tenant Information"
                                ><?= htmlspecialchars($row['name']) ?></button>
                            </td>
                            <td><?= htmlspecialchars($row['id_number']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= htmlspecialchars($row['property']) ?></td>
                            <td><?= htmlspecialchars($row['unit']) ?></td>
                            <td><?= htmlspecialchars($row['move_in_date']) ?></td>
                            <td><?= number_format($row['rent_amount'], 2) ?></td>
                            <td>
                                <span class="action-link" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">Edit</span>
                                |
                                <span class="action-link" onclick="deleteTenant(<?= $row['id'] ?>)">Delete</span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9">No tenants found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Tenant Info Modal -->
    <div id="tenantInfoModal" class="modern-modal" style="display:none;">
        <div class="modern-modal-content">
            <span class="close-btn" onclick="closeTenantInfo()">&times;</span>
            <div class="tenant-modal-title">Tenant Information</div>
            <div class="tenant-modal-body">
                <div class="tenant-modal-img-wrap">
                    <img src="../images/user.jpg" alt="User" class="tenant-modal-img" id="tenantInfoImg">
                </div>
                <div class="tenant-details">
                    <div class="tenant-detail-row"><span class="detail-label">Name:</span> <span id="tenantInfoName"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">ID Number:</span> <span id="tenantInfoId"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Phone:</span> <span id="tenantInfoPhone"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Email:</span> <span id="tenantInfoEmail"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Property:</span> <span id="tenantInfoProperty"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Unit:</span> <span id="tenantInfoUnit"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Move-in Date:</span> <span id="tenantInfoMoveIn"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Tenant Duration:</span> <span id="tenantInfoDuration"></span></div>
                    <!-- Statement Details -->
                    <div class="tenant-detail-row"><span class="detail-label">Monthly Charge:</span> <span id="tenantInfoMonthlyCharge"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Total Required:</span> <span id="tenantInfoTotalRequired"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Total Paid:</span> <span id="tenantInfoTotalPaid"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Overpayment:</span> <span id="tenantInfoOverpayment"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Amount Due:</span> <span id="tenantInfoAmountDue"></span></div>
                    <div class="tenant-detail-row"><span class="detail-label">Security Deposit:</span> <span id="tenantInfoSecurityDeposit"></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openEditModal(data) {
        document.getElementById('modalTitle').textContent = "Edit Tenant";
        const iframe = document.getElementById('modalIframe');
        iframe.src = `create_account.php?edit=1&name=${encodeURIComponent(data.name)}&idNumber=${encodeURIComponent(data.id_number)}&phone=${encodeURIComponent(data.phone)}&email=${encodeURIComponent(data.email)}&property=${encodeURIComponent(data.property)}&unit=${encodeURIComponent(data.unit)}&moveInDate=${encodeURIComponent(data.move_in_date)}&rentAmount=${encodeURIComponent(data.rent_amount)}`;
        document.getElementById('tenantModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('tenantModal').style.display = 'none';
    }

    function deleteTenant(id) {
        if (confirm("Are you sure you want to delete this tenant?")) {
            window.location.href = `?delete=${id}`;
        }
    }

    function showTenantInfo(data) {
        document.getElementById('tenantInfoName').textContent = data.name;
        document.getElementById('tenantInfoId').textContent = data.id_number;
        document.getElementById('tenantInfoPhone').textContent = data.phone;
        document.getElementById('tenantInfoEmail').textContent = data.email;
        document.getElementById('tenantInfoProperty').textContent = data.property;
        document.getElementById('tenantInfoUnit').textContent = data.unit;
        document.getElementById('tenantInfoMoveIn').textContent = data.move_in_date;
        document.getElementById('tenantInfoDuration').textContent = data.duration;
        // Statement details (add these keys to your PHP when passing data)
        document.getElementById('tenantInfoMonthlyCharge').textContent = data.monthly_charge ? Number(data.monthly_charge).toLocaleString() + " KES" : "-";
        document.getElementById('tenantInfoTotalRequired').textContent = data.total_required ? Number(data.total_required).toLocaleString() + " KES" : "-";
        document.getElementById('tenantInfoTotalPaid').textContent = data.total_paid ? Number(data.total_paid).toLocaleString() + " KES" : "-";
        document.getElementById('tenantInfoOverpayment').textContent = data.overpayment ? Number(data.overpayment).toLocaleString() + " KES" : "-";
        document.getElementById('tenantInfoAmountDue').textContent = data.amount_due ? Number(data.amount_due).toLocaleString() + " KES" : "-";
        document.getElementById('tenantInfoSecurityDeposit').textContent = data.security_deposit ? Number(data.security_deposit).toLocaleString() + " KES" : "-";
        document.getElementById('tenantInfoModal').style.display = 'flex';
    }

    function closeTenantInfo() {
        document.getElementById('tenantInfoModal').style.display = 'none';
    }
</script>
</body>
</html>