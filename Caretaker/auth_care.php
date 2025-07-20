<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: Caretaker/login.php");
    exit;
}

// Log admin login
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>