<?php
function getUserIp() {
    // Get real IP address even behind proxies
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

function logAction($conn, $userType, $userIdOrName, $action, $details, $page = null) {
    $ip = getUserIp();
    $page = $page ?? ($_SERVER['PHP_SELF'] ?? 'unknown');
    $stmt = $conn->prepare("INSERT INTO logs (user_type, user_identifier, action, details, page, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssssss", $userType, $userIdOrName, $action, $details, $page, $ip);
    $stmt->execute();
    $stmt->close();
}

// Helper for admin
function logAdminAction($conn, $admin_id, $action, $details, $page = null) {
    logAction($conn, 'admin', $admin_id, $action, $details, $page);
}

// Helper for caretaker (logs name, not id)
function logCaretakerAction($conn, $caretaker_id, $action, $details, $page = null) {
    $name = "Unknown Caretaker";
    $stmt = $conn->prepare("SELECT name FROM caretaker WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $caretaker_id);
        $stmt->execute();
        $stmt->bind_result($name);
        $stmt->fetch();
        $stmt->close();
    }
    logAction($conn, 'caretaker', $name, $action, $details, $page);
}

function logUserAction($conn, $user_id, $action, $description) {
    $stmt = $conn->prepare("INSERT INTO user_logs (user_id, action, description, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $action, $description);
    $stmt->execute();
    $stmt->close();
}
?>