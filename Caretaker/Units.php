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

// Handle adding or updating units
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_unit'])) {
    $id = $_POST['id'] ?? null;
    $unit = $_POST['unit'];
    $floor = $_POST['floor'];
    $status = $_POST['status'];
    $rent = $_POST['rent'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE units SET unit = ?, floor = ?, status = ?, rent = ? WHERE id = ? AND property = ?");
        $stmt->bind_param("sssdis", $unit, $floor, $status, $rent, $id, $caretaker_property);
    } else {
        $stmt = $conn->prepare("INSERT INTO units (unit, floor, status, rent, property) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssds", $unit, $floor, $status, $rent, $caretaker_property);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle deleting units
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM units WHERE id = ? AND property = ?");
    $stmt->bind_param("is", $id, $caretaker_property);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch units for the caretaker's property
$stmt = $conn->prepare("SELECT * FROM units WHERE property = ?");
$stmt->bind_param("s", $caretaker_property);
$stmt->execute();
$result = $stmt->get_result();
$totalUnits = $result->num_rows;
$vacancies = $conn->query("SELECT COUNT(*) AS vacant_count FROM units WHERE property = '$caretaker_property' AND status = 'Vacant'")->fetch_assoc()['vacant_count'] ?? 0;
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Units</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="styles.css">
<style>
    body {
        font-family: Arial, sans-serif;
        background: #f4f6f8;
        margin: 0;
        padding: 0;
    }

    .container {
        padding: 20px;
        max-width: 1200px;
        margin: auto;
    }

    header h1 {
        margin-bottom: 20px;
        color: rgb(0,0,130);
    }

    .summary {
        background: white;
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }

    .summary h2 {
        margin-top: 0;
        color: rgb(0,0,130);
    }

    .summary p {
        font-size: 16px;
        margin: 8px 0;
    }

    .add-button {
        background-color: rgb(0,0,130);
        color: white;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
        margin-bottom: 20px;
        float: right;
    }

    .add-button:hover {
        background-color: rgb(0,0,100);
    }

    .table-container table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
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

    /* Modal Styling */
    .modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }

    .modal-content {
        background: white;
        width: 90%;
        max-width: 600px;
        padding: 30px;
        border-radius: 12px;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 20px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    }

    .modal-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 20px;
        font-weight: bold;
        color: rgb(0,0,130);
    }

    .close-btn {
        font-size: 28px;
        cursor: pointer;
        color: grey;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .input-group {
        display: flex;
        flex-direction: column;
    }

    .input-group label {
        font-size: 14px;
        margin-bottom: 5px;
        color: #555;
    }

    .input-group input,
    .input-group select {
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        width: 100%;
    }

    .button-group {
        grid-column: 1 / span 2;
        display: flex;
        justify-content: center;
    }

    .button-group button {
        background-color: rgb(0,0,130);
        color: white;
        border: none;
        padding: 12px 24px;
        font-size: 16px;
        border-radius: 6px;
        cursor: pointer;
    }

    .button-group button:hover {
        background-color: rgb(0,0,100);
    }

    @media (max-width: 600px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

</head>
<body>
<div class="container">
    <header>
        <h1>Units</h1>
    </header>

    <div class="summary">
        <h2>Summary</h2>
        <p>Total Units: <?php echo $totalUnits; ?></p>
        <p>Vacancies: <?php echo $vacancies; ?></p>
    </div>

    <button class="add-button" onclick="openAddModal()">Add Unit</button>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Floor</th>
                    <th>Status</th>
                    <th>Rent</th>
                    <th>Options</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['unit']; ?></td>
                    <td><?php echo $row['floor']; ?></td>
                    <td><?php echo $row['status']; ?></td>
                    <td><?php echo $row['rent']; ?></td>
                    <td>
                        <span class="action-link" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</span>
                        |
                        <span class="action-link" onclick="deleteUnit(<?php echo $row['id']; ?>)">Delete</span>
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
            <span id="modalTitle">Add Unit</span>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form method="post" class="form-grid">
            <input type="hidden" name="id" id="unitId">
            <div class="input-group">
                <label>Unit</label>
                <input type="text" name="unit" id="unitName" required>
            </div>
            <div class="input-group">
                <label>Floor</label>
                <select name="floor" id="floorDropdown" required>
                    <option value="Ground Floor">Ground Floor</option>
                    <option value="1st Floor">1st Floor</option>
                    <option value="2nd Floor">2nd Floor</option>
                    <option value="3rd Floor">3rd Floor</option>
                </select>
            </div>
            <div class="input-group">
                <label>Status</label>
                <select name="status" id="unitStatus" required>
                    <option value="Occupied">Occupied</option>
                    <option value="Vacant">Vacant</option>
                </select>
            </div>
            <div class="input-group">
                <label>Rent</label>
                <input type="number" step="0.01" name="rent" id="unitRent" required>
            </div>
            <div class="button-group">
                <button type="submit" name="save_unit">Save Unit</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById("modalTitle").textContent = "Add Unit";
    document.getElementById("unitId").value = "";
    document.getElementById("unitName").value = "";
    document.getElementById("floorDropdown").value = "Ground Floor";
    document.getElementById("unitStatus").value = "Vacant";
    document.getElementById("unitRent").value = "";
    document.getElementById("modal").style.display = "flex";
    document.body.style.overflow = "hidden";
}

function openEditModal(data) {
    document.getElementById("modalTitle").textContent = "Edit Unit";
    document.getElementById("unitId").value = data.id;
    document.getElementById("unitName").value = data.unit;
    document.getElementById("floorDropdown").value = data.floor;
    document.getElementById("unitStatus").value = data.status;
    document.getElementById("unitRent").value = data.rent;
    document.getElementById("modal").style.display = "flex";
    document.body.style.overflow = "hidden";
}

function closeModal() {
    document.getElementById("modal").style.display = "none";
    document.body.style.overflow = "auto";
}

function deleteUnit(id) {
    if (confirm("Are you sure you want to delete this unit?")) {
        window.location.href = `?delete=${id}`;
    }
}
</script>
</body>
</html>