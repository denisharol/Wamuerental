<?php include 'auth_admin.php';
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$property = $_GET['property'] ?? '';
$unit = $_GET['unit'] ?? '';

if ($property && !$unit) {
    $stmt = $conn->prepare("
        SELECT u.unit, 
               u.rent,
               CASE WHEN EXISTS (
                   SELECT 1 FROM users WHERE users.property = u.property AND users.unit = u.unit
               ) THEN 1 ELSE 0 END AS assigned
        FROM units u
        WHERE u.property = ?
    ");
    $stmt->bind_param("s", $property);
    $stmt->execute();
    $result = $stmt->get_result();
    $units = [];
    while ($row = $result->fetch_assoc()) {
        $units[] = $row;
    }
    echo json_encode($units);
} elseif ($property && $unit) {
    $stmt = $conn->prepare("SELECT rent FROM units WHERE property = ? AND unit = ?");
    $stmt->bind_param("ss", $property, $unit);
    $stmt->execute();
    $result = $stmt->get_result();
    $unitData = $result->fetch_assoc();
    echo json_encode($unitData);
} else {
    echo json_encode([]);
}
?>