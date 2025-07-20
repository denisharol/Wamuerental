<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = $error = "";

// Fetch caretaker's property
$caretaker_id = $_SESSION['caretaker_id'];
$stmt = $conn->prepare("SELECT property FROM caretaker WHERE id = ?");
$stmt->bind_param("i", $caretaker_id);
$stmt->execute();
$stmt->bind_result($caretaker_property);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $idNumber = $_POST['idNumber'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $unit = $_POST['unit'];
    $moveInDate = $_POST['moveInDate'];
    $rentAmount = $_POST['rentAmount'];
    $securityDeposit = $_POST['securityDeposit'];

    // Check if the unit is already assigned
    $checkUnit = $conn->prepare("SELECT * FROM users WHERE property = ? AND unit = ?");
    $checkUnit->bind_param("ss", $caretaker_property, $unit);
    $checkUnit->execute();
    $result = $checkUnit->get_result();

    if ($result->num_rows > 0) {
        $error = "This unit is already assigned to another tenant.";
    } else {
        // Insert tenant into the database
        $stmt = $conn->prepare("INSERT INTO users (name, id_number, phone, email, password, unit, property, move_in_date, rent_amount, security_deposit) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssdd", $name, $idNumber, $phone, $email, $password, $unit, $caretaker_property, $moveInDate, $rentAmount, $securityDeposit);

        if ($stmt->execute()) {
            // Update unit status to "Occupied"
            $updateUnit = $conn->prepare("UPDATE units SET status = 'Occupied' WHERE property = ? AND unit = ?");
            $updateUnit->bind_param("ss", $caretaker_property, $unit);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Tenant Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">

<div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-4xl">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Create Tenant Account</h2>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
    const unitDropdown = document.getElementById("unitDropdown");
    const rentAmountField = document.getElementById("rentAmount");
    const securityDepositField = document.getElementById("securityDeposit");

    // Fetch units for the caretaker's property
    fetch(`get_units.php?property=<?php echo urlencode($caretaker_property); ?>`)
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

    unitDropdown.addEventListener("change", function() {
        const selectedUnit = unitDropdown.value;

        if (selectedUnit) {
            fetch(`get_units.php?property=<?php echo urlencode($caretaker_property); ?>&unit=${selectedUnit}`)
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