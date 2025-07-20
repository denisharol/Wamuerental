<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .disabled {
            background-color: #e5e7eb;
            color: #9ca3af;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans text-gray-800">

<?php
session_start();
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$error = "";

$userId = $_SESSION['user_id'] ?? 1;
$userQuery = $conn->prepare("SELECT property, unit, security_deposit FROM users WHERE id = ?");
$userQuery->bind_param("i", $userId);
$userQuery->execute();
$userResult = $userQuery->get_result();
$userData = $userResult->fetch_assoc();
$property = $userData['property'];
$unit = $userData['unit'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['save_maintenance'])) {
    $id = $_POST['id'] ?? null;
    $shortsummary = $_POST['shortsummary'] ?? null;
    $date = $_POST['date'] ?? null;
    $paymentMethod = $_POST['payment_method'] ?? null;

    if ($id) {
        $stmt = $conn->prepare("UPDATE maintenance SET shortsummary = ?, date = ?, payment_method = ? WHERE id = ?");
        $stmt->bind_param("sssi", $shortsummary, $date, $paymentMethod, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO maintenance (shortsummary, property, unit, date, payment_method) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $shortsummary, $property, $unit, $date, $paymentMethod);
    }
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$result = $conn->query("SELECT * FROM maintenance WHERE property = '$property' AND unit = '$unit'");
if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<div class="max-w-6xl mx-auto p-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-lg font-bold text-left">Maintenance</h1>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
            Request Maintenance
        </button>
    </div>
    <div class="overflow-x-auto bg-white rounded shadow">
        <table class="min-w-full text-sm table-auto border-collapse">
            <thead class="bg-gray-200 text-gray-800 uppercase text-xs">
                <tr>
                    <th class="px-4 py-2 text-left">Summary</th>
                    <th class="px-4 py-2 text-left">Property</th>
                    <th class="px-4 py-2 text-left">Unit</th>
                    <th class="px-4 py-2 text-left">Date</th>
                    <th class="px-4 py-2 text-left">Payment Method</th>
                    <th class="px-4 py-2 text-left">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr class="border-t hover:bg-gray-50">
                    <td class="px-4 py-2"><?php echo $row['shortsummary']; ?></td>
                    <td class="px-4 py-2"><?php echo $row['property']; ?></td>
                    <td class="px-4 py-2"><?php echo $row['unit']; ?></td>
                    <td class="px-4 py-2"><?php echo $row['date']; ?></td>
                    <td class="px-4 py-2"><?php echo $row['payment_method']; ?></td>
                    <td class="px-4 py-2 text-blue-500">
                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg w-full max-w-lg p-6 shadow-lg relative">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-left">Request Maintenance</h2>
            <button onclick="closeModal()" class="text-2xl leading-none">&times;</button>
        </div>
        <form method="post" class="grid gap-4">
            <input type="hidden" name="id" id="maintenanceId">
            <?php if ($error): ?>
                <div class="bg-red-100 text-red-700 p-3 rounded"><?php echo $error; ?></div>
            <?php endif; ?>
            <div>
                <label class="font-semibold">Short Summary</label>
                <select name="shortsummary" id="shortsummary" class="w-full border px-3 py-2 rounded">
                    <option value="">Select a Summary</option>
                    <option value="Electricals">Electricals</option>
                    <option value="Plumbing">Plumbing</option>
                    <option value="Painting">Painting</option>
                    <option value="Woodwork">Woodwork</option>
                    <option value="General">General</option>
                </select>
            </div>
            <div>
                <label class="font-semibold">Property Name</label>
                <input type="text" value="<?php echo $property; ?>" readonly class="w-full border px-3 py-2 rounded bg-gray-100 text-gray-500">
            </div>
            <div>
                <label class="font-semibold">Unit</label>
                <input type="text" value="<?php echo $unit; ?>" readonly class="w-full border px-3 py-2 rounded bg-gray-100 text-gray-500">
            </div>
            <div>
                <label class="font-semibold">Date</label>
                <input type="date" name="date" id="date" class="w-full border px-3 py-2 rounded">
            </div>
            <div>
                <label class="font-semibold">Payment Method</label>
                <select name="payment_method" id="paymentMethod" class="w-full border px-3 py-2 rounded disabled">
                    <option value="">Select a Payment Method</option>
                    <option value="Security Deposit">Security Deposit</option>
                </select>
            </div>
            <div class="text-right">
                <button type="submit" name="save_maintenance" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded font-semibold">
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById("modal").classList.remove("hidden");
        document.body.classList.add("overflow-hidden");
        document.getElementById("maintenanceId").value = "";
        document.getElementById("shortsummary").value = "";
        document.getElementById("date").value = "";
        document.getElementById("paymentMethod").classList.add("disabled");
        document.getElementById("paymentMethod").value = "";
    }

    function closeModal() {
        document.getElementById("modal").classList.add("hidden");
        document.body.classList.remove("overflow-hidden");
    }

    function openEditModal(data) {
        openModal();
        document.getElementById("maintenanceId").value = data.id;
        document.getElementById("shortsummary").value = data.shortsummary;
        document.getElementById("date").value = data.date;
        document.getElementById("paymentMethod").classList.remove("disabled");
        document.getElementById("paymentMethod").value = data.payment_method;
    }
</script>

</body>
</html>