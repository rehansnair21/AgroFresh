<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['id']) || (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') && (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seller_id'])) {
    $seller_id = $_POST['seller_id'];
    
    try {
        // Start transaction
        $conn->begin_transaction();

        // First, delete all products associated with this seller
        $delete_products = "DELETE FROM products WHERE seller_id = ?";
        $stmt = $conn->prepare($delete_products);
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();

        // Then delete the seller
        $delete_seller = "DELETE FROM sellerdetails WHERE id = ?";
        $stmt = $conn->prepare($delete_seller);
        $stmt->bind_param("i", $seller_id);
        
        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Seller deleted successfully'
            ]);
        } else {
            throw new Exception("Error deleting seller");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

$conn->close();
?>
