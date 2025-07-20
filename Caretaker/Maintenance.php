<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch caretaker's property
$caretaker_id = $_SESSION['caretaker_id'];
$stmt = $conn->prepare("SELECT property FROM caretaker WHERE id = ?");
$stmt->bind_param("i", $caretaker_id);
$stmt->execute();
$stmt->bind_result($caretaker_property);
$stmt->fetch();
$stmt->close();

// Handle deleting maintenance requests
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteMaintenance = $conn->prepare("DELETE FROM maintenance WHERE id = ? AND property = ?");
    $deleteMaintenance->bind_param("is", $id, $caretaker_property);
    if ($deleteMaintenance->execute()) {
        $success = "Maintenance request deleted successfully.";
    } else {
        $error = "Failed to delete maintenance request.";
    }
    $deleteMaintenance->close();
}

// Handle adding/updating maintenance request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $summary = $_POST['shortsummary'];
    $unit = $_POST['unit'];
    $status = $_POST['status'];
    $expense = $_POST['expense'];
    $payment_method = $_POST['payment_method'];
    $description = $_POST['description'];
    $date = date('Y-m-d');

    if (!empty($_POST['id'])) {
        // Update
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE maintenance SET summary=?, unit=?, status=?, expense=?, payment_method=?, description=? WHERE id=? AND property=?");
        $stmt->bind_param("ssssssis", $summary, $unit, $status, $expense, $payment_method, $description, $id, $caretaker_property);
        if ($stmt->execute()) {
            $success = "Maintenance request updated successfully.";
        } else {
            $error = "Failed to update maintenance request.";
        }
        $stmt->close();
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO maintenance (shortsummary, unit, status, expense, date, payment_method, description, property) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssss", $summary, $unit, $status, $expense, $date, $payment_method, $description, $caretaker_property);
        if ($stmt->execute()) {
            $success = "Maintenance request added successfully.";
        } else {
            $error = "Failed to add maintenance request.";
        }
        $stmt->close();
    }
}

// Fetch maintenance requests
$maintenanceQuery = "SELECT * FROM maintenance WHERE property = ?";
$stmt = $conn->prepare($maintenanceQuery);
$stmt->bind_param("s", $caretaker_property);
$stmt->execute();
$maintenanceRequests = $stmt->get_result();
$stmt->close();

$totalRequests = $maintenanceRequests->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Maintenance</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        .container {
            padding: 20px;
        }
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .add-btn {
            background: rgb(0,0,130);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            float: right;
        }
        .add-btn:hover {
            background: rgb(0,0,90);
        }
        .summary, .table-container {
            margin-top: 20px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .summary h2 {
            margin-top: 0;
            color: rgb(0,0,130);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        .action-link {
            color: grey;
            text-decoration: none;
            cursor: pointer;
            border-bottom: 1px solid grey;
        }
        .action-link:hover {
            color: darkgrey;
            border-bottom: 1px solid darkgrey;
        }
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 700px;
            max-width: 90%;
            display: flex;
            flex-direction: column;
        }
        .modal-header {
            font-size: 20px;
            margin-bottom: 20px;
            color: rgb(0,0,130);
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        input, select, textarea {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }
        textarea {
            resize: vertical;
        }
        .modal-actions {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            background: rgb(0,0,130);
            color: white;
            cursor: pointer;
        }
        .btn:hover {
            background: rgb(0,0,90);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-bar">
        <h1>Maintenance Requests</h1>
    </div>

    <?php if (!empty($success)): ?>
        <div class="success-message"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="summary">
        <h2>Summary</h2>
        <p>Total Maintenance Requests: <?= $totalRequests ?></p>
    </div>

    <div class="container">
        <button class="add-btn" onclick="openAddModal()">Add Maintenance Request</button>
    </div>

    <div class="table-container">
        <table>
            <thead>
            <tr>
                <th>Short Summary</th>
                <th>Unit</th>
                <th>Category</th>
                <th>Expense</th>
                <th>Date Submitted</th>
                <th>Payment Method</th>
                <th>Options</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = $maintenanceRequests->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['shortsummary']) ?></td>
                    <td><?= htmlspecialchars($row['unit']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><?= htmlspecialchars($row['expense']) ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['payment_method']) ?></td>
                    <td>
                        <span class="action-link" onclick='openEditModal(<?= json_encode($row) ?>)'>Edit</span> |
                        <span class="action-link" onclick="deleteRequest(<?= $row['id'] ?>)">Delete</span>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="maintenanceModal" class="modal">
    <form class="modal-content" method="POST">
        <div class="modal-header" id="modalTitle">Add Maintenance Request</div>
        <div class="form-grid">
        <div class="input-group">
                <label>Short Summary</label>
                <select name="shortsummary" id="shortsummary">
                    <option value="">Select a Summary</option>
                    <option value="Electricals">Electricals</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="Painting">Painting</option>
                    <option value="Woodwork">Woodwork</option>
                    <option value="General">General</option>
                </select>
            </div>
            <div class="input-group">
                <label>Unit</label>
                <select name="unit" id="unitDropdown" required>
                    <option value="">Select a Unit</option>
                </select>
            </div>
            <div class="form-group">
                <label>Category</label>
                <select name="category" id="category" required>
                    <option value="Open">Open</option>
                    <option value="Ongoing">Ongoing</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div class="form-group">
                <label>Expense</label>
                <input type="number" step="0.01" name="expense" id="expense">
            </div>
            <div class="form-group">
                <label>Payment Method</label>
                <select name="payment_method" id="payment_method" required>
                    <option value="Cash">Cash</option>
                    <option value="Bank">Bank</option>
                    <option value="Mpesa">Mpesa</option>
                    <option value="Security Deposit">Security Deposit</option>
                </select>
            </div>
        </div>
        <input type="hidden" name="id" id="record_id">
        <div class="modal-actions">
            <button type="button" class="btn" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn">Save</button>
        </div>
    </form>
</div>

<script>
   function openAddModal() {
    document.getElementById('modalTitle').textContent = "Add Maintenance Request";
    document.getElementById('shortsummary').value = '';
    document.getElementById('unitDropdown').innerHTML = '<option value="">Loading...</option>'; // Show loading state
    document.getElementById('category').value = 'Open';
    document.getElementById('expense').value = '';
    document.getElementById('payment_method').value = 'Cash';
    document.getElementById('record_id').value = '';

    // Fetch units for the caretaker's property
    fetch(`get_units.php?property=<?php echo urlencode($caretaker_property); ?>`)
        .then(response => response.json())
        .then(data => {
            const unitDropdown = document.getElementById('unitDropdown');
            unitDropdown.innerHTML = '<option value="">Select a Unit</option>'; // Reset dropdown
            data.forEach(unit => {
                const option = document.createElement('option');
                option.value = unit.unit;
                option.textContent = `${unit.unit} (${unit.status})`;
                option.disabled = unit.status === 'Occupied'; // Disable occupied units
                unitDropdown.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error fetching units:', error);
            document.getElementById('unitDropdown').innerHTML = '<option value="">Error loading units</option>';
        });

    document.getElementById('maintenanceModal').style.display = 'flex';
    }

    function openEditModal(data) {
        document.getElementById('modalTitle').textContent = "Edit Maintenance Request";
        document.getElementById('shortsummary').value = data.summary;
        document.getElementById('unitDropdown').value = data.unit;
        document.getElementById('category').value = data.category;
        document.getElementById('expense').value = data.expense;
        document.getElementById('payment_method').value = data.payment_method;
        document.getElementById('record_id').value = data.id;
        document.getElementById('maintenanceModal').style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('maintenanceModal').style.display = 'none';
    }

    function deleteRequest(id) {
        if (confirm("Are you sure you want to delete this maintenance request?")) {
            window.location.href = `?delete=${id}`;
        }
    }
</script>
</body>
</html>
