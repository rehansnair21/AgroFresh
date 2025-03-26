<?php
// At the very beginning of the file, enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'db_connection.php';
require_once 'vendor/autoload.php';
use Razorpay\Api\Api;

// Get user identifier based on login status
$user_id = null;
$is_logged_in = isset($_SESSION['id']);

if ($is_logged_in) {
    $user_id = $_SESSION['id'];
    $is_guest = 0; // Explicitly set is_guest for logged in users
} else {
    $user_id = session_id(); // Session ID for guest users
    $is_guest = 1; // Explicitly set is_guest for guest users
}

// Debug variables
 $debug_output = "Debug Info:<br>";
$debug_output .= "User ID: " . $user_id . "<br>";
$debug_output .= "Is Guest: " . ($is_guest ? 'Yes' : 'No') . "<br>";
$debug_output .= "Session ID: " . session_id() . "<br>";

// Get user details if logged in
if ($is_logged_in) {
    $user_query = "SELECT id, full_name, email, mobile, address, city, state, pincode, photo_url FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else {
    // Initialize empty user details for guests
    $user = [
        'full_name' => '',
        'email' => '',
        'mobile' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'pincode' => '',
        'photo_url' => ''
    ];
}

// Fetch cart items with explicit debugging
$sql = "SELECT c.*, p.name, p.price, p.image_url, p.category, p.seller_id, p.stock, s.full_name as seller_name 
        FROM cart c
        JOIN products p ON c.product_id = p.id
        LEFT JOIN sellerdetails s ON p.seller_id = s.id
        WHERE c.user_id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();
$cartItems = [];
$total_amount = 0;

while ($row = $result->fetch_assoc()) {
    // Assuming 'quantity' is a column in the 'cart' table, otherwise you will need to set it explicitly
    $price = $row['price'];  // The price from the products table
    $quantity = $row['quantity'];  // The quantity from the cart table (ensure this exists)

    // Check if $quantity is set and is numeric
    if (!isset($quantity) || !is_numeric($quantity) || $quantity <= 0) {
        $quantity = 1;  // Default to 1 if quantity is not valid
    }

    // Calculate the total for this item
    $itemTotal = $price * $quantity;

    // Debug information
    $debug_output .= sprintf(
        "Item: %s, Price: %.2f, Qty: %d, Total: %.2f<br>",
        $row['name'],
        $price,
        $quantity,
        $itemTotal
    );

    // Add the item to the cart items array
    $cartItems[] = array_merge($row, [
        'price' => $price,
        'quantity' => $quantity,
        'item_total' => $itemTotal
    ]);

    // Add to the running total
    $total_amount += $itemTotal;
}

$debug_output .= "FINAL TOTAL: " . number_format($total_amount, 2) . "<br>";

$stmt->close();

// Initialize Razorpay
$razorpay = new Api('rzp_test_tviD0nX9tPfUxN', 'tRl7osLfQLpDmAsUyrt3Y6fz');

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // If guest user, create a new user account
        if (!$is_logged_in) {
            // Create new user account
            $create_user = "INSERT INTO users (full_name, email, mobile, address, city, state, pincode, role) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'user')";
            $stmt = $conn->prepare($create_user);
            $stmt->bind_param("sssssss", 
                $_POST['name'],
                $_POST['email'],
                $_POST['mobile'],
                $_POST['address'],
                $_POST['city'],
                $_POST['state'],
                $_POST['pincode']
            );
            $stmt->execute();
            $user_id = (string)$stmt->insert_id; // Convert to string for cart table
            $stmt->close();
            
            // Set session for new user
            $_SESSION['id'] = $user_id;
            $_SESSION['full_name'] = $_POST['name'];
            $_SESSION['email'] = $_POST['email'];
            $_SESSION['role'] = 'user';
        }

        // Convert user_id to integer for the orders table
        $user_id_int = (int)$user_id;
        
        // Make sure total_amount is correctly formatted
        $total_amount = round(floatval($total_amount), 2);
        
        // Create order with the correct total amount
        $order_sql = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status) 
                     VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($order_sql);
        $shipping_address = $_POST['address'] . ', ' . $_POST['city'] . ', ' . $_POST['state'] . ' - ' . $_POST['pincode'];
        
        $stmt->bind_param("idss", 
            $user_id_int,
            $total_amount, 
            $shipping_address,
            $_POST['payment_method']
        );
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // Create order items and update stock
        foreach ($cartItems as $item) {
            // Insert order item
            $item_sql = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                        VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($item_sql);
            $stmt->bind_param("iiid", 
                $order_id, 
                $item['product_id'], 
                $item['quantity'], 
                $item['price']
            );
            $stmt->execute();
            $stmt->close();

            // Update product stock
            $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
            $stmt = $conn->prepare($update_stock);
            $stmt->bind_param("ii", $item['quantity'], $item['product_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Clear cart
        $clear_cart = "DELETE FROM cart WHERE user_id = ? AND is_guest = ?";
        $stmt = $conn->prepare($clear_cart);
        $stmt->bind_param("si", $user_id, $is_guest);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        
        // Redirect to order confirmation
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $error = "Error processing order: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
      :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --dark: #0f172a;
            --light: #f8fafc;
            --gradient: linear-gradient(135deg, #22c55e, #0ea5e9);
            --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            --border-radius: 1rem;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #f0f9f1;
            padding-top: 80px;
            color: #334155;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 2rem;
            position: relative;
            display: inline-block;
        }

        .page-title:after {
            content: '';
            position: absolute;
            height: 4px;
            width: 60px;
            background: var(--gradient);
            bottom: -10px;
            left: 0;
            border-radius: 2px;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 3fr 2fr;
            gap: 2rem;
        }

        .form-section, .order-summary {
            background: white;
            padding: 2.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            transition: transform 0.3s ease;
        }

        .form-section:hover, .order-summary:hover {
            transform: translateY(-5px);
        }

        h3 {
            color: var(--dark);
            margin-bottom: 1.8rem;
            font-size: 1.6rem;
            position: relative;
            padding-bottom: 0.5rem;
            font-weight: 600;
        }

        h3:after {
            content: '';
            position: absolute;
            width: 40px;
            height: 3px;
            background: var(--gradient);
            left: 0;
            bottom: 0;
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 1.8rem;
        }

        label {
            display: block;
            margin-bottom: 0.6rem;
            color: #475569;
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
        }

        input, select {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.8rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background-color: #f8fafc;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.1);
            background-color: white;
        }

        .order-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1.5rem;
            padding: 1.2rem 0;
            border-bottom: 1px solid #f1f5f9;
            align-items: center;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item img {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 0.8rem;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.08);
            border: 3px solid white;
            transition: transform 0.3s ease;
        }

        .order-item img:hover {
            transform: scale(1.05);
        }

        .item-details h4 {
            margin: 0;
            font-size: 1.15rem;
            color: #1f2937;
            font-weight: 600;
        }

        .item-details p {
            margin: 0.5rem 0;
            color: #64748b;
            font-size: 0.95rem;
        }

        .item-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.2rem;
            min-width: 120px;
            text-align: right;
        }

        .total-section {
            margin-top: 2rem;
            padding: 1.5rem;
            border-top: 2px dashed #e2e8f0;
            background-color: #f0f9ff; /* Light blue background */
            border-radius: 0.8rem;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.8rem 0;
            font-size: 1.1rem;
            color: #64748b;
        }

        .total-row.final {
            margin-top: 0.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .item-price {
            font-weight: 600;
            color: var(--primary);
            font-size: 1.1rem;
        }

        #total-amount {
            color: var(--primary-dark);
            font-weight: 700;
        }

        .submit-btn {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 1.2rem;
            border-radius: 0.8rem;
            font-size: 1.1rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(34, 197, 94, 0.2);
            letter-spacing: 0.5px;
            margin-top: 1rem;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(34, 197, 94, 0.3);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .form-section, .order-summary {
                padding: 1.8rem;
            }
        }

        .empty-cart-message {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .continue-shopping-btn {
            display: inline-block;
            margin-top: 1.5rem;
            padding: 0.9rem 1.8rem;
            background: var(--gradient);
            color: white;
            text-decoration: none;
            border-radius: 0.8rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(34, 197, 94, 0.2);
        }

        .continue-shopping-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(34, 197, 94, 0.3);
        }

        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 0.8rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
            border-left: 4px solid #dc2626;
        }

        /* New enhanced styles */
        .info-icon {
            color: var(--secondary);
            margin-left: 5px;
            font-size: 0.9rem;
        }

        .payment-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .payment-option {
            flex: 1;
            min-width: 160px;
            border: 2px solid #e5e7eb;
            border-radius: 0.8rem;
            padding: 1.2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-option.selected {
            border-color: var(--primary);
            background-color: rgba(34, 197, 94, 0.05);
        }

        .payment-option i {
            font-size: 1.6rem;
            color: #64748b;
            margin-bottom: 0.8rem;
            display: block;
        }

        .payment-option.selected i {
            color: var(--primary);
        }

        .payment-option p {
            font-weight: 600;
            color: #334155;
        }

        .order-badge {
            background: var(--gradient);
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.3rem 0.8rem;
            border-radius: 1rem;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .delivery-info {
            background-color: #f0f9ff;
            border-radius: 0.8rem;
            padding: 1rem;
            margin-top: 1.5rem;
            border-left: 4px solid var(--secondary);
        }

        .delivery-info i {
            color: var(--secondary);
            margin-right: 0.5rem;
        }

        .delivery-info p {
            color: #334155;
            font-size: 0.95rem;
            margin: 0;
        }
        
        .debug-info {
            margin: 20px;
            padding: 15px;
            background-color: #ffe8e8;
            border: 1px solid #ffb8b8;
            border-radius: 5px;
        }
        
        #cart-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: #dc2626; /* Red to make it stand out */
        }

        .header-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.2rem;
            background: white;
            color: #334155;
            text-decoration: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            box-shadow: var(--box-shadow);
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            transform: translateX(-5px);
            background: var(--gradient);
            color: white;
        }

        .back-btn i {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="header-section">
            <a href="javascript:history.back()" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <h2 class="page-title">Checkout</h2>
        </div>
        
        <!-- <?php if (isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?> -->

        <!-- Debug information - remove after fixing -->
        <!-- <div class="debug-info">
            <?php echo $debug_output; ?>
        </div> -->

        <div class="checkout-grid">
            <div class="form-section">
                <h3>Shipping Information</h3>
                <form method="POST" id="checkout-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="mobile">Mobile</label>
                        <input type="tel" id="mobile" name="mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="state">State</label>
                        <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($user['state']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="pincode">Pincode</label>
                        <input type="text" id="pincode" name="pincode" value="<?php echo htmlspecialchars($user['pincode']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="payment_method">Payment Method</label>
                        <div class="payment-options">
                            <div class="payment-option" data-method="cod">
                                <i class="fas fa-money-bill-wave"></i>
                                <p>Cash on Delivery</p>
                            </div>
                            <div class="payment-option" data-method="razorpay">
                                <i class="fas fa-credit-card"></i>
                                <p>Pay Online</p>
                            </div>
                        </div>
                        <input type="hidden" name="payment_method" id="payment_method" required>
                    </div>

                    <button type="submit" class="submit-btn">Place Order</button>
                </form>
            </div>

            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php if (!empty($cartItems)): ?>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                <p>Price: ₹<?php echo number_format($item['price'], 2); ?> × <?php echo $item['quantity']; ?> 
                                   <?php echo $item['category'] === 'milk' ? 'liters' : 'kg'; ?></p>
                            </div>
                            <div class="item-price">
                                ₹<?php echo number_format($item['item_total'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="total-section">
                        <div class="total-row final">
                            <span>Total Amount:</span>
                            <span id="cart-total">₹<?php echo number_format($total_amount, 2); ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-cart-message">
                        <p>Your cart is empty</p>
                        <a href="sale.php" class="continue-shopping-btn">Continue Shopping</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const paymentOptions = document.querySelectorAll('.payment-option');
        const paymentMethodInput = document.getElementById('payment_method');
        const checkoutForm = document.getElementById('checkout-form');
        const totalAmount = <?php echo $total_amount; ?>; // Get the total amount from PHP

        // Handle payment option selection
        paymentOptions.forEach(option => {
            option.addEventListener('click', async function() {
                paymentOptions.forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                paymentMethodInput.value = this.dataset.method;

                // If Razorpay is selected, open payment immediately
                if (this.dataset.method === 'razorpay') {
                    try {
                        // Create Razorpay order
                        const response = await fetch('create_razorpay_order.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                amount: totalAmount
                            })
                        });
                        const data = await response.json();

                        if (data.error) {
                            alert(data.error);
                            return;
                        }

                        // Initialize Razorpay payment
                        const options = {
                            key: 'rzp_test_tviD0nX9tPfUxN',
                            amount: data.amount, // Amount in paise
                            currency: 'INR',
                            name: 'AgroFresh',
                            description: 'Purchase from AgroFresh',
                            order_id: data.order_id,
                            handler: function(response) {
                                // Add payment details to form
                                const paymentInput = document.createElement('input');
                                paymentInput.type = 'hidden';
                                paymentInput.name = 'razorpay_payment_id';
                                paymentInput.value = response.razorpay_payment_id;
                                checkoutForm.appendChild(paymentInput);

                                const orderInput = document.createElement('input');
                                orderInput.type = 'hidden';
                                orderInput.name = 'razorpay_order_id';
                                orderInput.value = response.razorpay_order_id;
                                checkoutForm.appendChild(orderInput);

                                // Submit the form
                                checkoutForm.submit();
                            },
                            prefill: {
                                name: document.getElementById('name').value,
                                email: document.getElementById('email').value,
                                contact: document.getElementById('mobile').value
                            },
                            theme: {
                                color: '#22c55e'
                            }
                        };

                        const rzp = new Razorpay(options);
                        rzp.open();

                    } catch (error) {
                        console.error('Error:', error);
                        alert('Something went wrong. Please try again.');
                    }
                }
            });
        });

        // Select COD by default
        document.querySelector('[data-method="cod"]').click();

        // Handle form submission
        checkoutForm.addEventListener('submit', function(e) {
            if (paymentMethodInput.value === 'cod') {
                if (confirm('Are you sure you want to place this order?')) {
                    return true; // Allow form submission
                }
                e.preventDefault();
                return false;
            }
            // For Razorpay, prevent form submission as it's handled by the payment handler
            if (paymentMethodInput.value === 'razorpay') {
                e.preventDefault();
                return false;
            }
        });
    });
    </script>
</body>
</html>