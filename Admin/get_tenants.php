<?php include 'auth_admin.php';
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$property = $_GET['property'] ?? '';
$tenant = $_GET['tenant'] ?? '';

if ($property && !$tenant) {
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
} elseif ($property && $tenant) {
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
    echo json_encode([]);
}
?>