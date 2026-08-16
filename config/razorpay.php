<?php
// config/razorpay.php

// Load Razorpay settings from database
require_once __DIR__ . '/config.php';

define('RAZORPAY_KEY_ID', get_setting('razorpay_key_id', ''));
define('RAZORPAY_KEY_SECRET', get_setting('razorpay_key_secret', ''));
define('RAZORPAY_CURRENCY', get_setting('currency', 'INR'));

// Razorpay API endpoint
define('RAZORPAY_API_URL', 'https://api.razorpay.com/v1/');

// Verify Razorpay signature
function verify_razorpay_signature($order_id, $payment_id, $signature) {
    $generated_signature = hash_hmac('sha256', $order_id . '|' . $payment_id, RAZORPAY_KEY_SECRET);
    return hash_equals($generated_signature, $signature);
}

// Create Razorpay order
function create_razorpay_order($amount, $receipt_id, $notes = []) {
    $ch = curl_init();

    $data = [
        'amount' => $amount * 100, // Convert to paise
        'currency' => RAZORPAY_CURRENCY,
        'receipt' => $receipt_id,
        'notes' => $notes
    ];

    curl_setopt_array($ch, [
        CURLOPT_URL => RAZORPAY_API_URL . 'orders',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        return json_decode($response, true);
    }

    return false;
}

// Fetch payment details
function fetch_razorpay_payment($payment_id) {
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => RAZORPAY_API_URL . 'payments/' . $payment_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        return json_decode($response, true);
    }

    return false;
}
?>