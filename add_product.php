<?php
session_start();
require_once 'db_connection.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['seller_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$seller_id = $_SESSION['seller_id'];

// Server-side validation
$errors = [];

// Validate name
$name = trim($_POST['name']);
if (strlen($name) < 3 || strlen($name) > 255) {
    $errors[] = "Product name must be between 3 and 255 characters";
}
if (!preg_match('/^[a-zA-Z0-9\s\-\_\&\(\)]+$/', $name)) {
    $errors[] = "Product name can only contain letters, numbers, spaces, and basic punctuation";
}

// Validate description
$description = trim($_POST['description']);
if (strlen($description) < 10) {
    $errors[] = "Description must be at least 10 characters long";
}
if (preg_match('/<[^>]*>/', $description)) {
    $errors[] = "HTML tags are not allowed in the description";
}

// Validate category
$category = $_POST['category'];
$valid_categories = ['fruits', 'vegetables'];
if (!in_array($category, $valid_categories)) {
    $errors[] = "Invalid category selected";
}

// Validate price
$price = filter_var($_POST['price'], FILTER_VALIDATE_FLOAT);
if ($price === false || $price <= 0 || $price > 100000) {
    $errors[] = "Price must be between 0 and ₹100,000";
}

// Validate stock
$stock = filter_var($_POST['stock'], FILTER_VALIDATE_INT);
if ($stock === false || $stock < 0 || $stock > 10000) {
    $errors[] = "Stock must be between 0 and 10,000 units";
}

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode("<br>", $errors)]);
    exit();
}

// Handle file upload
$upload_dir = 'uploads/products/';
$image_url = '';

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $error_message = "Error uploading file: ";
    switch ($_FILES['image']['error']) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $error_message .= "File size exceeds limit (5MB maximum)";
            break;
        case UPLOAD_ERR_PARTIAL:
            $error_message .= "File was only partially uploaded";
            break;
        case UPLOAD_ERR_NO_FILE:
            $error_message .= "Please select an image file";
            break;
        default:
            $error_message .= "Unknown error occurred";
    }
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit();
}

$file_tmp = $_FILES['image']['tmp_name'];
$file_name = $_FILES['image']['name'];
$file_size = $_FILES['image']['size'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Additional security checks
$max_size = 5 * 1024 * 1024; // 5MB
$allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

// Validate file size
if ($file_size > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit();
}

// Validate file type
if (!in_array($file_ext, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed']);
    exit();
}

// Verify it's actually an image
$image_info = @getimagesize($file_tmp);
if ($image_info === false) {
    echo json_encode(['success' => false, 'message' => 'Invalid image file']);
    exit();
}

// Generate unique filename
$unique_filename = uniqid('product_') . '.' . $file_ext;
$upload_path = $upload_dir . $unique_filename;

// Create uploads directory if it doesn't exist
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if (!move_uploaded_file($file_tmp, $upload_path)) {
    echo json_encode(['success' => false, 'message' => 'Error saving file. Please check directory permissions.']);
    exit();
}

$image_url = $upload_path;

// Sanitize inputs
$name = $conn->real_escape_string(htmlspecialchars($name));
$description = $conn->real_escape_string(htmlspecialchars($description));
$category = $conn->real_escape_string(htmlspecialchars($category));
$price = $conn->real_escape_string($price);
$stock = $conn->real_escape_string($stock);
$image_url = $conn->real_escape_string($image_url);

// Insert product into database
$insert_sql = "INSERT INTO products (name, description, price, image_url, category, stock, seller_id) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_sql);

if ($stmt) {
    $stmt->bind_param("ssdssis", 
        $name,
        $description,
        $price,
        $image_url,
        $category,
        $stock,
        $seller_id
    );

    if ($stmt->execute()) {
        // Get the newly inserted product's ID
        $product_id = $stmt->insert_id;
        
        // Return the product data for DOM update
        $product_data = [
            'id' => $product_id,
            'name' => htmlspecialchars_decode($name),
            'description' => htmlspecialchars_decode($description),
            'category' => htmlspecialchars_decode($category),
            'price' => $price,
            'stock' => $stock,
            'image_url' => $image_url
        ];
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product added successfully!',
            'product' => $product_data
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error adding product: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error preparing statement: ' . $conn->error]);
}

$conn->close();
?>
