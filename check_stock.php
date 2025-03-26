<?php
require_once 'db_connection.php';
header('Content-Type: application/json');

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            echo json_encode([
                'success' => true,
                'stock' => $row['stock']
            ]);
            exit;
        }
    }
}

echo json_encode([
    'success' => false,
    'error' => 'Failed to get stock information'
]);

$conn->close(); 