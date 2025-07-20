<?php
session_start();
include 'log_action.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: Admin/login.php");
    exit;
}

// Log admin login
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin_id = $_SESSION['admin_id'];
logAction($conn, 'admin', $admin_id, "Login", "Admin logged in successfully.");
?>