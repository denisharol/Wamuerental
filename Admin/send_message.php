<?php include 'auth_admin.php';

$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = 1;
    $receiver_id = intval($_POST['receiver_id']);
    $message = trim($_POST['message']);

    if (!empty($message)) {
        if ($receiver_id === -1) {
            // Send to all tenants
            $tenantsQuery = "SELECT id FROM users WHERE id != 1"; // Exclude admin
            $result = $conn->query($tenantsQuery);

            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
                while ($row = $result->fetch_assoc()) {
                    $tenant_id = $row['id'];
                    $stmt->bind_param("iis", $admin_id, $tenant_id, $message);
                    $stmt->execute();
                }
                $stmt->close();
            }
        } else {
            // Send to a specific tenant
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $admin_id, $receiver_id, $message);
            $stmt->execute();
            $stmt->close();
        }
    }
}

header("Location: Messaging.php?user_id=$receiver_id");
exit;