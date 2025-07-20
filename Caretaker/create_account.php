<?php
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

    // Insert caretaker into the database
    $stmt = $conn->prepare("INSERT INTO caretaker (name, id_number, phone, email, password, property) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $name, $idNumber, $phone, $email, $password, $property);

    if ($stmt->execute()) {
        $success = "Caretaker account created successfully!";
    } else {
        $error = "Failed to create caretaker account. Please try again.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Caretaker Account</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">

<div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-4xl">
    <div class="text-center mb-6">
        <h2 class="text-2xl font-semibold text-gray-700">Create Caretaker Account</h2>
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

        <div>
            <label for="property" class="block text-sm font-medium text-gray-700">Property</label>
            <select name="property" id="property" class="mt-1 block w-full p-2 border rounded-lg" required>
                <option value="">Select a Property</option>
                <?php while ($p = $properties->fetch_assoc()): ?>
                    <option value="<?php echo $p['property']; ?>"><?php echo $p['property']; ?></option>
                <?php endwhile; ?>
            </select>
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

</body>
</html>