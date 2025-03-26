<?php
session_start();
require_once 'db_connection.php';

if (!$conn) {
    $response = array(
        'success' => false,
        'error' => 'Database connection failed'
    );
    echo json_encode($response);
    exit;
}

// Set character set to ensure proper encoding
if (!$conn->set_charset("utf8mb4")) {
    $response = array(
        'success' => false,
        'error' => 'Error setting character set: ' . $conn->error
    );
    echo json_encode($response);
    exit;
}

header('Content-Type: application/json');

// Get user identifier and type based on login status
$user_id = null;
$is_guest = true;

if (isset($_SESSION['id'])) {
    $user_id = $_SESSION['id'];
    $is_guest = false;
} else {
    $user_id = session_id();
    $is_guest = true;
}

// Initialize response array
$response = array('success' => false);

// Get the action and product_id from POST data
$action = $_POST['action'] ?? '';
$product_id = $_POST['product_id'] ?? '';
$quantity = $_POST['quantity'] ?? 1;

// Validate input
if (!$product_id || !$action) {
    $response['error'] = 'Invalid input.';
    echo json_encode($response);
    exit;
}

// Check if user is a seller of this product
$is_seller = false;
if (isset($_SESSION['id'])) {
    $seller_check = $conn->prepare("
        SELECT 1 
        FROM products p 
        JOIN sellerdetails s ON p.seller_id = s.id 
        WHERE p.id = ? AND s.id = ?
    ");
    
    if ($seller_check) {
        $seller_check->bind_param("ii", $product_id, $_SESSION['id']);
        $seller_check->execute();
        $is_seller = $seller_check->get_result()->num_rows > 0;
        $seller_check->close();

        if ($is_seller) {
            $response['error'] = 'You cannot purchase your own products.';
            echo json_encode($response);
            exit;
        }
    }
}

// Add this function at the beginning of the file after database connection
function updateCartItemsBasedOnStock($conn) {
    // Get all cart items with their current product stock
    $sql = "SELECT c.user_id, c.product_id, c.quantity, c.is_guest, p.stock 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.quantity != p.stock";
            
    $stmt = $conn->prepare($sql);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Update cart quantity to match product stock
            $update_sql = "UPDATE cart 
                          SET quantity = ? 
                          WHERE user_id = ? 
                          AND product_id = ? 
                          AND is_guest = ?";
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("isii", 
                $row['stock'],
                $row['user_id'],
                $row['product_id'],
                $row['is_guest']
            );
            $update_stmt->execute();
            $update_stmt->close();
        }
    }
    $stmt->close();
}

// Call this function at the beginning of each cart operation
updateCartItemsBasedOnStock($conn);

// Handle different cart actions
switch ($action) {
    case 'add':
        // First check product's available stock
        $stockStmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stockStmt->bind_param("i", $product_id);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        
        if ($stockResult->num_rows === 0) {
            $response['error'] = 'Product not found';
            echo json_encode($response);
            exit;
        }
        
        $product = $stockResult->fetch_assoc();
        $availableStock = $product['stock'];
        
        if ($availableStock <= 0) {
            $response['error'] = 'Product is out of stock';
            echo json_encode($response);
            exit;
        }
        
        // Begin transaction for data consistency
        $conn->begin_transaction();
        
        try {
            // Check if product already exists in cart
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND is_guest = ?");
            $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Product already in cart, increase quantity by 1
                $cartItem = $result->fetch_assoc();
                $newQuantity = $cartItem['quantity'] + 1;
                
                if ($newQuantity > $availableStock) {
                    $conn->rollback();
                    $response['error'] = "Cannot add more. Maximum quantity is limited to available stock.";
                    echo json_encode($response);
                    exit;
                }
                
                $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $updateStmt->bind_param("ii", $newQuantity, $cartItem['id']);
                $success = $updateStmt->execute();
                $updateStmt->close();
                
                // Update product stock (reduce by 1)
                if ($success) {
                    $updateStockStmt = $conn->prepare("UPDATE products SET stock = stock - 1 WHERE id = ? AND stock > 0");
                    $updateStockStmt->bind_param("i", $product_id);
                    $stockSuccess = $updateStockStmt->execute();
                    
                    if (!$stockSuccess) {
                        $conn->rollback();
                        $response['error'] = 'Failed to update product stock';
                        echo json_encode($response);
                        exit;
                    }
                    $updateStockStmt->close();
                }
                
                $response['success'] = $success;
                $response['message'] = $success ? "Added 1 more unit to cart" : 'Failed to update cart';
                
            } else {
                // Add new item with quantity 1
                $quantity = 1; // Only add 1 item
                $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, is_guest) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiii", $user_id, $product_id, $quantity, $is_guest);
                $success = $stmt->execute();
                
                if ($success) {
                    // Update product stock (reduce by 1)
                    $updateStockStmt = $conn->prepare("UPDATE products SET stock = stock - 1 WHERE id = ? AND stock > 0");
                    $updateStockStmt->bind_param("i", $product_id);
                    $stockSuccess = $updateStockStmt->execute();
                    
                    if (!$stockSuccess) {
                        $conn->rollback();
                        $response['error'] = 'Failed to update product stock';
                        echo json_encode($response);
                        exit;
                    }
                    $updateStockStmt->close();
                }
                
                $response['success'] = $success;
                $response['message'] = $success ? "Product added to cart (Qty: 1)" : 'Failed to add product to cart';
            }
            
            // Get updated cart count
            if ($success) {
                $count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ? AND is_guest = ?");
                if ($count_stmt) {
                    $count_stmt->bind_param("si", $user_id, $is_guest);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count_row = $count_result->fetch_assoc();
                    $response['cartCount'] = $count_row['total'] ?? 0;
                }
            }
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $response['success'] = false;
            $response['error'] = $e->getMessage();
        }
        break;

    case 'increase':
        // First check product's available stock
        $stockStmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stockStmt->bind_param("i", $product_id);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        
        if ($stockResult->num_rows === 0) {
            $response['error'] = 'Product not found';
            echo json_encode($response);
            exit;
        }
        
        $product = $stockResult->fetch_assoc();
        $availableStock = $product['stock'];
        
        // Get current quantity in cart
        $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ? AND is_guest = ?");
        $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $cartItem = $result->fetch_assoc();
            $currentQuantity = $cartItem['quantity'];
            
            // Check if increasing would exceed stock limit
            if ($currentQuantity >= $availableStock) {
                $response['error'] = "Quantity cannot be modified. Required quantity is fixed as per seller's stock.";
                echo json_encode($response);
                exit;
            }
            
            // Otherwise, allow increasing by 1
            $newQuantity = $currentQuantity + 1;
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND is_guest = ?");
            $updateStmt->bind_param("isii", $newQuantity, $user_id, $product_id, $is_guest);
            $success = $updateStmt->execute();
            
            if ($success) {
                // Update product stock (reduce by 1)
                $updateStockStmt = $conn->prepare("UPDATE products SET stock = stock - 1 WHERE id = ? AND stock > 0");
                $updateStockStmt->bind_param("i", $product_id);
                $stockSuccess = $updateStockStmt->execute();
                
                if (!$stockSuccess) {
                    $response['error'] = 'Failed to update product stock';
                    echo json_encode($response);
                    exit;
                }
                $updateStockStmt->close();
                
                // Get updated cart count
                $count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ? AND is_guest = ?");
                if ($count_stmt) {
                    $count_stmt->bind_param("si", $user_id, $is_guest);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count_row = $count_result->fetch_assoc();
                    $response['cartCount'] = $count_row['total'] ?? 0;
                }
                
                $response['success'] = true;
                $response['message'] = "Quantity increased by 1";
            } else {
                $response['success'] = false;
                $response['error'] = "Failed to update quantity";
            }
        } else {
            $response['error'] = "Product not found in cart";
        }
        break;

    case 'decrease':
        // First check if the item exists in the cart
        $stmt = $conn->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ? AND is_guest = ?");
        $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $cartItem = $result->fetch_assoc();
            $currentQuantity = $cartItem['quantity'];
            
            if ($currentQuantity <= 1) {
                $response['error'] = "Cannot decrease quantity below 1. Use remove if you want to delete the item.";
                echo json_encode($response);
                exit;
            }
            
            // Decrease quantity by 1
            $newQuantity = $currentQuantity - 1;
            $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND is_guest = ?");
            $updateStmt->bind_param("isii", $newQuantity, $user_id, $product_id, $is_guest);
            $success = $updateStmt->execute();
            
            if ($success) {
                // Update product stock (increase by 1)
                $updateStockStmt = $conn->prepare("UPDATE products SET stock = stock + 1 WHERE id = ?");
                $updateStockStmt->bind_param("i", $product_id);
                $stockSuccess = $updateStockStmt->execute();
                
                if (!$stockSuccess) {
                    $response['error'] = 'Failed to update product stock';
                    echo json_encode($response);
                    exit;
                }
                $updateStockStmt->close();
                
                // Get updated cart count
                $count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ? AND is_guest = ?");
                if ($count_stmt) {
                    $count_stmt->bind_param("si", $user_id, $is_guest);
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count_row = $count_result->fetch_assoc();
                    $response['cartCount'] = $count_row['total'] ?? 0;
                }
                
                $response['success'] = true;
                $response['message'] = "Quantity decreased by 1";
            } else {
                $response['success'] = false;
                $response['error'] = "Failed to update quantity";
            }
        } else {
            $response['error'] = "Product not found in cart";
        }
        break;

    case 'updateQuantity':
        $stockStmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
        $stockStmt->bind_param("i", $product_id);
        $stockStmt->execute();
        $stockResult = $stockStmt->get_result();
        
        if ($stockResult->num_rows > 0) {
            $product = $stockResult->fetch_assoc();
            $availableStock = $product['stock'];
            
            if ($quantity != $availableStock) {
                $response['error'] = "Cannot modify quantity. Required quantity is exactly $availableStock units";
                echo json_encode($response);
                exit;
            }
            
            // Update to exact stock quantity
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND is_guest = ?");
            $stmt->bind_param("isii", $availableStock, $user_id, $product_id, $is_guest);
            $success = $stmt->execute();
            $response['success'] = $success;
            $response['message'] = $success ? "Quantity updated to required $availableStock units" : 'Failed to update quantity';
        } else {
            $response['error'] = "Product not found";
        }
        break;

    case 'remove':
        $conn->begin_transaction();
        
        try {
            // Get the current quantity before removing
            $get_qty_sql = "SELECT quantity FROM cart WHERE product_id = ? AND user_id = ? AND is_guest = ?";
            $stmt = $conn->prepare($get_qty_sql);
            $stmt->bind_param("isi", $product_id, $user_id, $is_guest);
            $stmt->execute();
            $result = $stmt->get_result();
            $current_qty = 0;
            if ($row = $result->fetch_assoc()) {
                $current_qty = $row['quantity'];
            }
            $stmt->close();

            if ($current_qty > 0) {
                // Update product stock (increase by the quantity being removed)
                $updateStockStmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
                $updateStockStmt->bind_param("ii", $current_qty, $product_id);
                $stockSuccess = $updateStockStmt->execute();
                
                if (!$stockSuccess) {
                    $conn->rollback();
                    $response['success'] = false;
                    $response['error'] = 'Failed to restore product stock';
                    echo json_encode($response);
                    exit;
                }
                $updateStockStmt->close();
            }

            // Delete the item from cart
            $sql = "DELETE FROM cart WHERE product_id = ? AND user_id = ? AND is_guest = ?";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("isi", $product_id, $user_id, $is_guest);
                if ($stmt->execute()) {
                    $conn->commit();
                    echo json_encode(['success' => true]);
                } else {
                    $conn->rollback();
                    echo json_encode(['success' => false, 'error' => 'Failed to remove item']);
                }
                $stmt->close();
            } else {
                $conn->rollback();
                echo json_encode(['success' => false, 'error' => 'Failed to prepare statement']);
            }
            exit;
            
        } catch (Exception $e) {
            $conn->rollback();
            $response['success'] = false;
            $response['error'] = $e->getMessage();
        }
        break;
}

// Return JSON response
echo json_encode($response);
$conn->close();
?>