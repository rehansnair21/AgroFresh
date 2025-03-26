<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a seller
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$user_id = $_SESSION['id'];

// Validate product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$product_id = intval($_GET['id']);

// Prepare and execute query with proper parameter binding
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
if ($stmt) {
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'product' => $product
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found or you do not have permission to edit it'
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ]);
}

$conn->close();
?>
