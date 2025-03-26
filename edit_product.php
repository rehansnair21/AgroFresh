<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if user is a seller by looking up in sellerdetails
$seller_query = $conn->prepare("SELECT id FROM sellerdetails WHERE id = ?");
if (!$seller_query) {
    die("Error preparing seller query: " . $conn->error);
}

$seller_query->bind_param("i", $user_id);
$seller_query->execute();
$seller_result = $seller_query->get_result();

if ($seller_result->num_rows === 0) {
    header("Location: seller_register.php");
    exit();
}

$seller_data = $seller_result->fetch_assoc();
$seller_id = $seller_data['id'];
$seller_query->close();

// Fetch product details
$product_query = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
if (!$product_query) {
    die("Error preparing product query: " . $conn->error);
}

$product_query->bind_param("ii", $product_id, $seller_id);
$product_query->execute();
$product_result = $product_query->get_result();

if ($product_result->num_rows === 0) {
    header("Location: seller_dashboard.php");
    exit();
}

$product = $product_result->fetch_assoc();
$product_query->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category = $_POST['category'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    
    $image_url = $product['image_url']; // Keep existing image by default
    
    // Handle new image upload if provided
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $file_tmp = $_FILES['product_image']['tmp_name'];
        $file_name = $_FILES['product_image']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Generate unique filename
        $unique_filename = uniqid() . '_' . time() . '.' . $file_ext;
        $upload_path = $upload_dir . $unique_filename;
        
        // Check if file is an actual image
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($file_ext, $allowed_types) && getimagesize($file_tmp)) {
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Delete old image if exists and different from default
                if ($image_url && file_exists($image_url)) {
                    unlink($image_url);
                }
                $image_url = $upload_path;
            } else {
                $error_message = "Error uploading file.";
            }
        } else {
            $error_message = "Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    
    if (!isset($error_message)) {
        // Update product in database
        $update_sql = "UPDATE products SET name = ?, description = ?, category = ?, price = ?, stock = ?, image_url = ? WHERE id = ? AND seller_id = ?";
        $stmt = $conn->prepare($update_sql);
        
        if (!$stmt) {
            die("Error preparing update statement: " . $conn->error);
        }
        
        $stmt->bind_param("sssdssis", 
            $name,
            $description,
            $category,
            $price,
            $stock,
            $image_url,
            $product_id,
            $seller_id
        );

        if ($stmt->execute()) {
            header("Location: seller_dashboard.php?success=Product updated successfully!");
            exit();
        } else {
            $error_message = "Error updating product: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reuse the same styles from seller_dashboard.php */
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --accent: #1ba23f;
            --dark: #0f172a;
            --light: #f8fafc;
            --gradient: linear-gradient(135deg, #22c55e, #0ea5e9);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background: #f0f2f5;
            min-height: 100vh;
            padding: 2rem;
            color: var(--dark);
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            padding: 2.5rem;
            background: white;
            border-radius: 1.5rem;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .back-btn {
            position: absolute;
            left: 2rem;
            top: 2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: var(--gradient);
            color: white;
            text-decoration: none;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
        }

        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }

        .edit-form {
            background: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid rgba(34, 197, 94, 0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 2.8rem;
            color: #64748b;
            transition: var(--transition);
            pointer-events: none;
        }

        label {
            display: block;
            margin-bottom: 0.75rem;
            color: var(--dark);
            font-weight: 600;
            font-size: 1.1rem;
        }

        input, select, textarea {
            width: 100%;
            padding: 1rem;
            padding-left: 2.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: var(--transition);
            background: #f8fafc;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
            padding-left: 1rem;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
            background: white;
        }

        .current-image {
            margin: 1rem 0;
            text-align: center;
        }

        .current-image img {
            max-width: 200px;
            border-radius: 0.75rem;
            box-shadow: var(--shadow);
        }

        .submit-btn {
            width: 100%;
            padding: 1.25rem;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 0.75rem;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(34, 197, 94, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(34, 197, 94, 0.4);
        }

        .message {
            padding: 1.25rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 2px solid #fca5a5;
        }

        .help-text {
            color: #64748b;
            font-size: 0.9rem;
            margin-top: 0.75rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .container {
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }

        /* Add these new styles */
        .error-feedback {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: none;
        }

        input.invalid, select.invalid, textarea.invalid {
            border-color: #dc2626;
        }

        input.invalid:focus, select.invalid:focus, textarea.invalid:focus {
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="seller_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
            <h1>Edit Product</h1>
            <p>Update your product information</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="edit-form">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    <i class="fas fa-box"></i>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <?php
                        $categories = ['fruits', 'vegetables', 'dairy', 'milk', 'seeds', 'grains'];
                        foreach ($categories as $cat) {
                            $selected = ($cat === $product['category']) ? 'selected' : '';
                            echo "<option value=\"$cat\" $selected>" . ucfirst($cat) . "</option>";
                        }
                        ?>
                    </select>
                    <i class="fas fa-layer-group"></i>
                </div>

                <div class="form-group">
                    <label for="price">Price per <span id="priceUnit"><?php echo $product['category'] === 'milk' ? 'liter' : 'kg'; ?></span> (₹)</label>
                    <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
                    <i class="fas fa-tag"></i>
                </div>

                <div class="form-group">
                    <label for="stock">Stock (<span id="stockUnit"><?php echo $product['category'] === 'milk' ? 'liters' : 'kg'; ?></span>)</label>
                    <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
                    <i class="fas fa-warehouse"></i>
                </div>

                <div class="form-group">
                    <label for="product_image">Product Image</label>
                    <div class="current-image">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Current product image">
                        <p class="help-text">Current image</p>
                    </div>
                    <input type="file" id="product_image" name="product_image" accept="image/*">
                    <p class="help-text">
                        <i class="fas fa-info-circle"></i>
                        Leave empty to keep current image. Accepted formats: JPG, JPEG, PNG, GIF. Max size: 5MB
                    </p>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-save"></i>
                    Update Product
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const nameInput = document.getElementById('name');
            const descriptionInput = document.getElementById('description');
            const categorySelect = document.getElementById('category');
            const priceInput = document.getElementById('price');
            const stockInput = document.getElementById('stock');
            const imageInput = document.getElementById('product_image');

            // Add error message elements
            const inputs = [nameInput, descriptionInput, categorySelect, priceInput, stockInput];
            inputs.forEach(input => {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-feedback';
                errorDiv.id = `${input.id}-error`;
                input.parentNode.appendChild(errorDiv);
            });

            // Validation functions
            const validations = {
                name: (value) => {
                    if (value.length < 3) return 'Product name must be at least 3 characters long';
                    if (value.length > 100) return 'Product name must be less than 100 characters';
                    return '';
                },
                description: (value) => {
                    if (value.length < 10) return 'Description must be at least 10 characters long';
                    if (value.length > 1000) return 'Description must be less than 1000 characters';
                    return '';
                },
                category: (value) => {
                    if (!value) return 'Please select a category';
                    return '';
                },
                price: (value) => {
                    if (value <= 0) return 'Price must be greater than 0';
                    if (value > 1000000) return 'Price must be less than 1,000,000';
                    return '';
                },
                stock: (value) => {
                    if (value < 0) return 'Stock cannot be negative';
                    if (value > 1000000) return 'Stock must be less than 1,000,000';
                    return '';
                }
            };

            // Live validation handler
            function validateInput(input) {
                const errorDiv = document.getElementById(`${input.id}-error`);
                const errorMessage = validations[input.id](input.value);
                
                if (errorMessage) {
                    input.classList.add('invalid');
                    errorDiv.textContent = errorMessage;
                    errorDiv.style.display = 'block';
                    return false;
                } else {
                    input.classList.remove('invalid');
                    errorDiv.style.display = 'none';
                    return true;
                }
            }

            // Add live validation listeners
            inputs.forEach(input => {
                ['input', 'change'].forEach(event => {
                    input.addEventListener(event, () => validateInput(input));
                });
            });

            // Image validation
            imageInput.addEventListener('change', function() {
                const errorDiv = document.getElementById('product_image-error') || 
                    (() => {
                        const div = document.createElement('div');
                        div.className = 'error-feedback';
                        div.id = 'product_image-error';
                        this.parentNode.appendChild(div);
                        return div;
                    })();

                if (this.files.length > 0) {
                    const file = this.files[0];
                    const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    const maxSize = 5 * 1024 * 1024; // 5MB

                    if (!validTypes.includes(file.type)) {
                        errorDiv.textContent = 'Invalid file type. Only JPG, PNG & GIF files are allowed.';
                        errorDiv.style.display = 'block';
                        this.value = '';
                        return;
                    }

                    if (file.size > maxSize) {
                        errorDiv.textContent = 'File size must be less than 5MB';
                        errorDiv.style.display = 'block';
                        this.value = '';
                        return;
                    }

                    errorDiv.style.display = 'none';
                }
            });

            // Category change handler (keep existing functionality)
            categorySelect.addEventListener('change', function() {
                const unit = this.value === 'milk' ? 'liter' : 'kg';
                const pluralUnit = this.value === 'milk' ? 'liters' : 'kg';
                document.getElementById('priceUnit').textContent = unit;
                document.getElementById('stockUnit').textContent = pluralUnit;
            });

            // Form submission validation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate all inputs
                inputs.forEach(input => {
                    if (!validateInput(input)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
