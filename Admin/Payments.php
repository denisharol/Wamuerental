<?php include 'auth_admin.php';
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch properties for the dropdown
$propertiesQuery = "SELECT property FROM properties";
$propertiesResult = $conn->query($propertiesQuery);

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

// Fetch all billing records
$billingQuery = "SELECT * FROM billing";
$billingResult = $conn->query($billingQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            width: 90%;
            max-width: 600px;
            padding: 20px;
            border-radius: 8px;
        }

        .action-link {
            color: grey;
            text-decoration: underline;
            cursor: pointer;
        }

        .action-link:hover {
            color: darkgrey;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Billing</h1>
        </header>

        <button class="add-button" onclick="openModal()">Add Bill</button>

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
                                |
                                <span class="action-link" onclick="deleteBill(<?= $row['id'] ?>)">Delete</span>
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
                    <select name="property" id="property" required>
                        <option value="">Select a Property</option>
                        <?php while ($property = $propertiesResult->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($property['property']) ?>"><?= htmlspecialchars($property['property']) ?></option>
                        <?php endwhile; ?>
                    </select>
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
                    <button type="submit">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById("modalTitle").textContent = "Add Bill";
            document.getElementById("billId").value = "";
            document.getElementById("property").value = "";
            document.getElementById("wifi").value = "";
            document.getElementById("water").value = "";
            document.getElementById("electricity").value = "";
            document.getElementById("modal").style.display = "flex";
        }

        function openEditModal(data) {
            document.getElementById("modalTitle").textContent = "Edit Bill";
            document.getElementById("billId").value = data.id;
            document.getElementById("property").value = data.property;
            document.getElementById("wifi").value = data.wifi;
            document.getElementById("water").value = data.water;
            document.getElementById("electricity").value = data.electricity;
            document.getElementById("modal").style.display = "flex";
        }

        function closeModal() {
            document.getElementById("modal").style.display = "none";
        }

        function deleteBill(id) {
            if (confirm("Are you sure you want to delete this bill?")) {
                window.location.href = `?delete=${id}`;
            }
        }
    </script>
</body>
</html>