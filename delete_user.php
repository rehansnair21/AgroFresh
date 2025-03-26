<?php
session_start();
require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete from sellerdetails first (if exists) due to foreign key constraint
        $delete_seller = "DELETE FROM sellerdetails WHERE id = ?";
        $stmt = $conn->prepare($delete_seller);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        // Delete from users table
        $delete_user = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($delete_user);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            // Commit transaction
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);
        } else {
            throw new Exception("Failed to delete user");
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete user: ' . $e->getMessage()
        ]);
    }
    
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request'
    ]);
}

$conn->close();
?>
