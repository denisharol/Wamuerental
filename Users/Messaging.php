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

$user_id = $_SESSION['user_id'];
$admin_id = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message']);
    if (!empty($message)) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $admin_id, $message);
        $stmt->execute();
        $stmt->close();
    }
}

$stmt = $conn->prepare("
    SELECT sender_id, message, DATE_FORMAT(timestamp, '%Y-%m-%d') AS date, DATE_FORMAT(timestamp, '%h:%i %p') AS time 
    FROM messages 
    WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
    ORDER BY timestamp ASC
");
$stmt->bind_param("iiii", $user_id, $admin_id, $admin_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();

function formatDateLabel($date) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    if ($date === $today) {
        return "Today";
    } elseif ($date === $yesterday) {
        return "Yesterday";
    } else {
        return date('F j, Y', strtotime($date)); // Example: "March 15, 2025"
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tenant Messaging</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen p-6 font-sans">

  <div class="max-w-2xl mx-auto bg-white shadow-md rounded-lg overflow-hidden">
    <div class="bg-blue-600 text-white px-6 py-4">
      <h2 class="text-lg font-semibold">Message Admin</h2>
      <p class="text-sm text-blue-100">Ask a question, report a problem, or give feedback.</p>
    </div>

    <div class="p-6 space-y-4 h-[400px] overflow-y-auto" id="messageArea">
      <?php 
      $lastDate = null; // To track the last displayed date
      foreach ($messages as $msg): 
        if ($msg['date'] !== $lastDate): 
          $lastDate = $msg['date'];
      ?>
        <!-- Date Label -->
        <div class="text-center text-gray-500 text-sm my-2">
          <?= formatDateLabel($msg['date']) ?>
        </div>
      <?php endif; ?>

        <?php if ($msg['sender_id'] == $user_id): ?>
          <!-- Tenant Message -->
          <div class="flex justify-end">
            <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg max-w-xs">
              <p class="text-sm"><?= htmlspecialchars($msg['message']) ?></p>
              <span class="text-xs text-gray-400 block text-right mt-1"><?= $msg['time'] ?></span>
            </div>
          </div>
        <?php else: ?>
          <!-- Admin Message -->
          <div class="flex justify-start">
            <div class="bg-gray-100 text-gray-800 px-4 py-2 rounded-lg max-w-xs">
              <p class="text-sm"><?= htmlspecialchars($msg['message']) ?></p>
              <span class="text-xs text-gray-400 block mt-1"><?= $msg['time'] ?></span>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <div class="p-4 border-t border-gray-200">
      <form method="POST" class="flex gap-2">
        <input type="text" name="message" placeholder="Type your message..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400" required />
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
          Send
        </button>
      </form>
    </div>
  </div>

  <script>
    // Scroll to the newest message
    const messageArea = document.getElementById('messageArea');
    if (messageArea) {
      messageArea.scrollTop = messageArea.scrollHeight;
    }
  </script>

</body>
</html>