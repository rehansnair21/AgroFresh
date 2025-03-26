<?php
session_start();
require_once 'config.php';
require_once 'cart_operations.php';

header('Content-Type: application/json');

// Get user identifier based on login status
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit;
}

$user_id = $_SESSION['id'];

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['product_id']) || !isset($data['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

// Verify the product exists and is not from the current user (if they're a seller)
$stmt = $conn->prepare("
    SELECT p.*, s.id as seller_id 
    FROM products p 
    LEFT JOIN sellerdetails s ON p.seller_id = s.id 
    WHERE p.id = ?
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$stmt->bind_param("i", $data['product_id']);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Check if user is the seller of this product
$seller_check = $conn->prepare("SELECT id FROM sellerdetails WHERE id = ?");
$seller_check->bind_param("i", $_SESSION['id']);
$seller_check->execute();
$seller_result = $seller_check->get_result();

if ($seller_result->num_rows > 0) {
    $seller = $seller_result->fetch_assoc();
    if ($seller['id'] == $product['seller_id']) {
        echo json_encode(['success' => false, 'message' => 'Sellers cannot add their own products to cart']);
        exit;
    }
}

// Add to cart using CartOperations class
$cartOps = new CartOperations($conn);
$success = $cartOps->addToCart($user_id, $data['product_id'], $data['quantity']);

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Product added to cart successfully' : 'Failed to add product to cart'
]);
?>
