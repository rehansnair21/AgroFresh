<?php
session_start();
require_once 'db_connection.php';

// Get user identifier based on login status
$user_id = null;
$is_logged_in = isset($_SESSION['id']);

if ($is_logged_in) {
    $user_id = $_SESSION['id'];
} else {
    $user_id = session_id(); // Use session_id for guest users
}

// Get seller ID if user is logged in
$current_seller_id = null;
if ($is_logged_in) {
    $seller_query = "SELECT id FROM sellerdetails WHERE id = ?";
    $stmt = $conn->prepare($seller_query);
    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $sellerResult = $stmt->get_result();
        if ($sellerRow = $sellerResult->fetch_assoc()) {
            $current_seller_id = $sellerRow['id'];
        }
        $stmt->close();
    }
}

// Fetch cart items from database with seller verification
$cartItems = [];
$sql = "SELECT c.*, p.name, p.price, p.image_url, p.category, p.seller_id, p.stock, 
               s.full_name as seller_name 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        LEFT JOIN sellerdetails s ON p.seller_id = s.id 
        WHERE c.user_id = ? AND c.is_guest = ?
        ORDER BY c.created_at DESC"; // Added ordering for consistency

$stmt = $conn->prepare($sql);
if ($stmt) {
    $is_guest = !$is_logged_in;
    $stmt->bind_param("si", $user_id, $is_guest);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            // Skip products that belong to the current seller
            if ($current_seller_id && $row['seller_id'] == $current_seller_id) {
                // Remove invalid items from cart
                $delete_stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ? AND is_guest = ?");
                if ($delete_stmt) {
                    $delete_stmt->bind_param("sii", $user_id, $row['product_id'], $is_guest);
                    $delete_stmt->execute();
                    $delete_stmt->close();
                }
                continue;
            }
            
            // Calculate item total
            $itemTotal = $row['price'] * $row['quantity'];
            
            // Set minimum quantity (1 for most products)
            $minimumQty = 1; 
            
            $cartItems[] = [
                'id' => $row['product_id'],
                'name' => htmlspecialchars($row['name']),
                'price' => $row['price'],
                'image_url' => htmlspecialchars($row['image_url']),
                'quantity' => $row['quantity'],
                'category' => $row['category'],
                'seller_name' => htmlspecialchars($row['seller_name'] ?? 'Unknown Seller'),
                'stock' => $row['stock'],
                'minimum_qty' => $minimumQty,
                'total' => $itemTotal
            ];
        }
        $stmt->close();
    } else {
        die("Error fetching cart items: " . $conn->error);
    }
} else {
    die("Error preparing statement: " . $conn->error);
}

// Calculate cart total
$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['total'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reuse existing styles from sale.php */
        :root {
    --primary: #22c55e;
    --primary-dark: #16a34a;
    --secondary: #0ea5e9;
    --accent: #1ba23f;
    --dark: #0f172a;
    --light: #f8fafc;
    --gradient: linear-gradient(135deg, #22c55e, #0ea5e9);
    --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Plus Jakarta Sans', sans-serif;
}

body {
    background-color: #f0f5f1;
    padding-top: 80px;
    color: #333;
}

/* Header styling */
.header {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background: white;
    box-shadow: var(--box-shadow);
    z-index: 1000;
}

.nav {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    font-size: 1.5rem;
    font-weight: 700;
    background: var(--gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.nav-links a {
    color: var(--dark);
    text-decoration: none;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: var(--transition);
}

.nav-links a:hover {
    background: var(--gradient);
    color: white;
}

/* Cart container */
.cart-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}

.cart-container h1 {
    margin-bottom: 2rem;
    font-size: 2.2rem;
    color: var(--dark);
    position: relative;
    padding-bottom: 0.5rem;
}

.cart-container h1::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 80px;
    height: 4px;
    background: var(--gradient);
    border-radius: 2px;
}

/* Cart item styling */
.cart-item {
    display: grid;
    grid-template-columns: 100px 2fr 1fr 1fr 100px;
    align-items: center;
    gap: 1.5rem;
    background: white;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    border-radius: 0.8rem;
    box-shadow: var(--box-shadow);
    transition: var(--transition);
}

.cart-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
}

.cart-item img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 0.8rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.cart-item h3 {
    font-size: 1.2rem;
    margin-bottom: 0.5rem;
    color: var(--dark);
}

.cart-item p {
    color: #64748b;
    font-weight: 500;
}

.min-qty-info {
    color: #ef4444;
    font-size: 0.9rem;
    margin-top: 0.5rem;
    font-weight: 600;
}

/* Quantity controls */
.quantity-controls {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    background: #f1f5f9;
    padding: 0.5rem;
    border-radius: 0.5rem;
    width: fit-content;
}

.quantity-btn {
    background: var(--gradient);
    color: white;
    border: none;
    height: 30px;
    width: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.3rem;
    cursor: pointer;
    transition: var(--transition);
    font-weight: bold;
}

.quantity-btn:hover {
    background: var(--primary-dark);
    transform: scale(1.05);
}

.quantity-btn:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    transform: none;
}

.quantity-btn:disabled:hover {
    background: #cbd5e1;
    transform: none;
}

.quantity-display {
    font-weight: 600;
    min-width: 30px;
    text-align: center;
}

.item-total {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--primary-dark);
}

.remove-btn {
    background: #f87171;
    color: white;
    border: none;
    height: 40px;
    width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.4rem;
    cursor: pointer;
    transition: var(--transition);
}

.remove-btn:hover {
    background: #ef4444;
    transform: scale(1.05);
}

.stock-info {
    color: #64748b;
    font-size: 0.9rem;
    margin-left: 0.5rem;
}

/* Cart summary */
.cart-summary {
    background: white;
    padding: 2rem;
    border-radius: 0.8rem;
    box-shadow: var(--box-shadow);
    margin-top: 2rem;
}

.cart-summary h2 {
    color: var(--dark);
    margin-bottom: 1rem;
    font-size: 1.5rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.cart-summary h2::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 60px;
    height: 3px;
    background: var(--gradient);
    border-radius: 1.5px;
}

.cart-summary strong {
    font-size: 1.3rem;
    color: var(--dark);
}

#cart-total {
    color: var(--primary-dark);
    font-weight: 700;
}

.checkout-btn {
    background: var(--gradient);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 0.5rem;
    cursor: pointer;
    width: 100%;
    font-size: 1.1rem;
    font-weight: 600;
    margin-top: 1.5rem;
    transition: var(--transition);
    box-shadow: 0 2px 4px rgba(34, 197, 94, 0.2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.checkout-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(34, 197, 94, 0.3);
    background: linear-gradient(135deg, #16a34a, #0284c7);
}

/* Empty cart */
.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 0.8rem;
    box-shadow: var(--box-shadow);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.empty-cart h2 {
    font-size: 1.8rem;
    color: var(--dark);
    margin-bottom: 1rem;
}

.empty-cart p {
    color: #64748b;
    margin-bottom: 1.5rem;
    max-width: 400px;
}

.empty-cart a {
    display: inline-block;
    background: var(--gradient);
    color: white !important;
    text-decoration: none;
    padding: 0.8rem 2rem;
    border-radius: 0.5rem;
    font-weight: 600;
    transition: var(--transition);
    box-shadow: 0 2px 4px rgba(34, 197, 94, 0.2);
}

.empty-cart a:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(34, 197, 94, 0.3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .cart-item {
        grid-template-columns: 80px 1fr;
        gap: 1rem;
        padding: 1rem;
    }
    
    .cart-item > div:nth-child(2) {
        grid-column: 2;
    }
    
    .quantity-controls, .item-total {
        grid-column: span 2;
        margin-top: 0.5rem;
    }
    
    .remove-btn {
        position: absolute;
        top: 1rem;
        right: 1rem;
        height: 30px;
        width: 30px;
    }
    
    .cart-item {
        position: relative;
    }
}
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">AgroFresh</div>
            <div class="nav-links">
                <a href="sale.php">Continue Shopping</a>
            </div>
        </nav>
    </header>

    <div class="cart-container">
        <h1 style="margin-bottom: 2rem;">Shopping Cart</h1>
        
        <?php if (!empty($cartItems)): ?>
            <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" data-product-id="<?php echo $item['id']; ?>" 
                     data-stock="<?php echo $item['stock']; ?>" 
                     data-current-stock="<?php echo $item['stock']; ?>">
                    <img src="<?php echo $item['image_url']; ?>" alt="<?php echo $item['name']; ?>">
                    <div>
                        <h3><?php echo $item['name']; ?></h3>
                        <p>₹<?php echo number_format($item['price'], 2); ?>/<?php echo ($item['category'] === 'milk' ? 'liter' : 'kg'); ?></p>
                        <p class="seller-info">Seller: <?php echo $item['seller_name']; ?></p>
                        <p class="stock-info">Available: <?php echo $item['stock']; ?> <?php echo ($item['category'] === 'milk' ? 'liters' : 'kg'); ?></p>
                    </div>
                    <div class="quantity-controls">
                        <button class="quantity-btn decrease-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'decrease')" 
                                <?php echo ($item['quantity'] <= $item['minimum_qty'] ? 'disabled' : ''); ?>>-</button>
                        <span class="quantity-display"><?php echo $item['quantity']; ?></span>
                        <button class="quantity-btn increase-btn" onclick="updateQuantity(<?php echo $item['id']; ?>, 'increase')"
                                <?php echo ($item['quantity'] >= $item['stock'] ? 'disabled' : ''); ?>>+</button>
                        <span class="stock-info">(Available: <?php echo $item['stock']; ?>)</span>
                    </div>
                    <div class="item-total">₹<?php echo number_format($item['total'], 2); ?></div>
                    <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            <?php endforeach; ?>

            <div class="cart-summary">
                <h2>Cart Summary</h2>
                <div style="margin: 1rem 0;">
                    <strong>Total: ₹<span id="cart-total"><?php echo number_format($cartTotal, 2); ?></span></strong>
                </div>
                <?php if (!empty($cartItems)): ?>
                    <button class="checkout-btn" onclick="proceedToCheckout()">
                        Proceed to Checkout
                    </button>
                <?php else: ?>
                    <div class="empty-cart">
                        <h2>Your cart is empty</h2>
                        <p>Add some products to your cart and they will appear here</p>
                        <a href="sale.php" class="continue-shopping-btn">Continue Shopping</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Add some products to your cart and they will appear here</p>
                <a href="sale.php" style="display: inline-block; margin-top: 1rem; color: var(--primary);">
                    Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateQuantity(productId, action) {
            const cartItem = document.querySelector(`[data-product-id="${productId}"]`);
            const minQty = 1; // Minimum quantity is 1
            const availableStock = parseInt(cartItem.dataset.stock);
            const quantityDisplay = cartItem.querySelector('.quantity-display');
            const currentQty = parseInt(quantityDisplay.textContent);
            const decreaseBtn = cartItem.querySelector('.decrease-btn');
            const increaseBtn = cartItem.querySelector('.increase-btn');
            
            // Make sure action is one of the expected values
            const validAction = action === 'increase' ? 'add' : (action === 'decrease' ? 'remove' : action);
            
            if (validAction === 'remove' && currentQty <= minQty) {
                // Don't allow decrease below minimum quantity (1)
                return;
            }
            
            if (validAction === 'add' && currentQty >= availableStock) {
                // Don't allow increase above available stock
                alert(`Sorry, only ${availableStock} units of this product are available in stock.`);
                return;
            }
            
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('action', validAction);  // Use the validated action
            
            fetch('cart_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update quantity display
                    const newQty = validAction === 'add' ? currentQty + 1 : currentQty - 1;
                    quantityDisplay.textContent = newQty;
                    
                    // Update item total
                    const priceText = cartItem.querySelector('p').textContent;
                    const price = parseFloat(priceText.replace('₹', '').replace(/,/g, ''));
                    const totalElement = cartItem.querySelector('.item-total');
                    const newTotal = newQty * price;
                    totalElement.textContent = '₹' + newTotal.toFixed(2);
                    
                    // Update cart total
                    updateCartTotal();
                    
                    // Update button states
                    decreaseBtn.disabled = newQty <= minQty;
                    increaseBtn.disabled = newQty >= availableStock;
                } else {
                    alert(data.error || 'Failed to update quantity');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update quantity');
            });
        }

        function updateCartTotal() {
            // Calculate new cart total from all item totals
            let newCartTotal = 0;
            document.querySelectorAll('.item-total').forEach(item => {
                const itemTotal = parseFloat(item.textContent.replace('₹', '').replace(/,/g, ''));
                newCartTotal += itemTotal;
            });
            
            // Update the cart total display
            document.getElementById('cart-total').textContent = newCartTotal.toFixed(2);
        }

        // Update this function to properly manage quantity limits
        document.querySelectorAll('.quantity-btn').forEach(btn => {
            const cartItem = btn.closest('.cart-item');
            const availableStock = parseInt(cartItem.dataset.stock);
            const currentQty = parseInt(cartItem.querySelector('.quantity-display').textContent);
            
            if (btn.classList.contains('decrease-btn')) {
                btn.disabled = currentQty <= 1; // Can't go below 1
            } else if (btn.classList.contains('increase-btn')) {
                btn.disabled = currentQty >= availableStock; // Can't exceed available stock
            }
        });

        function removeItem(productId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                const data = new FormData();
                data.append('product_id', productId);
                data.append('action', 'remove');

                fetch('cart_actions.php', {
                    method: 'POST',
                    body: data
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.error || 'Failed to remove item.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to remove item.');
                });
            }
        }

        function proceedToCheckout() {
            // Validate cart items before proceeding
            const cartItems = document.querySelectorAll('.cart-item');
            if (cartItems.length === 0) {
                alert('Your cart is empty!');
                return;
            }

            // Proceed directly to checkout
            window.location.href = 'checkout.php';
        }
    </script>
</body>
</html>

<?php 
$conn->close(); 
?>
