<?php
$data = json_decode(file_get_contents('php://input'), true);
file_put_contents("mpesa_log.txt", json_encode($data), FILE_APPEND); // for debugging

$conn = new mysqli("localhost", "root", "", "Demo1");
if ($conn->connect_error) die("DB Error");

// Defensive null checks
$callback = $data['Body']['stkCallback'] ?? null;
if (!$callback || $callback['ResultCode'] !== 0) exit(); // Exit if failed transaction

$metadata = $callback['CallbackMetadata']['Item'] ?? [];

$amount = null;
$mpesaCode = null;
$phone = null;

foreach ($metadata as $item) {
    if ($item['Name'] === 'Amount') {
        $amount = $item['Value'];
    } elseif ($item['Name'] === 'MpesaReceiptNumber') {
        $mpesaCode = $item['Value'];
    } elseif ($item['Name'] === 'PhoneNumber') {
        $phone = $item['Value'];
    }
}

if (!$amount || !$mpesaCode || !$phone) exit(); // Exit if missing critical values

$timestamp = date('Y-m-d H:i:s');

// Get tenant details
$stmt = $conn->prepare("SELECT id, name, property, unit FROM users WHERE phone = ? LIMIT 1");
$stmt->bind_param("s", $phone);
$stmt->execute();
$stmt->bind_result($tenant_id, $name, $property, $unit);
$stmt->fetch();
$stmt->close();

if (!$tenant_id) exit(); // No matching tenant

// Insert into transactions table
$stmt = $conn->prepare("INSERT INTO transactions (transaction_code, payment_method, phone_number, name, tenant_id, property, unit, amount, date) VALUES (?, 'Mpesa', ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssds", $mpesaCode, $phone, $name, $tenant_id, $property, $unit, $amount, $timestamp);
$stmt->execute();
$stmt->close();