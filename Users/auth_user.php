<?php
session_start();
include '../Admin/log_action.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Log user login
$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
logUserAction($conn, $user_id, "Login", "User logged in successfully.");
?>