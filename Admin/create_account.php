<?php include_once 'auth_admin.php';
include_once 'log_action.php';
$conn = new mysqli("localhost", "root", "", "demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = $error = "";

// Fetch properties for dropdown
$properties = $conn->query("SELECT DISTINCT property FROM properties");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $idNumber = $_POST['idNumber'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $property = $_POST['property'];
    $unit = $_POST['unit'];
    $moveInDate = $_POST['moveInDate'];
    $rentAmount = $_POST['rentAmount'];
    $securityDeposit = $_POST['securityDeposit'];

    $checkUnit = $conn->prepare("SELECT * FROM users WHERE property = ? AND unit = ?");
    $checkUnit->bind_param("ss", $property, $unit);
    $checkUnit->execute();
    $result = $checkUnit->get_result();

    if ($result->num_rows > 0) {
        $error = "This unit is already assigned to another tenant.";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (name, id_number, phone, email, password, unit, property, move_in_date, rent_amount, security_deposit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssdd", $name, $idNumber, $phone, $email, $password, $unit, $property, $moveInDate, $rentAmount, $securityDeposit);

        if ($stmt->execute()) {
            $updateUnit = $conn->prepare("UPDATE units SET status = 'Occupied' WHERE property = ? AND unit = ?");
            $updateUnit->bind_param("ss", $property, $unit);
            $updateUnit->execute();
            $updateUnit->close();

            $success = "Account created successfully!";
        } else {
            $error = "Failed to create account. Please try again.";
        }

        $stmt->close();
    }
    $checkUnit->close();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    $getUnit = $conn->prepare("SELECT property, unit FROM users WHERE id = ?");
    $getUnit->bind_param("i", $id);
    $getUnit->execute();
    $unitResult = $getUnit->get_result();
    $unitData = $unitResult->fetch_assoc();
    $getUnit->close();

    if ($unitData) {
        $property = $unitData['property'];
        $unit = $unitData['unit'];

        $deleteAccount = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteAccount->bind_param("i", $id);
        if ($deleteAccount->execute()) {
            $updateUnit = $conn->prepare("UPDATE units SET status = 'Vacant' WHERE property = ? AND unit = ?");
            $updateUnit->bind_param("ss", $property, $unit);
            $updateUnit->execute();
            $updateUnit->close();

            $success = "Account deleted successfully!";
        } else {
            $error = "Failed to delete account.";
        }
        $deleteAccount->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Creation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .disabled-option {
            color: gray;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">

<div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-4xl">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Account Creation</h2>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <!-- Form Rows - Side by Side -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="name" id="name" class="mt-1 block w-full p-2 border rounded-lg" required>
            </div>

            <div>
                <label for="idNumber" class="block text-sm font-medium text-gray-700">ID Number</label>
                <input type="text" name="idNumber" id="idNumber" class="mt-1 block w-full p-2 border rounded-lg" required>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <div class="flex items-center border rounded-lg overflow-hidden">
                    <span class="bg-gray-200 px-3 py-2 text-gray-600">(+254)0</span>
                    <input type="text" name="phone" id="phone" maxlength="9" class="flex-1 p-2" required>
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" name="email" id="email" class="mt-1 block w-full p-2 border rounded-lg" required>
            </div>
        </div>

        <!-- Form Rows - Side by Side for Property, Unit, Date -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="property" class="block text-sm font-medium text-gray-700">Property</label>
                <select name="property" id="propertyDropdown" class="mt-1 block w-full p-2 border rounded-lg" required>
                    <option value="">Select a Property</option>
                    <?php while ($p = $properties->fetch_assoc()): ?>
                        <option value="<?php echo $p['property']; ?>"><?php echo $p['property']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div>
                <label for="unit" class="block text-sm font-medium text-gray-700">Unit</label>
                <select name="unit" id="unitDropdown" class="mt-1 block w-full p-2 border rounded-lg" required>
                    <option value="">Select a Unit</option>
                </select>
            </div>

            <div>
                <label for="moveInDate" class="block text-sm font-medium text-gray-700">Move-in Date</label>
                <input type="date" name="moveInDate" id="moveInDate" class="mt-1 block w-full p-2 border rounded-lg" required>
            </div>
        </div>

        <!-- Rent Amount & Security Deposit Side by Side -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="rentAmount" class="block text-sm font-medium text-gray-700">Rent Amount</label>
                <input type="text" name="rentAmount" id="rentAmount" class="mt-1 block w-full p-2 border rounded-lg bg-gray-100 text-gray-500" readonly>
            </div>

            <div>
                <label for="securityDeposit" class="block text-sm font-medium text-gray-700">Security Deposit</label>
                <input type="text" name="securityDeposit" id="securityDeposit" class="mt-1 block w-full p-2 border rounded-lg bg-gray-100 text-gray-500" readonly>
            </div>
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" name="password" id="password" class="mt-1 block w-full p-2 border rounded-lg" required>
            <p class="text-sm text-gray-500 mt-1">Password must be at least 8 characters long.</p>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white py-2 px-4 rounded-lg">Create Account</button>
        </div>
    </form>
</div>

<script>
    const propertyDropdown = document.getElementById("propertyDropdown");
    const unitDropdown = document.getElementById("unitDropdown");
    const rentAmountField = document.getElementById("rentAmount");
    const securityDepositField = document.getElementById("securityDeposit");

    propertyDropdown.addEventListener("change", function() {
        const selectedProperty = propertyDropdown.value;

        if (selectedProperty) {
            fetch(`get_units.php?property=${selectedProperty}`)
                .then(response => response.json())
                .then(data => {
                    unitDropdown.innerHTML = "<option value=''>Select a Unit</option>";
                    data.forEach(unit => {
                        const option = document.createElement("option");
                        option.value = unit.unit;
                        option.textContent = unit.unit + (unit.assigned ? " (Occupied)" : "");
                        option.disabled = unit.assigned; // Disable occupied units
                        unitDropdown.appendChild(option);
                    });
                });
        } else {
            unitDropdown.innerHTML = "<option value=''>Select a Unit</option>";
            rentAmountField.value = "";
            securityDepositField.value = "";
        }
    });

    unitDropdown.addEventListener("change", function() {
        const selectedUnit = unitDropdown.value;
        const selectedProperty = propertyDropdown.value;

        if (selectedUnit && selectedProperty) {
            fetch(`get_units.php?property=${selectedProperty}&unit=${selectedUnit}`)
                .then(response => response.json())
                .then(data => {
                    if (data.rent) {
                        rentAmountField.value = data.rent;
                        securityDepositField.value = data.rent; // Security deposit equals rent
                    } else {
                        rentAmountField.value = "";
                        securityDepositField.value = "";
                    }
                });
        } else {
            rentAmountField.value = "";
            securityDepositField.value = "";
        }
    });
</script>

</body>
</html>