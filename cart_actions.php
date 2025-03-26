<?php
session_start();
require_once 'db_connection.php';

// Get user identifier based on login status
$user_id = null;
$is_logged_in = isset($_SESSION['id']);

if ($is_logged_in) {
    $user_id = $_SESSION['id'];
} else {
    $user_id = session_id(); // Use session_id for guest users
}

$is_guest = !$is_logged_in ? 1 : 0;
$response = ['success' => false];

// Process the action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    
    // Validate the product_id
    if (empty($product_id)) {
        $response['error'] = 'Invalid product ID';
        echo json_encode($response);
        exit;
    }
    
    // Check if user is the seller of this product
    if ($is_logged_in) {
        $product = get_product_by_id($conn, $product_id);
        if ($product && $product['seller_id'] == $_SESSION['id']) {
            $response['error'] = 'You cannot purchase your own product';
            echo json_encode($response);
            exit;
        }
    }
    
    // Process different actions
    switch ($action) {
        case 'add':
            $result = add_to_cart($conn, $user_id, $product_id, $is_guest);
            $response = $result;
            break;
            
        case 'remove':
            $result = remove_from_cart($conn, $user_id, $product_id, $is_guest);
            $response = $result;
            break;
            
        default:
            $response['error'] = 'Invalid action';
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>
