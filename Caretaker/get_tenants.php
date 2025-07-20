<?php
include 'auth_admin.php';

$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session and fetch caretaker's property
session_start();
if (!isset($_SESSION['caretaker_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$caretaker_id = $_SESSION['caretaker_id'];
$stmt = $conn->prepare("SELECT property FROM caretaker WHERE id = ?");
$stmt->bind_param("i", $caretaker_id);
$stmt->execute();
$stmt->bind_result($caretaker_property);
$stmt->fetch();
$stmt->close();

$property = $_GET['property'] ?? '';
$tenant = $_GET['tenant'] ?? '';

if ($property === $caretaker_property && !$tenant) {
    // Fetch tenants tied to the selected property
    $stmt = $conn->prepare("
        SELECT id, name 
        FROM users 
        WHERE property = ?
    ");
    $stmt->bind_param("s", $property);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenants = [];
    while ($row = $result->fetch_assoc()) {
        $tenants[] = $row;
    }
    echo json_encode($tenants);
} elseif ($property === $caretaker_property && $tenant) {
    // Fetch tenant details (unit and security deposit)
    $stmt = $conn->prepare("
        SELECT unit, rent_amount AS security_deposit 
        FROM users 
        WHERE property = ? AND id = ?
    ");
    $stmt->bind_param("si", $property, $tenant);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenantData = $result->fetch_assoc();
    echo json_encode($tenantData);
} else {
    // Return an empty response if the property does not match or invalid parameters
    http_response_code(400);
    echo json_encode(["error" => "Invalid property or tenant"]);
}
?>