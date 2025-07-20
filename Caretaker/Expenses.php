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

// Fetch maintenance and expenses data for the caretaker's property
$expensesQuery = "
    SELECT 
        m.id AS maintenance_id,
        m.shortsummary AS category,
        m.property,
        m.unit,
        m.date,
        m.expense AS amount,
        CASE 
            WHEN m.expense > 0 THEN 'Paid'
            ELSE 'Unpaid'
        END AS status
    FROM maintenance m
    LEFT JOIN expenses e ON m.id = e.maintenance_id
    WHERE m.property = ?
";
$stmt = $conn->prepare($expensesQuery);
$stmt->bind_param("s", $caretaker_property);
$stmt->execute();
$expensesResult = $stmt->get_result();
$expenses = [];
while ($row = $expensesResult->fetch_assoc()) {
    $expenses[] = $row;
}
$stmt->close();

// Fetch all categories (shortsummary) from maintenance table for the caretaker's property
$categoriesQuery = "SELECT DISTINCT shortsummary FROM maintenance WHERE property = ?";
$stmt = $conn->prepare($categoriesQuery);
$stmt->bind_param("s", $caretaker_property);
$stmt->execute();
$categoriesResult = $stmt->get_result();
$categories = [];
while ($row = $categoriesResult->fetch_assoc()) {
    $categories[] = $row['shortsummary'];
}
$stmt->close();

// Fetch properties and units for the caretaker's property
$unitsQuery = "SELECT DISTINCT property, unit FROM units WHERE property = ?";
$stmt = $conn->prepare($unitsQuery);
$stmt->bind_param("s", $caretaker_property);
$stmt->execute();
$unitsResult = $stmt->get_result();
$propertyUnits = [];
while ($row = $unitsResult->fetch_assoc()) {
    $propertyUnits[$row['property']][] = $row['unit'];
}
$stmt->close();

// Handle saving expense
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $maintenance_id = $_POST['maintenance_id'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $status = $_POST['status'];

    $checkQuery = $conn->prepare("SELECT id FROM expenses WHERE maintenance_id = ?");
    $checkQuery->bind_param("i", $maintenance_id);
    $checkQuery->execute();
    $checkResult = $checkQuery->get_result();

    if ($checkResult->num_rows > 0) {
        $stmt = $conn->prepare("UPDATE expenses SET amount = ?, date = ?, status = ? WHERE maintenance_id = ?");
        $stmt->bind_param("dssi", $amount, $date, $status, $maintenance_id);
    } else {
        $stmt = $conn->prepare("INSERT INTO expenses (maintenance_id, amount, date, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $maintenance_id, $amount, $date, $status);
    }

    if ($stmt->execute()) {
        $success = true;
    } else {
        $error = "Failed to save expense.";
    }
    $stmt->close();
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
    </style>
</head>
<body>
<div class="container">
    <h1>Expenses</h1>
    <button class="add-button" onclick="openModal()">Add Expense</button>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Property(Unit)</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Options</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?= htmlspecialchars($expense['category']) ?></td>
                        <td><?= htmlspecialchars($expense['property']) ?> (<?= htmlspecialchars($expense['unit']) ?>)</td>
                        <td><?= htmlspecialchars($expense['date']) ?></td>
                        <td><?= number_format($expense['amount'], 2) ?></td>
                        <td><?= $expense['status'] ?></td>
                        <td>
                            <button class="edit-btn" onclick='editExpense(<?= json_encode($expense) ?>)'>Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const propertyUnits = <?= json_encode($propertyUnits) ?>;

    function populateUnits() {
        const prop = document.getElementById("propertySelect").value;
        const unitSelect = document.getElementById("unitSelect");
        unitSelect.innerHTML = '<option value="">Select Unit</option>';
        if (propertyUnits[prop]) {
            propertyUnits[prop].forEach(unit => {
                const opt = document.createElement("option");
                opt.value = unit;
                opt.text = unit;
                unitSelect.add(opt);
            });
        }
    }

    function openModal() {
        document.getElementById("expenseModal").style.display = "flex";
        document.getElementById("modalTitle").innerText = "Add Expense";
        document.getElementById("expenseForm").reset();
    }

    function closeModal() {
        document.getElementById("expenseModal").style.display = "none";
    }

    function editExpense(data) {
        openModal();
        document.getElementById("modalTitle").innerText = "Edit Expense";
        document.getElementById("maintenanceId").value = data.maintenance_id;
        document.getElementById("expenseAmount").value = data.amount;
        document.getElementById("expenseDate").value = data.date;
        document.getElementById("expenseStatus").value = data.status;
        document.getElementById("propertySelect").value = data.property;
        populateUnits();
        document.getElementById("unitSelect").value = data.unit;
        document.getElementById("category").value = data.category;
    }
</script>
</body>
</html>