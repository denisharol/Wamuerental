<?php
require_once 'access_token.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize phone and amount
    $phone = $_POST['phone'];
    $amount = floatval($_POST['amount']); // Ensure it's numeric

    if ($amount <= 0) {
        echo json_encode(['error' => 'Invalid amount']);
        exit;
    }

    // Convert phone to international format (e.g., 0712345678 -> 254712345678)
    if (preg_match('/^(07|01)(\d{8})$/', $phone, $matches)) {
        $phone = '254' . substr($phone, 1);
    } elseif (preg_match('/^254\d{9}$/', $phone)) {
        // Already in international format
        // do nothing
    } else {
        echo json_encode(['error' => 'Invalid phone number format']);
        exit;
    }

    $token = getAccessToken();

    $shortCode = '174379'; // UPDATED to official sandbox shortcode
    $passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
    $timestamp = date('YmdHis');
    $password = base64_encode($shortCode . $passkey . $timestamp);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            "BusinessShortCode" => $shortCode,
            "Password" => $password,
            "Timestamp" => $timestamp,
            "TransactionType" => "CustomerPayBillOnline",
            "Amount" => $amount,
            "PartyA" => $phone,
            "PartyB" => $shortCode,
            "PhoneNumber" => $phone,
            "CallBackURL" => "https://kiwi-tolerant-recently.ngrok-free.app/demo/mpesa/callback.php",
            "AccountReference" => "WamueRent",
            "TransactionDesc" => "Rent Payment"
        ])
    ]);

    $response = curl_exec($curl);

    if ($response === false) {
        echo json_encode(["error" => curl_error($curl)]);
    } else {
        echo $response;
    }

    curl_close($curl);
}
?>