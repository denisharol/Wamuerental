<?php include 'auth_admin.php';

$mysqli = new mysqli("localhost", "root", "", "Demo1");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Fetch all users (excluding admin and caretaker) and count their unread messages to admin
$query = "
    SELECT u.id, u.name,
        (SELECT COUNT(*) FROM messages m 
         WHERE m.sender_id = u.id AND m.receiver_id = 1 AND m.is_read = 0) AS unread_count
    FROM users u 
    WHERE u.id != 1 AND u.id NOT IN (SELECT id FROM caretaker)
    ORDER BY unread_count DESC, u.name ASC
";

$result = $mysqli->query($query);
$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$selected_user_id = $_GET['user_id'] ?? null;
$messages = [];
$admin_id = 1;

// If "Send to all tenants" selected, don't load messages
if ($selected_user_id && $selected_user_id != -1) {
    // Mark messages as read
    $mysqli->query("UPDATE messages SET is_read = 1 WHERE sender_id = $selected_user_id AND receiver_id = $admin_id");

    // Fetch conversation
    $stmt = $mysqli->prepare("
        SELECT sender_id, message, DATE_FORMAT(timestamp, '%h:%i %p') AS time 
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY timestamp ASC
    ");
    $stmt->bind_param("iiii", $selected_user_id, $admin_id, $admin_id, $selected_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Messaging</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans h-screen overflow-hidden">

  <div class="flex h-full">
    <!-- Sidebar -->
    <aside class="w-1/3 border-r border-gray-200 bg-white p-4 overflow-y-auto">
      <h2 class="text-lg font-semibold mb-4">Send Message To</h2>

      <div class="space-y-2">
        <!-- Send to all tenants button -->
        <form method="GET" class="relative block">
          <input type="hidden" name="user_id" value="-1" />
          <button type="submit" class="w-full flex justify-between items-center px-3 py-2 border rounded text-left bg-yellow-100 hover:bg-yellow-200 <?= $selected_user_id == -1 ? 'border-yellow-500' : '' ?>">
            <span class="font-semibold text-yellow-900">Send to All Tenants</span>
          </button>
        </form>

        <!-- Individual users -->
        <?php foreach ($users as $user): ?>
          <form method="GET" class="relative block" style="margin: 0;">
            <input type="hidden" name="user_id" value="<?= $user['id'] ?>" />
            <button type="submit" class="w-full flex justify-between items-center px-3 py-2 border rounded text-left hover:bg-gray-100 <?= $selected_user_id == $user['id'] ? 'bg-blue-50 border-blue-500' : '' ?>">
              <span><?= htmlspecialchars($user['name']) ?></span>
              <?php if ($user['unread_count'] > 0): ?>
                <span class="bg-red-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full"><?= $user['unread_count'] ?></span>
              <?php endif; ?>
            </button>
          </form>
        <?php endforeach; ?>
      </div>
    </aside>

    <!-- Chat Area -->
    <main class="w-2/3 flex flex-col">
      <div class="p-4 border-b bg-white">
        <h2 id="chatTenantName" class="font-semibold text-lg">
          <?php 
            if (!$selected_user_id) echo "No User Selected";
            elseif ($selected_user_id == -1) echo "Broadcast to All Tenants";
            else echo htmlspecialchars($users[array_search($selected_user_id, array_column($users, 'id'))]['name']);
          ?>
        </h2>
      </div>

      <div id="chatMessages" class="flex-1 p-4 space-y-4 overflow-y-auto bg-gray-100">
        <?php if (!$selected_user_id): ?>
          <div class="text-center text-gray-500">Select a user to view messages</div>
        <?php elseif ($selected_user_id == -1): ?>
          <div class="text-center text-gray-500 italic">Your message will be sent to <strong>all tenants</strong></div>
        <?php else: ?>
          <?php foreach ($messages as $msg): ?>
            <?php if ($msg['sender_id'] == $admin_id): ?>
              <div class="flex justify-end">
                <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg max-w-xs">
                  <p class="text-sm"><?= htmlspecialchars($msg['message']) ?></p>
                  <span class="text-xs text-gray-400 block text-right mt-1"><?= $msg['time'] ?></span>
                </div>
              </div>
            <?php else: ?>
              <div class="flex justify-start">
                <div class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg max-w-xs">
                  <p class="text-sm"><?= htmlspecialchars($msg['message']) ?></p>
                  <span class="text-xs text-gray-400 block mt-1"><?= $msg['time'] ?></span>
                </div>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Message Form -->
      <?php if ($selected_user_id): ?>
        <div class="p-4 bg-white border-t">
          <form method="POST" action="send_message.php" class="flex space-x-2">
            <input type="hidden" name="receiver_id" value="<?= $selected_user_id ?>">
            <input type="text" name="message" placeholder="Type your message..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:border-blue-300" required />
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Send</button>
          </form>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <script>
    const chatMessages = document.getElementById('chatMessages');
    if (chatMessages) {
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }
  </script>

</body>
</html>