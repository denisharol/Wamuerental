<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the property and unit from the request
$property = $_GET['property'] ?? '';
$unit = $_GET['unit'] ?? '';

if ($property) {
    if ($unit) {
        // Fetch details for a specific unit
        $stmt = $conn->prepare("SELECT rent FROM units WHERE property = ? AND unit = ?");
        $stmt->bind_param("ss", $property, $unit);
        $stmt->execute();
        $result = $stmt->get_result();
        echo json_encode($result->fetch_assoc());
    } else {
        // Fetch all units for the specified property
        $stmt = $conn->prepare("SELECT unit, status FROM units WHERE property = ?");
        $stmt->bind_param("s", $property);
        $stmt->execute();
        $result = $stmt->get_result();
        $units = [];
        while ($row = $result->fetch_assoc()) {
            $units[] = [
                'unit' => $row['unit'],
                'assigned' => $row['status'] === 'Occupied'
            ];
        }
        echo json_encode($units);
    }
} else {
    echo json_encode(['error' => 'Property not specified']);
}
?>
