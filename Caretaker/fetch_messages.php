<?php
session_start();
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$caretaker_id = $_SESSION['caretaker_id'] ?? null;
$user_id = intval($_GET['user_id'] ?? 0);

if (!$caretaker_id) {
    die("Error: Caretaker ID not found in session.");
}

$messages = [];
if ($user_id === -1) {
    // Broadcast to all tenants (no specific messages to load)
    echo '<div class="text-center text-gray-500 italic">Your message will be sent to <strong>all tenants</strong></div>';
} else {
    // Fetch conversation with a specific tenant
    $stmt = $conn->prepare("
        SELECT sender_id, message, DATE_FORMAT(timestamp, '%h:%i %p') AS time 
        FROM messages 
        WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)
        ORDER BY timestamp ASC
    ");
    $stmt->bind_param("iiii", $caretaker_id, $user_id, $user_id, $caretaker_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();

    foreach ($messages as $msg) {
        if ($msg['sender_id'] == $caretaker_id) {
            echo '<div class="flex justify-end">
                    <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded-lg max-w-xs">
                        <p class="text-sm">' . htmlspecialchars($msg['message']) . '</p>
                        <span class="text-xs text-gray-400 block text-right mt-1">' . $msg['time'] . '</span>
                    </div>
                  </div>';
        } else {
            echo '<div class="flex justify-start">
                    <div class="bg-gray-200 text-gray-800 px-4 py-2 rounded-lg max-w-xs">
                        <p class="text-sm">' . htmlspecialchars($msg['message']) . '</p>
                        <span class="text-xs text-gray-400 block mt-1">' . $msg['time'] . '</span>
                    </div>
                  </div>';
        }
    }
}
?>