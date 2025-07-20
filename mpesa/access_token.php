<?php
function getAccessToken() {
    $consumerKey = 'G530CndclKmiUJpNoGgGFpLv125VffGMCzWzR0TZSEUrIAAf';
    $consumerSecret = 'iu8jM9z5BcwEYx15cZHImf6JZP36AAgGCmXJ36QVxsYJ5Fr665oyAEInFKlGUHuG';

    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

    $url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => ['Authorization: Basic ' . $credentials],
        CURLOPT_RETURNTRANSFER => true,
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    $json = json_decode($response);
    return $json->access_token ?? null;
}
