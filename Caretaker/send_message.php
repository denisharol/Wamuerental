<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Start the session if it's not already active
}

$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate caretaker_id
    $caretaker_id = $_SESSION['caretaker_id'] ?? null; // Assuming caretaker ID is stored in the session
    if (!$caretaker_id) {
        die("Error: Caretaker ID not found in session.");
    }

    // Ensure caretaker exists in the users table
    $checkCaretaker = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $checkCaretaker->bind_param("i", $caretaker_id);
    $checkCaretaker->execute();
    $checkCaretaker->store_result();

    if ($checkCaretaker->num_rows === 0) {
        // Insert caretaker into users table if not exists
        $insertCaretaker = $conn->prepare("INSERT INTO users (id, name, email, property) SELECT id, name, email, property FROM caretaker WHERE id = ?");
        $insertCaretaker->bind_param("i", $caretaker_id);
        $insertCaretaker->execute();
        $insertCaretaker->close();
    }
    $checkCaretaker->close();

    $receiver_id = intval($_POST['receiver_id']);
    $message = trim($_POST['message']);

    if (!empty($message)) {
        // Get the caretaker's assigned property
        $property_query = $conn->prepare("SELECT property FROM caretaker WHERE id = ?");
        $property_query->bind_param("i", $caretaker_id);
        $property_query->execute();
        $property_query->bind_result($caretaker_property);
        $property_query->fetch();
        $property_query->close();

        if ($receiver_id === -1) {
            // Send to all tenants assigned to the caretaker's property
            $tenantsQuery = $conn->prepare("SELECT id FROM users WHERE property = ?");
            $tenantsQuery->bind_param("s", $caretaker_property);
            $tenantsQuery->execute();
            $result = $tenantsQuery->get_result();

            if ($result->num_rows > 0) {
                $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
                while ($row = $result->fetch_assoc()) {
                    $tenant_id = $row['id'];
                    $stmt->bind_param("iis", $caretaker_id, $tenant_id, $message);
                    $stmt->execute();
                }
                $stmt->close();
            }
            $tenantsQuery->close();
        } else {
            // Send to a specific tenant
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $caretaker_id, $receiver_id, $message);
            $stmt->execute();
            $stmt->close();
        }
    }
}

header("Location: Messaging.php?user_id=$receiver_id");
exit;