<?php
session_start();

// Connect to the database
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Check if the user's notice has already been accepted
$checkNoticeStmt = $conn->prepare("SELECT id FROM approved_vacating_notices WHERE tenant_id = ?");
$checkNoticeStmt->bind_param("i", $user_id);
$checkNoticeStmt->execute();
$checkNoticeStmt->store_result();
$hasApprovedNotice = $checkNoticeStmt->num_rows > 0;
$checkNoticeStmt->close();

// Fetch tenant details
$stmt = $conn->prepare("SELECT name, unit FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($tenant_name, $house_number);
$stmt->fetch();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$hasApprovedNotice) {
    $date_of_notice = $_POST['date_of_notice'];
    $planned_exit_date = $_POST['planned_exit_date'];
    $reason = $_POST['reason'];

    $stmt = $conn->prepare("INSERT INTO vacating_notices (tenant_id, tenant_name, house_number, date_of_notice, planned_exit_date, reason) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $user_id, $tenant_name, $house_number, $date_of_notice, $planned_exit_date, $reason);

    if ($stmt->execute()) {
        $success_message = "Vacation notice submitted successfully!";
    } else {
        $error_message = "Failed to submit vacation notice. Please try again.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Vacation Notice</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-6 font-sans">

  <div class="max-w-xl mx-auto bg-white p-8 rounded-lg shadow-md border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-4">Vacation Notice</h2>

    <?php if ($hasApprovedNotice): ?>
      <div class="bg-blue-100 text-blue-700 p-3 rounded mb-4">
        Your vacation notice has already been sent and accepted. You cannot submit another notice.
      </div>
    <?php else: ?>
      <p class="text-sm text-gray-600 mb-6">Please fill out the form to notify management of your intent to vacate your premises.</p>

      <?php if (!empty($success_message)): ?>
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
          <?= htmlspecialchars($success_message) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error_message)): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-5">
        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Tenant Name</label>
          <input type="text" value="<?= htmlspecialchars($tenant_name) ?>" class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-100 text-gray-500" readonly />
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">House Number</label>
          <input type="text" value="<?= htmlspecialchars($house_number) ?>" class="w-full px-4 py-2 border border-gray-300 rounded bg-gray-100 text-gray-500" readonly />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Date of Notice</label>
            <input type="date" name="date_of_notice" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-400" required />
          </div>
          <div>
            <label class="block mb-1 text-sm font-medium text-gray-700">Planned Exit Date</label>
            <input type="date" name="planned_exit_date" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-400" required />
          </div>
        </div>

        <div>
          <label class="block mb-1 text-sm font-medium text-gray-700">Reason for Leaving</label>
          <textarea name="reason" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-400" required></textarea>
        </div>

        <div>
          <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition">
            Submit Vacation Notice
          </button>
        </div>
      </form>
    <?php endif; ?>
  </div>

</body>
</html>