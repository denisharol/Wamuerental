<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the session only if it's not already active
}

$mysqli = new mysqli("localhost", "root", "", "Demo1");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the caretaker's assigned property
$caretaker_id = $_SESSION['caretaker_id'] ?? null; // Assuming caretaker ID is stored in the session
if (!$caretaker_id) {
    die("Error: Caretaker ID not found in session.");
}

$property_query = $mysqli->prepare("SELECT property FROM caretaker WHERE id = ?");
$property_query->bind_param("i", $caretaker_id);
$property_query->execute();
$property_query->bind_result($caretaker_property);
$property_query->fetch();
$property_query->close();

// Fetch tenants assigned to the caretaker's property and count their unread messages
$query = "
    SELECT u.id, u.name,
        (SELECT COUNT(*) FROM messages m 
         WHERE m.sender_id = u.id AND m.receiver_id = ? AND m.is_read = 0) AS unread_count
    FROM users u 
    WHERE u.property = ? AND u.id != ? -- Exclude the caretaker
    ORDER BY unread_count DESC, u.name ASC
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("isi", $caretaker_id, $caretaker_property, $caretaker_id);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messaging</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800 font-sans h-screen overflow-hidden">

  <div class="flex h-full">
    <!-- Sidebar -->
    <aside class="w-1/3 border-r border-gray-200 bg-white p-4 overflow-y-auto">
      <h2 class="text-lg font-semibold mb-4">Send Message To</h2>

      <div class="space-y-2">
        <!-- Send to all tenants button -->
        <button class="w-full flex justify-between items-center px-3 py-2 border rounded text-left bg-yellow-100 hover:bg-yellow-200" data-user-id="-1">
          <span class="font-semibold text-yellow-900">Send to All Tenants</span>
        </button>

        <!-- Individual users -->
        <?php foreach ($users as $user): ?>
          <button class="w-full flex justify-between items-center px-3 py-2 border rounded text-left hover:bg-gray-100" data-user-id="<?= $user['id'] ?>">
            <span><?= htmlspecialchars($user['name']) ?></span>
            <?php if ($user['unread_count'] > 0): ?>
              <span class="bg-red-600 text-white text-xs font-semibold px-2 py-0.5 rounded-full"><?= $user['unread_count'] ?></span>
            <?php endif; ?>
          </button>
        <?php endforeach; ?>
      </div>
    </aside>

    <!-- Chat Area -->
    <main class="w-2/3 flex flex-col">
      <div id="chatMessages" class="flex-1 p-4 space-y-4 overflow-y-auto bg-gray-100">
        <div class="text-center text-gray-500">Select a user to view messages</div>
      </div>

      <!-- Message Form -->
      <div class="p-4 bg-white border-t">
        <form id="messageForm" method="POST" class="flex space-x-2">
          <input type="hidden" name="receiver_id" id="receiver_id" value="">
          <input type="text" name="message" id="messageInput" placeholder="Type your message..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring focus:border-blue-300" required />
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">Send</button>
        </form>
      </div>
    </main>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const chatMessages = document.getElementById('chatMessages');
      const messageForm = document.getElementById('messageForm');
      const messageInput = document.getElementById('messageInput');
      const receiverInput = document.getElementById('receiver_id');

      // Handle sidebar button clicks
      document.querySelectorAll('[data-user-id]').forEach(button => {
        button.addEventListener('click', function () {
          const userId = this.getAttribute('data-user-id');
          receiverInput.value = userId;

          // Fetch messages dynamically
          fetch(`fetch_messages.php?user_id=${userId}`)
            .then(response => response.text())
            .then(html => {
              chatMessages.innerHTML = html;
              chatMessages.scrollTop = chatMessages.scrollHeight; // Scroll to the newest message
            })
            .catch(error => {
              chatMessages.innerHTML = `<div class="text-center text-red-500">Error loading messages</div>`;
            });
        });
      });

      // Handle message form submission
      messageForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(messageForm);

        fetch('send_message.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.text())
          .then(() => {
            messageInput.value = ''; // Clear the input
            const userId = receiverInput.value;

            // Reload messages
            fetch(`fetch_messages.php?user_id=${userId}`)
              .then(response => response.text())
              .then(html => {
                chatMessages.innerHTML = html;
                chatMessages.scrollTop = chatMessages.scrollHeight; // Scroll to the newest message
              });
          })
          .catch(error => {
            console.error('Error sending message:', error);
          });
      });
    });
  </script>

</body>
</html>