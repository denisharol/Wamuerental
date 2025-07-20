<?php include 'auth_admin.php'; ?>
<?php
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch properties and units
$unitsResult = $conn->query("SELECT DISTINCT property, unit FROM units");
$propertyUnits = [];
while ($row = $unitsResult->fetch_assoc()) {
    $propertyUnits[$row['property']][] = $row['unit'];
}

// Fetch all categories (shortsummary) from maintenance table
$categoriesResult = $conn->query("SELECT DISTINCT shortsummary FROM maintenance");
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row['shortsummary'];
}

// Handle saving expense (add or edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_expense'])) {
    $expense_id = $_POST['expense_id'] ?? null;
    $maintenance_id = $_POST['maintenance_id'] ?? null;
    $category = $_POST['category'];
    $property = $_POST['property'];
    $unit = $_POST['unit'];
    $date = $_POST['date'];
    $amount = $_POST['amount'];
    $status = "Paid";

    if ($expense_id) {
        // Update expense
        $stmt = $conn->prepare("UPDATE expenses SET category=?, property=?, unit=?, date=?, amount=?, status=? WHERE id=?");
        $stmt->bind_param("ssssdsi", $category, $property, $unit, $date, $amount, $status, $expense_id);
        $stmt->execute();
        $stmt->close();

        // If linked to maintenance, update maintenance as well
        if ($maintenance_id) {
            $stmt = $conn->prepare("UPDATE maintenance SET shortsummary=?, property=?, unit=?, date=?, expense=? WHERE id=?");
            $stmt->bind_param("ssssdi", $category, $property, $unit, $date, $amount, $maintenance_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // Insert new expense
        $null = NULL;
        $stmt = $conn->prepare("INSERT INTO expenses (maintenance_id, category, property, unit, date, amount, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssds", $null, $category, $property, $unit, $date, $amount, $status);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch expenses data (joined with maintenance for editing)
$expensesQuery = "
    SELECT 
        e.id AS expense_id,
        e.maintenance_id,
        e.category,
        e.property,
        e.unit,
        e.date,
        e.amount,
        e.status
    FROM expenses e
    LEFT JOIN maintenance m ON e.maintenance_id = m.id
    ORDER BY e.date DESC
";
$expensesResult = $conn->query($expensesQuery);
$expenses = [];
while ($row = $expensesResult->fetch_assoc()) {
    $expenses[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Expenses</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background-color: #f4f6f8;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            padding: 20px;
        }

        .filter-container {
            background-color: #fff;
            padding: 16px;
            margin-bottom: 20px;
            width: 25%;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-container label {
            font-weight: 500;
            display: block;
            margin-bottom: 8px;
        }

        .filter-container select {
            width: 100%;
            padding: 10px;
            border: 1px solid rgb(0,0,130);
            border-radius: 6px;
        }

        .add-button {
            background-color: rgb(0,0,130);
            color: white;
            padding: 10px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 20px 0;
            float: right;
        }

        .table-container {
            background-color: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            clear: both;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container th, .table-container td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .table-container th {
            background-color: rgb(0,0,130);
            color: white;
        }

        .edit-btn {
            background: none;
            color: grey;
            border: none;
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 999;
        }

        .modal-content {
            background-color: #fff;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            padding: 30px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }

        .modal-header h2 {
            margin: 0 0 20px 0;
            font-size: 24px;
            color: #333;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .form-group select, .form-group input {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid rgb(0,0,130);
        }

        .modal-footer {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .cancel-btn, .save-btn {
            padding: 10px 16px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            cursor: pointer;
        }

        .cancel-btn {
            background-color: #ccc;
            color: #000;
        }

        .save-btn {
            background-color: rgb(0,0,130);
            color: white;
        }

        .modal-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 10px;
            margin-bottom: 10px;
            line-height: 1;
        }

        .modal-form-grid .form-group {
            display: flex;
            flex-direction: column;
            line-height: 1;
        }

        .modal-form-grid .form-group label {
            margin-bottom: 2px;
            font-weight: 500;
            line-height: 1;
        }

        .modal-form-grid .form-group input,
        .modal-form-grid .form-group select {
            width: 100%;
            min-width: 0;
            box-sizing: border-box;
            padding: 10px 12px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid rgb(0,0,130);
            line-height: 1;
        }

        @media (max-width: 700px) {
            .modal-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Expenses</h1>

    <div class="filter-container">
        <label for="filterProperty">Filter by Property</label>
        <select id="filterProperty" onchange="filterTable()">
            <option value="">All Properties</option>
            <?php foreach ($propertyUnits as $prop => $units): ?>
                <option value="<?= htmlspecialchars($prop) ?>"><?= htmlspecialchars($prop) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <button class="add-button" onclick="openModal()">Add Expense</button>

    <div class="table-container">
        <table id="expensesTable">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Property(Unit)</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr data-property="<?= htmlspecialchars($expense['property']) ?>">
                        <td><?= htmlspecialchars($expense['category']) ?></td>
                        <td><?= htmlspecialchars($expense['property']) ?> (<?= htmlspecialchars($expense['unit']) ?>)</td>
                        <td><?= htmlspecialchars($expense['date']) ?></td>
                        <td><?= number_format($expense['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($expense['status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Expense Modal -->
<div id="expenseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Add Expense</h2>
        </div>
        <form id="expenseForm" method="post" autocomplete="off">
            <input type="hidden" name="expense_id" id="expenseId">
            <input type="hidden" name="maintenance_id" id="maintenanceId">
            <div class="modal-form-grid">
                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" id="category" required placeholder="Enter category">
                </div>
                <div class="form-group">
                    <label>Property</label>
                    <select name="property" id="propertySelect" required onchange="populateUnits()">
                        <option value="">Select Property</option>
                        <option value="All properties">All properties</option>
                        <?php foreach ($propertyUnits as $prop => $units): ?>
                            <option value="<?= htmlspecialchars($prop) ?>"><?= htmlspecialchars($prop) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unit</label>
                    <select name="unit" id="unitSelect" required>
                        <option value="">Select Unit</option>
                        <option value="All units">All units</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="date" id="expenseDate" required>
                </div>
                <div class="form-group">
                    <label>Amount</label>
                    <input type="number" step="0.01" name="amount" id="expenseAmount" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <input type="text" name="status" id="expenseStatus" value="Paid" readonly style="background:#eee;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                <button type="submit" class="save-btn" name="save_expense" id="saveExpenseBtn">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    const propertyUnits = <?= json_encode($propertyUnits) ?>;

    function populateUnits() {
        const prop = document.getElementById("propertySelect").value;
        const unitSelect = document.getElementById("unitSelect");
        unitSelect.innerHTML = '<option value="">Select Unit</option>';
        if (prop === "All properties") {
            unitSelect.innerHTML += '<option value="All units">All units</option>';
        } else if (propertyUnits[prop]) {
            unitSelect.innerHTML += '<option value="All units">All units</option>';
            propertyUnits[prop].forEach(unit => {
                const opt = document.createElement("option");
                opt.value = unit;
                opt.text = unit;
                unitSelect.add(opt);
            });
        }
    }

    function openModal(isEdit = false) {
        document.getElementById("expenseModal").style.display = "flex";
        document.getElementById("modalTitle").innerText = isEdit ? "Edit Expense" : "Add Expense";
        document.getElementById("expenseForm").reset();
        document.getElementById("expenseId").value = "";
        document.getElementById("maintenanceId").value = "";
        document.getElementById("propertySelect").disabled = false;
        document.getElementById("unitSelect").disabled = false;
        document.getElementById("expenseDate").disabled = false;
        document.getElementById("expenseStatus").value = "Paid";
    }

    function closeModal() {
        document.getElementById("expenseModal").style.display = "none";
    }

    function filterTable() {
        const filter = document.getElementById("filterProperty").value;
        const rows = document.querySelectorAll("#expensesTable tbody tr");
        rows.forEach(row => {
            if (!filter || row.getAttribute("data-property") === filter) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    }

    function disableSubmitBtn() {
        document.getElementById('saveExpenseBtn').disabled = true;
    }
</script>
</body>
</html>