<?php
if (session_status() == PHP_SESSION_NONE) {
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

// Handle form submission for adding or editing a bill
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $property = $_POST['property'];
    $wifi = $_POST['wifi'];
    $water = $_POST['water'];
    $electricity = $_POST['electricity'];

    if ($id) {
        // Update existing bill
        $stmt = $conn->prepare("UPDATE billing SET property = ?, wifi = ?, water = ?, electricity = ? WHERE id = ?");
        $stmt->bind_param("sdddi", $property, $wifi, $water, $electricity, $id);
    } else {
        // Insert new bill
        $stmt = $conn->prepare("INSERT INTO billing (property, wifi, water, electricity) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sddd", $property, $wifi, $water, $electricity);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM billing WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch all billing records for the caretaker's property
$billingQuery = "SELECT * FROM billing WHERE property = ?";
$stmt = $conn->prepare($billingQuery);
$stmt->bind_param("s", $caretaker_property);
$stmt->execute();
$billingResult = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 0;
}

.container {
    max-width: 1000px;
    margin: 30px auto;
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
}

header h1 {
    text-align: center;
    color: rgb(0, 0, 130);
    margin-bottom: 20px;
}

.add-button {
    background: rgb(0, 0, 130);
    color: white;
    border: none;
    padding: 12px 24px;
    font-size: 16px;
    border-radius: 6px;
    cursor: pointer;
    float: right;
    margin-bottom: 20px;
}

.add-button:hover {
    background: rgb(0, 0, 100);
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table th, table td {
    border: 1px solid #ddd;
    padding: 12px 16px;
    text-align: center;
}

table th {
    background-color: rgb(0, 0, 130);
    color: white;
}

.action-link {
    color: grey;
    cursor: pointer;
    text-decoration: underline;
}

.action-link:hover {
    color: black;
}

.modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
    z-index: 999;
}

.modal-content {
    background: #fff;
    padding: 30px;
    width: 90%;
    max-width: 600px;
    border-radius: 10px;
    position: relative;
}

.modal-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-title h2 {
    margin: 0;
}

.close-btn {
    font-size: 24px;
    cursor: pointer;
    color: #555;
}

.save-btn {
        background: rgb(0, 0, 130);
        color: white;
        padding: 18px; /* Increased size by 1.5 times */
        font-size: 16px; /* Increased font size */
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }

    .save-btn:hover {
        background: rgb(0, 0, 100); /* Slightly darker shade for hover effect */
    }

.form-row {
    display: flex;
    gap: 20px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.input-group {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.input-group label {
    margin-bottom: 6px;
    font-weight: bold;
    color: #333;
}

.input-group input {
    padding: 10px;
    font-size: 15px;
    border: 1px solid #ccc;
    border-radius: 6px;
    width: 100%;
}

.button-group {
    margin-top: 30px;
    text-align: right;
}

.submit-btn {
    background: rgb(0, 0, 130);
    color: white;
    padding: 12px 24px;
    font-size: 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.submit-btn:hover {
    background: rgb(0, 0, 100);
}

    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Billing</h1>
        </header>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Wi-fi</th>
                        <th>Water</th>
                        <th>Electricity</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $billingResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['property']) ?></td>
                            <td><?= number_format($row['wifi'], 2) ?></td>
                            <td><?= number_format($row['water'], 2) ?></td>
                            <td><?= number_format($row['electricity'], 2) ?></td>
                            <td>
                                <span class="action-link" onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">Edit</span>
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
                <h2 id="modalTitle">Add Bill</h2>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="id" id="billId">
                <div class="input-group">
                    <label for="property">Property</label>
                    <input type="text" name="property" id="property" value="<?= htmlspecialchars($caretaker_property) ?>" readonly>
                </div>
                <div class="input-group">
                    <label for="wifi">Wi-fi</label>
                    <input type="number" step="0.01" name="wifi" id="wifi" required>
                </div>
                <div class="input-group">
                    <label for="water">Water</label>
                    <input type="number" step="0.01" name="water" id="water" required>
                </div>
                <div class="input-group">
                    <label for="electricity">Electricity</label>
                    <input type="number" step="0.01" name="electricity" id="electricity" required>
                </div>
                <div class="button-group">
                    <button type="submit" class="save-btn">Save Billing</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById("modalTitle").textContent = "Add Bill";
            document.getElementById("billId").value = "";
            document.getElementById("wifi").value = "";
            document.getElementById("water").value = "";
            document.getElementById("electricity").value = "";
            document.getElementById("modal").style.display = "flex";
        }

        function openEditModal(data) {
            document.getElementById("modalTitle").textContent = "Edit Bill";
            document.getElementById("billId").value = data.id;
            document.getElementById("wifi").value = data.wifi;
            document.getElementById("water").value = data.water;
            document.getElementById("electricity").value = data.electricity;
            document.getElementById("modal").style.display = "flex";
        }

        function closeModal() {
            document.getElementById("modal").style.display = "none";
        }
    </script>
</body>
</html>