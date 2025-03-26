<?php
session_start();
require_once 'db_connection.php';

// Debug logging
error_log("Admin Dashboard Access - Session Data:");
error_log("Session ID: " . session_id());
error_log("User ID: " . ($_SESSION['id'] ?? 'not set'));
error_log("Role: " . ($_SESSION['role'] ?? 'not set'));
error_log("Is Admin: " . ($_SESSION['is_admin'] ?? 'not set'));
error_log("Email: " . ($_SESSION['email'] ?? 'not set'));

// Check if user is logged in and is an admin
if (!isset($_SESSION['id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin' || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    error_log("Admin access denied - Invalid session data");
    header('Location: login.php');
    exit();
}

// Verify admin credentials from memory 3988d44a
if ($_SESSION['email'] !== 'agrofresh.admin@gmail.com') {
    error_log("Admin access denied - Invalid admin email");
    header('Location: login.php');
    exit();
}

error_log("Admin access granted - Proceeding to dashboard");

// Fetch statistics
$stats = array();

// Initialize default values
$stats['total_users'] = 0;
$stats['total_sellers'] = 0;
$stats['total_products'] = 0;
$stats['total_orders'] = 0;

// Total users (excluding admins)
$users_query = "SELECT COUNT(*) as total_users FROM users WHERE (role != 'admin' AND is_admin != 1)";
$result = $conn->query($users_query);
if ($result && $result->num_rows > 0) {
    $stats['total_users'] = $result->fetch_assoc()['total_users'];
}

// Total sellers
$sellers_query = "SELECT COUNT(*) as total_sellers FROM sellerdetails";
$result = $conn->query($sellers_query);
if ($result && $result->num_rows > 0) {
    $stats['total_sellers'] = $result->fetch_assoc()['total_sellers'];
}

// Total products
$products_query = "SELECT COUNT(*) as total_products FROM products";
$result = $conn->query($products_query);
if ($result && $result->num_rows > 0) {
    $stats['total_products'] = $result->fetch_assoc()['total_products'];
}

// Total orders
$orders_query = "SELECT COUNT(*) as total_orders FROM orders";
$result = $conn->query($orders_query);
if ($result && $result->num_rows > 0) {
    $stats['total_orders'] = $result->fetch_assoc()['total_orders'];
} else {
    $stats['total_orders'] = 0;
}

// Recent users
$recent_users_result = null;
$recent_users_query = "SELECT * FROM users WHERE (role != 'admin' AND is_admin != 1) ORDER BY id DESC LIMIT 5";
$recent_users_result = $conn->query($recent_users_query);

// Recent sellers with detailed information
$recent_sellers_result = null;
$recent_sellers_query = "SELECT 
                            s.*,
                            (SELECT COUNT(*) FROM products p 
                             WHERE p.seller_id = s.id) as product_count
                        FROM sellerdetails s 
                        ORDER BY s.id DESC";
$recent_sellers_result = $conn->query($recent_sellers_query);

if ($conn->error) {
    echo "Error in seller query: " . $conn->error;
}

// Recent orders with details
$recent_orders_result = null;
$recent_orders_query = "SELECT o.*, u.full_name as customer_name, u.email as customer_email,
                              COUNT(oi.id) as total_items,
                              GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as items
                       FROM orders o
                       LEFT JOIN users u ON o.user_id = u.id
                       LEFT JOIN order_items oi ON o.id = oi.order_id
                       LEFT JOIN products p ON oi.product_id = p.id
                       GROUP BY o.id
                       ORDER BY o.id DESC
                       LIMIT 10";
$recent_orders_result = $conn->query($recent_orders_query);

if ($conn->error) {
    error_log("Error in orders query: " . $conn->error);
}

// Recent products with seller info
$recent_products_result = null;
$recent_products_query = "SELECT p.*, 
                                s.full_name as seller_name
                         FROM products p 
                         LEFT JOIN sellerdetails s ON p.seller_id = s.id 
                         ORDER BY p.id DESC 
                         LIMIT 10";
$recent_products_result = $conn->query($recent_products_query);

// Check for database errors
if ($conn->error) {
    die("Database error: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #43cea2;
            --secondary: #185a9d;
            --dark: #1e293b;
            --light: #f1f5f9;
            --gradient: linear-gradient(135deg, #43cea2, #185a9d);
            --sidebar-width: 250px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: var(--light);
            min-height: 100vh;
            display: flex;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: white;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 2rem 1rem;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            z-index: 1000;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .nav-links {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            flex-grow: 1;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            text-decoration: none;
            color: var(--dark);
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .nav-link i {
            font-size: 1.25rem;
            width: 24px;
            text-align: center;
        }

        .nav-link:hover, .nav-link.active {
            background: var(--gradient);
            color: white;
            transform: translateX(5px);
        }

        .nav-link span {
            font-weight: 500;
        }

        .container {
            flex-grow: 1;
            margin-left: var(--sidebar-width);
            padding: 2rem;
            max-width: calc(100% - var(--sidebar-width));
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 2rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #64748b;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .dashboard-card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card h2 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
        }

        .user-list, .product-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .user-item, .product-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: var(--light);
            border-radius: 0.5rem;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .user-info, .product-info {
            flex-grow: 1;
        }

        .user-info h3, .product-info h3 {
            color: var(--dark);
            margin-bottom: 0.25rem;
        }

        .user-info p, .product-info p {
            color: #64748b;
            font-size: 0.9rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logout-btn {
            margin-top: auto;
            padding: 1rem;
            background: linear-gradient(135deg, #ff6b6b, #ee5253);
            color: white;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .seller-details, .seller-stats {
            display: flex;
            gap: 1rem;
            margin-top: 0.25rem;
        }

        .seller-details span, .seller-stats span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        .seller-details i {
            color: #ee5253;
        }

        .seller-stats i {
            color: #43cea2;
        }

        .user-info p {
            margin: 0.25rem 0;
        }

        .content-section {
            display: none;
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .content-section.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
            animation: fadeIn 0.3s ease forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .seller-stats span {
            margin-right: 15px;
            color: #666;
            font-size: 0.9em;
        }
        
        .seller-stats .fa-check-circle {
            color: #28a745;
        }
        
        .seller-stats .fa-hourglass-half {
            color: #ffc107;
        }
        
        .product-info p {
            margin: 5px 0;
            color: #666;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            flex: 1;
            min-width: 200px;
            text-align: center;
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card i {
            font-size: 2em;
            margin-bottom: 10px;
            color: #4CAF50;
        }
        
        .stat-card h3 {
            font-size: 1.8em;
            margin: 10px 0;
            color: #333;
        }
        
        .stat-card p {
            color: #666;
            margin: 0;
        }
        
        .stat-card .fa-hourglass-half {
            color: #ffc107;
        }
        
        .stat-card .fa-check-circle {
            color: #28a745;
        }
        
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 20px;
            justify-content: space-between;
        }

        .table-responsive {
            overflow-x: auto;
            margin-top: 1rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
            background-color: white;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--light);
        }

        .table th {
            background-color: var(--light);
            font-weight: 600;
            color: var(--dark);
        }

        .table tr:hover {
            background-color: #f8f9fa;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .text-center {
            text-align: center;
        }

        .badge {
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            color: white;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .badge-info {
            background-color: #17a2b8;
        }

        .badge-secondary {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>AgroFresh</h2>
            <p>Admin Panel</p>
        </div>
        <nav class="nav-links">
            <a href="#dashboard-section" class="nav-link active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="#users-section" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="#sellers-section" class="nav-link">
                <i class="fas fa-store"></i>
                <span>Sellers</span>
            </a>
            <a href="#products-section" class="nav-link">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
            <a href="#orders-section" class="nav-link">
                <i class="fas fa-shopping-cart"></i>
                <span>Orders</span>
            </a>
            <a href="#settings-section" class="nav-link">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
        </nav>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </aside>

    <div class="container">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <p>Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>!</p>
        </div>

        <!-- Dashboard Section -->
        <section id="dashboard-section" class="content-section active">
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-store"></i>
                    <h3><?php echo $stats['total_sellers']; ?></h3>
                    <p>Total Sellers</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-box"></i>
                    <h3><?php echo $stats['total_products']; ?></h3>
                    <p>Total Products</p>
                </div>
                <div class="stat-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3><?php echo $stats['total_orders'] ?? 0; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
        </section>

        <!-- Users Section -->
        <section id="users-section" class="content-section">
            <div class="dashboard-card">
                <h2>Users Management</h2>
                <div class="user-list">
                    <?php if ($recent_users_result && $recent_users_result->num_rows > 0): ?>
                        <?php while($user = $recent_users_result->fetch_assoc()): ?>
                            <div class="user-item" data-user-id="<?php echo $user['id']; ?>">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['full_name'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div class="user-info">
                                    <h3><?php echo htmlspecialchars($user['full_name'] ?? 'Unknown User'); ?></h3>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email'] ?? 'No email'); ?></p>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['mobile'] ?? 'No phone'); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($user['address'] ?? 'No address'); ?></p>
                                    <p><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($user['state'] ?? 'No state'); ?> - <?php echo htmlspecialchars($user['pincode'] ?? 'No pincode'); ?></p>
                                    <div class="user-stats">
                                        <span><i class="fas fa-calendar"></i> Joined: <?php echo date('M d, Y', strtotime($user['created_at'] ?? 'now')); ?></span>
                                        <span><i class="fas fa-shopping-cart"></i> Orders: <?php echo (int)($user['order_count'] ?? 0); ?></span>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-primary" onclick="viewUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No users found</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Sellers Section -->
        <section id="sellers-section" class="content-section">
            <div class="dashboard-card">
                <h2>Sellers Management</h2>
                <div class="user-list">
                    <?php 
                    if ($recent_sellers_result && $recent_sellers_result->num_rows > 0): 
                        while($seller = $recent_sellers_result->fetch_assoc()): 
                    ?>
                            <div class="user-item" data-seller-id="<?php echo $seller['id']; ?>">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($seller['full_name'] ?? 'S', 0, 1)); ?>
                                </div>
                                <div class="user-info">
                                    <h3><?php echo htmlspecialchars($seller['full_name'] ?? 'Unknown Seller'); ?></h3>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($seller['email'] ?? 'No email'); ?></p>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($seller['mobile'] ?? 'No phone'); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($seller['address'] ?? 'No address'); ?></p>
                                    <p><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($seller['state'] ?? 'No state'); ?> - <?php echo htmlspecialchars($seller['pincode'] ?? 'No pincode'); ?></p>
                                    <div class="seller-stats">
                                        <span><i class="fas fa-box"></i> <?php echo (int)($seller['product_count'] ?? 0); ?> Products</span>
                                    </div>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-primary" onclick="viewSeller(<?php echo $seller['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="deleteSeller(<?php echo $seller['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                    <?php 
                        endwhile;
                    else: 
                    ?>
                        <p>No sellers found</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Products Section -->
        <section id="products-section" class="content-section">
            <div class="dashboard-card">
                <h2>Products Management</h2>
                <div class="product-list">
                    <?php if ($recent_products_result && $recent_products_result->num_rows > 0): ?>
                        <?php while($product = $recent_products_result->fetch_assoc()): ?>
                            <div class="product-item" data-product-id="<?php echo $product['id']; ?>">
                                <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'placeholder.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>"
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 0.5rem;">
                                <div class="product-info">
                                    <h3><?php echo htmlspecialchars($product['name'] ?? 'Unknown Product'); ?></h3>
                                    <p>Seller: <?php echo htmlspecialchars($product['seller_name'] ?? 'Unknown Seller'); ?></p>
                                    <p>Price: ₹<?php echo htmlspecialchars($product['price'] ?? '0'); ?>/kg</p>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn btn-primary" onclick="viewProduct(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p>No products found</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Orders Section -->
        <section id="orders-section" class="content-section">
            <div class="dashboard-card">
                <h2>Orders Management</h2>
                <?php if ($recent_orders_result && $recent_orders_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                    <th>Items</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = $recent_orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                            <small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                        </td>
                                        <td>₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <small><?php echo htmlspecialchars($order['items']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo match($order['status']) {
                                                    'pending' => 'warning',
                                                    'processing' => 'info',
                                                    'completed' => 'success',
                                                    'cancelled' => 'danger',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-primary btn-sm" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-info btn-sm" onclick="updateOrderStatus(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No orders found</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Settings Section -->
        <section id="settings-section" class="content-section">
            <div class="dashboard-card">
                <h2>Settings</h2>
                <p>Settings panel coming soon...</p>
            </div>
        </section>
    </div>

    <script>
        // Sidebar navigation
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            
            // Show initial section based on hash or default to dashboard
            const hash = window.location.hash || '#dashboard-section';
            const initialSection = hash.substring(1);
            showSection(initialSection);
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === hash) {
                    link.classList.add('active');
                }
                
                link.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default anchor behavior
                    
                    // Remove active class from all links
                    navLinks.forEach(l => l.classList.remove('active'));
                    // Add active class to clicked link
                    this.classList.add('active');

                    // Get the section id from href
                    const section = this.getAttribute('href').substring(1);
                    showSection(section);
                    
                    // Update URL hash without scrolling
                    history.pushState(null, null, `#${section}`);
                });
            });
        });

        function showSection(sectionId) {
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(s => {
                s.classList.remove('active');
                // Reset animation
                s.style.animation = 'none';
                s.offsetHeight; // Trigger reflow
                s.style.animation = null;
            });
            
            const activeSection = document.getElementById(sectionId);
            if (activeSection) {
                activeSection.classList.add('active');
                // Update page title
                const sectionName = sectionId.split('-')[0].charAt(0).toUpperCase() + 
                                  sectionId.split('-')[0].slice(1);
                document.title = `${sectionName} - Admin Dashboard`;
            }
        }

        function viewUser(userId) {
            // Implement user view functionality
            console.log('View user:', userId);
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user?')) {
                const formData = new FormData();
                formData.append('user_id', userId);

                fetch('delete_user.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the user element from the DOM
                        const userElement = document.querySelector(`.user-item[data-user-id="${userId}"]`);
                        if (userElement) {
                            userElement.remove();
                        }
                        
                        // Update the total users count
                        const totalUsersElement = document.querySelector('.stat-card:nth-child(1) h3');
                        if (totalUsersElement) {
                            const currentCount = parseInt(totalUsersElement.textContent);
                            totalUsersElement.textContent = currentCount - 1;
                        }
                        
                        alert('User deleted successfully');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting user');
                });
            }
        }

        function viewSeller(sellerId) {
            // Implement seller view functionality
            console.log('View seller:', sellerId);
        }

        function deleteSeller(sellerId) {
            if (confirm('Are you sure you want to delete this seller?')) {
                const formData = new FormData();
                formData.append('seller_id', sellerId);

                fetch('delete_seller.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the seller element from the DOM
                        const sellerElement = document.querySelector(`.user-item[data-seller-id="${sellerId}"]`);
                        if (sellerElement) {
                            sellerElement.remove();
                        }
                        
                        // Update the total sellers count
                        const totalSellersElement = document.querySelector('.stat-card:nth-child(2) h3');
                        if (totalSellersElement) {
                            const currentCount = parseInt(totalSellersElement.textContent);
                            totalSellersElement.textContent = currentCount - 1;
                        }
                        
                        alert('Seller deleted successfully');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting seller');
                });
            }
        }

        function viewProduct(productId) {
            // Implement product view functionality
            console.log('View product:', productId);
        }

        function deleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product?')) {
                const formData = new FormData();
                formData.append('product_id', productId);

                fetch('delete_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the product element from the DOM
                        const productElement = document.querySelector(`[data-product-id="${productId}"]`);
                        if (productElement) {
                            productElement.remove();
                        }
                        
                        // Update the total products count
                        const totalProductsElement = document.querySelector('.stat-card:nth-child(3) h3');
                        if (totalProductsElement) {
                            const currentCount = parseInt(totalProductsElement.textContent);
                            totalProductsElement.textContent = currentCount - 1;
                        }
                        
                        alert('Product deleted successfully');
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting product');
                });
            }
        }

        function viewOrder(orderId) {
            // Implement order view functionality
            console.log('View order:', orderId);
        }

        function updateOrderStatus(orderId) {
            const newStatus = prompt('Enter new status (pending, processing, completed, cancelled):');
            if (!newStatus) return;

            const validStatuses = ['pending', 'processing', 'completed', 'cancelled'];
            if (!validStatuses.includes(newStatus.toLowerCase())) {
                alert('Invalid status. Please use: pending, processing, completed, or cancelled');
                return;
            }

            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', newStatus.toLowerCase());

            fetch('update_order_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Refresh to show updated status
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating order status');
            });
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
