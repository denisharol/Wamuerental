<?php

$host = 'localhost';      // usually localhost if using XAMPP
$dbname = 'demo1';         // your database name
$username = 'root';       // default XAMPP user
$password = '';           // default XAMPP password (blank)

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
