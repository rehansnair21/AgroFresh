<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'db_connection.php';

use Razorpay\Api\Api;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    // Receive the POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['amount'])) {
        throw new Exception('Amount is required');
    }

    // Initialize Razorpay API
    $api = new Api('rzp_test_tviD0nX9tPfUxN', 'tRl7osLfQLpDmAsUyrt3Y6fz');

    // Convert amount to paise (Razorpay expects amount in smallest currency unit)
    $amount = round($input['amount'] * 100);

    // Create order
    $orderData = [
        'receipt'         => 'order_' . time(),
        'amount'          => $amount,
        'currency'        => 'INR',
        'payment_capture' => 1
    ];

    $razorpayOrder = $api->order->create($orderData);

    // Return success response
    echo json_encode([
        'id' => $razorpayOrder['id'],
        'amount' => $amount,
        'currency' => 'INR',
        'order_id' => $razorpayOrder['id']
    ]);

} catch (Exception $e) {
    // Return error response
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 