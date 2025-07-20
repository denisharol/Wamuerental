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

// Handle updating tenants
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_tenant'])) {
    $id = intval($_POST['id']);
    $name = $_POST['name'];
    $idNumber = $_POST['idNumber'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $unit = $_POST['unit'];
    $password = $_POST['password'];

    // Update tenant details
    $query = "UPDATE users SET name = ?, id_number = ?, phone = ?, email = ?, unit = ?";
    $params = [$name, $idNumber, $phone, $email, $unit];

    if (!empty($password)) {
        $query .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    $query .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat('s', count($params)), ...$params);

    if ($stmt->execute()) {
        $success = "Tenant updated successfully!";
    } else {
        $error = "Failed to update tenant.";
    }

    $stmt->close();
}

// Handle deleting tenants
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $getUnit = $conn->prepare("SELECT unit FROM users WHERE id = ? AND property = ?");
    $getUnit->bind_param("is", $id, $caretaker_property);
    $getUnit->execute();
    $unitResult = $getUnit->get_result();
    $unitData = $unitResult->fetch_assoc();
    $getUnit->close();

    if ($unitData) {
        $unit = $unitData['unit'];

        // Disable foreign key checks
        $conn->query("SET FOREIGN_KEY_CHECKS=0");

        $deleteTenant = $conn->prepare("DELETE FROM users WHERE id = ? AND property = ?");
        $deleteTenant->bind_param("is", $id, $caretaker_property);
        if ($deleteTenant->execute()) {
            $updateUnit = $conn->prepare("UPDATE units SET status = 'Vacant' WHERE property = ? AND unit = ?");
            $updateUnit->bind_param("ss", $caretaker_property, $unit);
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

// Fetch tenants for the caretaker's property, excluding the caretaker's ID
$tenantQuery = "SELECT * FROM users WHERE property = ? AND id != ?";
$stmt = $conn->prepare($tenantQuery);
$stmt->bind_param("si", $caretaker_property, $caretaker_id);
$stmt->execute();
$tenants = $stmt->get_result();
$stmt->close();

$totalTenants = $tenants->num_rows;
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
        .summary {
            margin-bottom: 20px;
            background: white;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        .summary h2 {
            margin-top: 0;
            color: rgb(0, 0, 130);
        }
        .summary p {
            font-size: 16px;
            margin: 8px 0;
        }
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        .table-container table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .table-container th, .table-container td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
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
        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            width: 600px;
            max-width: 90%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        .modal-header {
            margin-bottom: 20px;
        }
        .modal-header h2 {
            margin: 0;
            font-size: 22px;
            color: #333;
        }
        .form-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .form-grid .form-group {
            flex: 1 1 45%;
            display: flex;
            flex-direction: column;
        }
        .form-grid label {
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-grid input, .form-grid select {
            padding: 8px 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }
        .modal-footer button {
            padding: 8px 20px;
            margin-left: 10px;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .modal-footer .save-btn {
            background: #007BFF;
            color: white;
        }
        .modal-footer .cancel-btn {
            background: grey;
            color: white;
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

    <div class="summary">
        <h2>Summary</h2>
        <p>Number of Tenants: <?= $totalTenants ?></p>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Tenant Name</th>
                    <th>ID Number</th>
                    <th>Phone Number</th>
                    <th>Email Address</th>
                    <th>Unit</th>
                    <th>Move-in Date</th>
                    <th>Rent</th>
                    <th>Options</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $tenants->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['id_number']) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['unit']) ?></td>
                        <td><?= htmlspecialchars($row['move_in_date']) ?></td>
                        <td><?= number_format($row['rent_amount'], 2) ?></td>
                        <td>
                            <span class="action-link" onclick='openEditModal(<?= json_encode($row) ?>)'>Edit</span> |
                            <span class="action-link" onclick="deleteTenant(<?= $row['id'] ?>)">Delete</span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="tenantModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit Tenant</h2>
        </div>
        <form method="POST">
            <div class="form-grid">
                <input type="hidden" name="id" id="editId">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" id="editName">
                </div>
                <div class="form-group">
                    <label>ID Number</label>
                    <input type="text" name="idNumber" id="editIdNumber">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="editPhone">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" name="email" id="editEmail">
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <select name="unit" id="editUnit"></select>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="editPassword" placeholder="Enter new password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                <button type="submit" name="update_tenant" class="save-btn">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(data) {
        document.getElementById('editId').value = data.id;
        document.getElementById('editName').value = data.name;
        document.getElementById('editIdNumber').value = data.id_number;
        document.getElementById('editPhone').value = data.phone;
        document.getElementById('editEmail').value = data.email;
        document.getElementById('editPassword').value = ""; // Clear password field

        // Fetch units for the caretaker's property
        fetch(`get_units.php?property=<?php echo urlencode($caretaker_property); ?>`)
            .then(response => response.json())
            .then(units => {
                const unitDropdown = document.getElementById('editUnit');
                unitDropdown.innerHTML = '<option value="">Select a Unit</option>';
                units.forEach(unit => {
                    const option = document.createElement('option');
                    option.value = unit.unit;
                    option.textContent = `${unit.unit} (${unit.status})`;
                    option.disabled = unit.status === 'Occupied';
                    unitDropdown.appendChild(option);
                });
                unitDropdown.value = data.unit; // Set the current unit
            })
            .catch(error => console.error('Error fetching units:', error));

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
</script>
</body>
</html>