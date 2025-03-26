<?php
session_start();
require_once 'db_connection.php';

// Return JSON response
header('Content-Type: application/json');

// Debug information
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $user_id = $_SESSION['id'];
    
    // Verify the product belongs to this seller
    $check_sql = "SELECT p.id FROM products p 
                  JOIN sellerdetails s ON p.seller_id = s.id 
                  WHERE p.id = ? AND s.id = ?";
                  
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response = ['success' => false, 'message' => 'You do not have permission to delete this product'];
    } else {
        // Check if we need to force delete (remove from carts first)
        if (isset($_POST['force_delete']) && $_POST['force_delete'] === 'true') {
            // First delete from cart
            $delete_cart_sql = "DELETE FROM cart WHERE product_id = ?";
            $cart_stmt = $conn->prepare($delete_cart_sql);
            $cart_stmt->bind_param("i", $product_id);
            
            if (!$cart_stmt->execute()) {
                $response = ['success' => false, 'message' => 'Failed to remove product from carts: ' . $conn->error];
                echo json_encode($response);
                exit();
            }
            $cart_stmt->close();
        }
        
        // Now delete the product
        $delete_sql = "DELETE FROM products WHERE id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $product_id);
        
        if ($delete_stmt->execute()) {
            $response = ['success' => true, 'message' => 'Product deleted successfully'];
        } else {
            // Check for foreign key constraint error
            if ($conn->errno == 1451) { // This is the MySQL error code for foreign key constraint failure
                $response = [
                    'success' => false, 
                    'error_type' => 'foreign_key_constraint',
                    'message' => 'This product cannot be deleted because it is in customers\' carts'
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete product: ' . $conn->error];
            }
        }
        $delete_stmt->close();
    }
    $stmt->close();
}

echo json_encode($response);
$conn->close();
?>
