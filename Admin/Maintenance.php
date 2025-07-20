<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance</title>
    <link rel="stylesheet" href="styles.css">
    <style>
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
    </style>
</head>
<body>
<?php 
include_once 'auth_admin.php';
include_once 'log_action.php';
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_maintenance'])) {
    $id = $_POST['id'] ?? null;
    $shortsummary = $_POST['shortsummary'] ?? null;
    $property = $_POST['property'] ?? null;
    $unit = $_POST['unit'] ?? null;
    $category = $_POST['category'] ?? null;
    $expense = $_POST['expense'] ?? null;
    $date = $_POST['date'] ?? null;
    $paymentMethod = $_POST['payment_method'] ?? null;

    if ($id) {
        // Fetch the previous expense amount
        $stmt = $conn->prepare("SELECT expense FROM maintenance WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($previousExpense);
        $stmt->fetch();
        $stmt->close();

        // Update the maintenance record
        $stmt = $conn->prepare("UPDATE maintenance SET shortsummary = ?, property = ?, unit = ?, category = ?, expense = ?, date = ?, payment_method = ? WHERE id = ?");
        $stmt->bind_param("ssssdssi", $shortsummary, $property, $unit, $category, $expense, $date, $paymentMethod, $id);
        $stmt->execute();
        $stmt->close();

        // Adjust the user's security deposit
        if ($paymentMethod === "Security Deposit") {
            $difference = $expense - $previousExpense;
            $stmt = $conn->prepare("UPDATE users SET security_deposit = security_deposit - ? WHERE property = ? AND unit = ?");
            $stmt->bind_param("dss", $difference, $property, $unit);
            $stmt->execute();
            $stmt->close();
        }

        // --- EXPENSE SYNC LOGIC ---
        // Only add/update expense if expense > 0 and payment method is not Tenant
        if ($expense > 0 && strtolower($paymentMethod) !== "tenant") {
            // Check if expense already exists for this maintenance
            $stmt = $conn->prepare("SELECT id FROM expenses WHERE maintenance_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                // Update
                $stmt->close();
                $status = "Unpaid";
                $stmt = $conn->prepare("UPDATE expenses SET category=?, property=?, unit=?, date=?, amount=?, status=? WHERE maintenance_id=?");
                $stmt->bind_param("ssssdsi", $shortsummary, $property, $unit, $date, $expense, $status, $id);
                $stmt->execute();
                $stmt->close();
            } else {
                // Insert
                $stmt->close();
                $status = "Unpaid";
                $stmt = $conn->prepare("INSERT INTO expenses (maintenance_id, category, property, unit, date, amount, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssds", $id, $shortsummary, $property, $unit, $date, $expense, $status);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            // Remove any expense linked to this maintenance
            $stmt = $conn->prepare("DELETE FROM expenses WHERE maintenance_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Insert a new maintenance record
        $stmt = $conn->prepare("INSERT INTO maintenance (shortsummary, property, unit, category, expense, date, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdss", $shortsummary, $property, $unit, $category, $expense, $date, $paymentMethod);
        $stmt->execute();
        $maintenance_id = $stmt->insert_id;
        $stmt->close();

        // Adjust the user's security deposit for new records
        if ($paymentMethod === "Security Deposit") {
            $stmt = $conn->prepare("UPDATE users SET security_deposit = security_deposit - ? WHERE property = ? AND unit = ?");
            $stmt->bind_param("dss", $expense, $property, $unit);
            $stmt->execute();
            $stmt->close();
        }

        // --- EXPENSE SYNC LOGIC ---
        if ($expense > 0 && strtolower($paymentMethod) !== "tenant") {
            $status = "Unpaid";
            $stmt = $conn->prepare("INSERT INTO expenses (maintenance_id, category, property, unit, date, amount, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssds", $maintenance_id, $shortsummary, $property, $unit, $date, $expense, $status);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    // Remove any expense linked to this maintenance
    $stmt = $conn->prepare("DELETE FROM expenses WHERE maintenance_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM maintenance WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$properties = $conn->query("SELECT DISTINCT property FROM properties");

$result = $conn->query("SELECT * FROM maintenance");
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<div class="container">
    <header>
        <h1>Maintenance</h1>
    </header>

    <div class="filters">
        <h3>Filter Maintenance Records</h3>
        <form method="get">
            <label for="property">Property</label>
            <select name="filter" id="property" onchange="this.form.submit()">
                <option value="All">All Properties</option>
                <?php while ($p = $properties->fetch_assoc()): ?>
                    <option value="<?php echo $p['property']; ?>"><?php echo $p['property']; ?></option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

    <button class="add-button" onclick="openModal()">Add Maintenance</button>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Short Summary</th>
                    <th>Property Name</th>
                    <th>Unit</th>
                    <th>Category</th>
                    <th>Expense (KES)</th>
                    <th>Date</th>
                    <th>Payment Method</th>
                    <th>Options</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['shortsummary']; ?></td>
                    <td><?php echo $row['property']; ?></td>
                    <td><?php echo $row['unit']; ?></td>
                    <td><?php echo $row['category']; ?></td>
                    <td><?php echo $row['expense']; ?></td>
                    <td><?php echo $row['date']; ?></td>
                    <td><?php echo $row['payment_method']; ?></td>
                    <td>
                        <span class="action-link" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</span>
                        |
                        <a href="?delete=<?php echo $row['id']; ?>" class="action-link" onclick="return confirm('Delete this maintenance record?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-title">
            <span>Add/Edit Maintenance</span>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form method="post" class="form-grid">
            <input type="hidden" name="id" id="maintenanceId">
            <?php if ($error): ?>
                <div class="error-box"><?php echo $error; ?></div>
            <?php endif; ?>
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
                <label>Property Name</label>
                <select name="property" id="propertyDropdown" required>
                    <option value="">Select a Property</option>
                    <?php
                    $properties->data_seek(0);
                    while ($p = $properties->fetch_assoc()): ?>
                        <option value="<?php echo $p['property']; ?>"><?php echo $p['property']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="input-group">
                <label>Unit</label>
                <select name="unit" id="unitDropdown" required>
                    <option value="">Select a Unit</option>
                </select>
            </div>
            <div class="input-group">
                <label>Category</label>
                <select name="category" id="category">
                    <option value="">Select a Category</option>
                    <option value="Open">Open</option>
                    <option value="Ongoing">Ongoing</option>
                    <option value="Closed">Closed</option>
                </select>
            </div>
            <div class="input-group">
                <label>Expense</label>
                <input type="number" step="0.01" name="expense" id="expense">
            </div>
            <div class="input-group">
                <label>Date</label>
                <input type="date" name="date" id="date">
            </div>
            <div class="input-group">
                <label>Payment Method</label>
                <select name="payment_method" id="paymentMethod">
                    <option value="">Select a Payment Method</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank">Bank</option>
                    <option value="Mpesa">Mpesa</option>
                    <option value="Cheque">Tenant</option>
                    <option value="Security Deposit">Security Deposit</option>
                </select>
            </div>
            <div class="button-group">
                <button type="submit" name="save_maintenance" id="saveBtn">Save Maintenance</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById("modal").style.display = "flex";
        document.body.style.overflow = "hidden";
        document.getElementById("maintenanceId").value = "";
        document.getElementById("shortsummary").value = "";
        document.getElementById("propertyDropdown").value = "";
        document.getElementById("unitDropdown").innerHTML = '<option value="">Select a Unit</option>';
        document.getElementById("category").value = "";
        document.getElementById("expense").value = "";
        document.getElementById("date").value = "";
        document.getElementById("paymentMethod").value = "";
    }

    function closeModal() {
        document.getElementById("modal").style.display = "none";
        document.body.style.overflow = "auto";
    }

    function openEditModal(data) {
        openModal();
        document.getElementById("maintenanceId").value = data.id;
        document.getElementById("shortsummary").value = data.shortsummary;
        document.getElementById("propertyDropdown").value = data.property;
        document.getElementById("unitDropdown").innerHTML = `<option value="${data.unit}" selected>${data.unit}</option>`;
        document.getElementById("category").value = data.category;
        document.getElementById("expense").value = data.expense;
        document.getElementById("date").value = data.date;
        document.getElementById("paymentMethod").value = data.payment_method;
    }

    document.getElementById("propertyDropdown").addEventListener("change", function () {
        const property = this.value;
        const unitDropdown = document.getElementById("unitDropdown");

        unitDropdown.innerHTML = '<option value="">Select a Unit</option>';

        if (property) {
            fetch(`get_units.php?property=${property}`)
                .then(response => response.json())
                .then(data => {
                    data.forEach(unit => {
                        const option = document.createElement("option");
                        option.value = unit.unit;
                        option.textContent = unit.unit;
                        unitDropdown.appendChild(option);
                    });
                })
                .catch(error => console.error("Error fetching units:", error));
        }
    });
</script>
</body>
</html>