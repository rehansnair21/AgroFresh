<?php
// Database configuration
$db_host = 'localhost';       // Database host (usually localhost for XAMPP)
$db_name = 'agrofresh';       // Your database name
$db_user = 'root';            // Database username (default is root for XAMPP)
$db_pass = '';                // Database password (empty by default for XAMPP)

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of special characters
$conn->set_charset("utf8mb4");

// Optional: Set timezone (adjust to your region if needed)
date_default_timezone_set('Asia/Kolkata');

// Function to sanitize input data
function sanitize_input($conn, $data) {
    return $conn->real_escape_string(htmlspecialchars(trim($data)));
}

// Function to format currency
function format_currency($amount) {
    return '₹' . number_format($amount, 2);
}

// Function to check if a user is a seller
function is_seller($conn, $user_id) {
    // Direct check in the users table (since seller_id references users.id)
    $query = "SELECT id FROM users WHERE id = ? AND user_type = 'seller'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $is_seller = $result->num_rows > 0;
        $stmt->close();
        return $is_seller;
    }
    return false;
}

// Function to get seller ID (which is the same as user_id since seller_id points to users.id)
function get_seller_id($conn, $user_id) {
    // In this case, the seller_id is actually the user_id itself
    if (is_seller($conn, $user_id)) {
        return $user_id;
    }
    return null;
}

// Function to get cart count for a user
function get_cart_count($conn, $user_id, $is_guest = false) {
    $query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ? AND is_guest = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("si", $user_id, $is_guest);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $count = $row['total'] ?? 0;
            $stmt->close();
            return $count;
        }
        $stmt->close();
    }
    return 0;
}

// Function to get all products
function get_all_products($conn, $current_seller_id = null) {
    // Create base query
    $query = "SELECT p.*, u.full_name as seller_name 
              FROM products p 
              LEFT JOIN users u ON p.seller_id = u.id";
    
    // Add WHERE clause to exclude current seller's products if a seller is logged in
    if ($current_seller_id) {
        $query .= " WHERE p.seller_id != ?";
    }
    
    // Add ordering
    $query .= " ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($query);
    
    // Bind the seller ID parameter if needed
    if ($current_seller_id) {
        $stmt->bind_param("i", $current_seller_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $products = [];
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    $stmt->close();
    return $products;
}

// Function to get a single product by ID
function get_product_by_id($conn, $product_id) {
    $sql = "SELECT p.*, u.full_name as seller_name 
            FROM products p 
            LEFT JOIN users u ON p.seller_id = u.id 
            WHERE p.id = ?";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row;
        }
        
        $stmt->close();
    }
    
    return null;
}

// Function to get products by seller ID
function get_seller_products($conn, $seller_id) {
    $sql = "SELECT * FROM products WHERE seller_id = ? ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        $stmt->close();
        return $products;
    }
    
    return [];
}

// Function to get cart items for a user
function get_cart_items($conn, $user_id, $is_guest = false) {
    $sql = "SELECT c.*, p.name, p.price, p.image_url, p.category, p.seller_id, p.stock, 
                   u.full_name as seller_name 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            LEFT JOIN users u ON p.seller_id = u.id 
            WHERE c.user_id = ? AND c.is_guest = ?
            ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $user_id, $is_guest);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cart_items = [];
        while ($row = $result->fetch_assoc()) {
            $cart_items[] = $row;
        }
        
        $stmt->close();
        return $cart_items;
    }
    
    return [];
}

// Function to add an item to cart - modified to add just 1 unit at a time
function add_to_cart($conn, $user_id, $product_id, $is_guest = false) {
    // First check if product exists and get minimum stock
    $product = get_product_by_id($conn, $product_id);
    
    if (!$product) {
        return [
            'success' => false,
            'error' => 'Product not found'
        ];
    }
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Check if item already exists in cart
        $check_sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ? AND is_guest = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing_item = $result->fetch_assoc();
        $stmt->close();
        
        if ($existing_item) {
            // Update existing cart item - add 1 to current quantity
            $new_quantity = $existing_item['quantity'] + 1;
            $update_sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ? AND is_guest = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("isii", $new_quantity, $user_id, $product_id, $is_guest);
            $success = $stmt->execute();
            $stmt->close();
        } else {
            // Add new cart item with 1 quantity
            $insert_sql = "INSERT INTO cart (user_id, product_id, quantity, is_guest) VALUES (?, ?, 1, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
            $success = $stmt->execute();
            $stmt->close();
        }
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => $existing_item ? '1 unit added to cart' : 'Product added to cart',
            'cartCount' => get_cart_count($conn, $user_id, $is_guest)
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Function to remove item from cart
function remove_from_cart($conn, $user_id, $product_id, $is_guest = false) {
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ? AND is_guest = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $user_id, $product_id, $is_guest);
        $success = $stmt->execute();
        $stmt->close();
        
        $conn->commit();
        
        return [
            'success' => $success,
            'message' => $success ? 'Item removed from cart' : 'Failed to remove item',
            'cartCount' => get_cart_count($conn, $user_id, $is_guest)
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
?>
