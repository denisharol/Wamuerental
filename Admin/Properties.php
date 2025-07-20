<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties</title>
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
            z-index: 1000;
        }

        .modal-content {
            background: #fff;
            width: 95%;
            max-width: 600px;
            padding: 25px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 1.4rem;
            font-weight: bold;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .close-btn {
            cursor: pointer;
            font-size: 24px;
            color: #999;
        }

        .close-btn:hover {
            color: #333;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #444;
        }

        .input-group input {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
        }

        .readonly-input {
            background-color: #f0f0f0;
            color: #888;
            pointer-events: none;
        }

        .button-group {
            text-align: right;
        }

        .button-group button {
            background-color: #2d89ef;
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .button-group button:hover {
            background-color: #226ac1;
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

// Handle form submission for adding or updating properties
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_property'])) {
    $id = $_POST['id'] ?? null;
    $property = $_POST['property'];
    $numberofunits = $_POST['numberofunits'];
    $city = $_POST['city'];

    if ($id) {
        $stmt = $conn->prepare("UPDATE properties SET property = ?, numberofunits = ?, city = ? WHERE id = ?");
        $stmt->bind_param("sssi", $property, $numberofunits, $city, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO properties (property, numberofunits, city) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $property, $numberofunits, $city);
    }
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle property deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Filter logic
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';
$sql = "SELECT p.*, b.wifi, b.water, b.electricity FROM properties p LEFT JOIN billing b ON p.property = b.property";
if ($filter && $filter !== 'All') {
    $stmt = $conn->prepare($sql . " WHERE p.property = ?");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

// Fetch distinct properties for the filter dropdown
$properties = $conn->query("SELECT DISTINCT property FROM properties");

// Calculate total units
$totalUnits = 0;
while ($row = $result->fetch_assoc()) {
    $totalUnits += $row['numberofunits'];
}
$result->data_seek(0); // Reset result pointer for table rendering
?>
    <div class="container">
        <header>
            <h1 class="title">Properties</h1>
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

        <section class="summary">
            <h2>Summary</h2>
            <p>Total Properties: <?php echo $result->num_rows; ?></p>
            <p>Total Units: <?php echo $totalUnits; ?></p>
        </section>

        <div class="buttons">
            <button class="add-button" onclick="openModal()">Add Property</button>
        </div>

        <section class="properties">
            <h2>Properties</h2>
            <table>
                <thead>
                    <tr>
                        <th>Property Name</th>
                        <th>Number of Units</th>
                        <th>City</th>
                        <th>Water Rate (KES)</th>
                        <th>Electricity Rate (KES)</th>
                        <th>Wi-fi Rate (KES)</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['property']; ?></td>
                        <td><?php echo $row['numberofunits']; ?></td>
                        <td><?php echo $row['city']; ?></td>
                        <td><?php echo $row['water']; ?></td>
                        <td><?php echo $row['electricity']; ?></td>
                        <td><?php echo $row['wifi']; ?></td>
                        <td>
                            <span class="action-link" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</span>
                            |
                            <span class="action-link" onclick="deleteProperty(<?php echo $row['id']; ?>)">Delete</span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </div>
    <div id="modal" class="modal">
        <div class="modal-content">
            <div class="modal-title">
                <span id="modalTitle">Add Property</span>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form method="post" class="form-grid">
                <input type="hidden" name="id" id="propertyId">
                <?php if ($error): ?>
                    <div class="error-box"><?php echo $error; ?></div>
                <?php endif; ?>
                <div class="input-group">
                    <label>Property</label>
                    <input type="text" name="property" id="propertyName" required>
                </div>
                <div class="input-group">
                    <label>Number of Units</label>
                    <input type="text" name="numberofunits" id="numberOfUnits" required>
                </div>
                <div class="input-group">
                    <label>City</label>
                    <input type="text" name="city" id="city" required>
                </div>
                <div class="input-group">
                    <label>Water Rate (KES)</label>
                    <input type="number" step="0.01" id="waterRate" class="readonly-input" readonly>
                </div>
                <div class="input-group">
                    <label>Electricity Rate (KES)</label>
                    <input type="number" step="0.01" id="electricityRate" class="readonly-input" readonly>
                </div>
                <div class="input-group">
                    <label>Wi-fi Rate (KES)</label>
                    <input type="number" step="0.01" id="wifiRate" class="readonly-input" readonly>
                </div>
                <div class="button-group">
                    <button type="submit" name="save_property">Save Property</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById("modalTitle").textContent = "Add Property";
            document.getElementById("propertyId").value = "";
            document.getElementById("propertyName").value = "";
            document.getElementById("numberOfUnits").value = "";
            document.getElementById("city").value = "";
            document.getElementById("waterRate").value = "";
            document.getElementById("electricityRate").value = "";
            document.getElementById("wifiRate").value = "";
            document.getElementById("modal").style.display = "flex";
            document.body.style.overflow = "hidden";
        }

        function closeModal() {
            document.getElementById("modal").style.display = "none";
            document.body.style.overflow = "auto";
        }

        function deleteProperty(id) {
            if (confirm("Are you sure you want to delete this property?")) {
                window.location.href = `?delete=${id}`;
            }
        }

        function openEditModal(data) {
            document.getElementById("modalTitle").textContent = "Edit Property";
            document.getElementById("propertyId").value = data.id;
            document.getElementById("propertyName").value = data.property;
            document.getElementById("numberOfUnits").value = data.numberofunits;
            document.getElementById("city").value = data.city;
            document.getElementById("waterRate").value = data.water;
            document.getElementById("electricityRate").value = data.electricity;
            document.getElementById("wifiRate").value = data.wifi;
            document.getElementById("modal").style.display = "flex";
            document.body.style.overflow = "hidden";
        }
    </script>
</body>
</html>
