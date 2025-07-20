<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Units</title>
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

        /* Remove highlight and background from modal title */
        .modal-title {
            display: flex;
            align-items: center;
            justify-content: flex-start; /* Align to start */
            font-size: 1.3em;
            font-weight: bold;
            background: none;
            box-shadow: none;
            border: none;
            padding: 0 0 0 2px; /* Align left with textboxes */
            margin-bottom: 6px; /* Decrease space below title */
        }

        .close-btn {
            font-size: 1.5em;
            cursor: pointer;
            margin-left: auto;
        }

        .form-grid {
            margin-left: 2px; /* Align form with title */
        }

        .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 18px;
        }

        .button-group button {
            background: rgb(17, 17, 57);
            color: #fff;
            border: none;
            padding: 10px 28px; /* Increased width */
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            transition: background 0.2s;
        }

        .button-group button:hover {
            background: rgb(0,0,200);
        }
    </style>
</head>
<body>
<?php include 'auth_admin.php';
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = "";

$properties = $conn->query("SELECT property FROM properties ORDER BY property ASC");

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_unit'])) {
    $id = $_POST['id'] ?? null;
    $property = $_POST['property'];
    $unit = $_POST['unit'];
    $floor = $_POST['floor'];
    $status = $_POST['status'];
    $rent = $_POST['rent'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE units SET property = ?, unit = ?, floor = ?, status = ?, rent = ? WHERE id = ?");
        $stmt->bind_param("ssssdi", $property, $unit, $floor, $status, $rent, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO units (property, unit, floor, status, rent) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $property, $unit, $floor, $status, $rent);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM units WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Filter logic
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$sql = "SELECT * FROM units";
if ($filter && $filter !== 'All') {
    $stmt = $conn->prepare("SELECT * FROM units WHERE property = ? ORDER BY property ASC, unit ASC");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM units ORDER BY property ASC, unit ASC");
}

// Calculate total units and vacancies based on the filter
$totalUnits = $result->num_rows;
if ($filter && $filter !== 'All') {
    $vacanciesStmt = $conn->prepare("SELECT COUNT(*) AS vacant_count FROM units WHERE property = ? AND status = 'Vacant'");
    $vacanciesStmt->bind_param("s", $filter);
    $vacanciesStmt->execute();
    $vacanciesResult = $vacanciesStmt->get_result();
    $vacancies = $vacanciesResult->fetch_assoc()['vacant_count'] ?? 0;
    $vacanciesStmt->close();
} else {
    $vacancies = $conn->query("SELECT * FROM units WHERE status = 'Vacant'")->num_rows;
}
?>

<div class="container">
    <header>
        <h1>Units</h1>
    </header>

    <div class="filters">
        <h3>Filter Units</h3>
        <form method="get">
            <label for="property">Property</label>
            <select name="filter" onchange="this.form.submit()">
                <option value="All">All Properties</option>
                <?php while($p = $properties->fetch_assoc()): ?>
                    <option value="<?php echo $p['property']; ?>" <?php if($filter == $p['property']) echo 'selected'; ?>><?php echo $p['property']; ?></option>
                <?php endwhile; ?>
            </select>
        </form>
    </div>

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
                    <th>Property</th>
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
                    <td><?php echo $row['property']; ?></td>
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
        <form method="post" class="form-grid" id="unitForm">
            <input type="hidden" name="id" id="unitId">
            <?php if ($error): ?>
                <div class="error-box"><?php echo $error; ?></div>
            <?php endif; ?>
            <div class="input-group">
                <label>Property</label>
                <select name="property" id="propertyDropdown" required>
                    <option value="">Select a Property</option>
                    <?php
                    $properties->data_seek(0);
                    while($p = $properties->fetch_assoc()): ?>
                        <option value="<?php echo $p['property']; ?>"><?php echo $p['property']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
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
                <button type="button" onclick="closeModal()">Cancel</button>
                <button type="submit" name="save_unit">Save Unit</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById("modalTitle").textContent = "Add Unit";
    document.getElementById("unitId").value = "";
    document.getElementById("propertyDropdown").value = "";
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
    document.getElementById("propertyDropdown").value = data.property;
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