<?php
// ... existing code ...

// Get pending deliveries query remains the same

// Get today's completed deliveries query remains the same

// Add this new query for all user orders
$orders_query = "SELECT o.*, 
                u.full_name as customer_name,
                u.phone as customer_phone,
                GROUP_CONCAT(CONCAT(p.name, ' (', oi.quantity, ')') SEPARATOR ', ') as order_items,
                COUNT(DISTINCT oi.id) as item_count,
                SUM(oi.quantity * oi.price) as total_amount
                FROM orders o 
                JOIN users u ON o.user_id = u.id
                LEFT JOIN order_items oi ON o.id = oi.order_id
                LEFT JOIN products p ON oi.product_id = p.id
                GROUP BY o.id
                ORDER BY o.created_at DESC";

$orders_result = $conn->query($orders_query);

// Check if query was successful
if ($orders_result === false) {
    echo "SQL Error in orders query: " . $conn->error;
    $all_orders = [];
} else {
    $all_orders = $orders_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<!-- HTML head remains the same -->

<body>
    <!-- Header section remains the same -->

    <div class="container">
        <h1 class="page-title">Delivery Dashboard</h1>
        
        <!-- Success/error alerts remain the same -->
        
        <!-- Dashboard stats remain the same -->

        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" data-tab="pending">Pending Deliveries</div>
                <div class="tab" data-tab="completed">Completed Today</div>
                <div class="tab" data-tab="orders">All Orders</div>
            </div>

            <!-- Pending deliveries tab content remains the same -->
            
            <!-- Completed deliveries tab content remains the same -->

            <!-- Add this new tab content for all orders -->
            <div class="tab-content" id="orders-tab">
                <?php if (empty($all_orders)): ?>
                    <div class="no-deliveries">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No orders found</p>
                    </div>
                <?php else: ?>
                    <div class="order-filters">
                        <input type="text" id="orderSearch" placeholder="Search orders..." class="form-control">
                        <select id="statusFilter" class="form-control">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="shipped">Shipped</option>
                            <option value="delivered">Delivered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="orders-list">
                        <?php foreach ($all_orders as $order): ?>
                            <div class="order-card" data-status="<?php echo strtolower($order['status']); ?>">
                                <div class="order-header">
                                    <div class="order-main-info">
                                        <h3>Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                                        <span class="order-date"><?php echo date('M j, Y, g:i A', strtotime($order['created_at'])); ?></span>
                                    </div>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="order-details">
                                    <div class="customer-info">
                                        <h4>Customer Details</h4>
                                        <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                    </div>
                                    
                                    <div class="order-items">
                                        <h4>Order Items</h4>
                                        <div class="items-list">
                                            <?php 
                                            $items = explode(', ', $order['order_items']);
                                            foreach ($items as $item): 
                                            ?>
                                                <div class="item">
                                                    <i class="fas fa-box"></i>
                                                    <?php echo htmlspecialchars($item); ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="order-summary">
                                        <div class="summary-item">
                                            <span>Total Items:</span>
                                            <strong><?php echo $order['item_count']; ?></strong>
                                        </div>
                                        <div class="summary-item">
                                            <span>Total Amount:</span>
                                            <strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="order-actions">
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn btn-info">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    
                                    <?php if ($order['status'] === 'processing'): ?>
                                        <button class="btn btn-primary update-status" 
                                                data-order-id="<?php echo $order['id']; ?>"
                                                data-status="shipped">
                                            <i class="fas fa-shipping-fast"></i> Mark as Shipped
                                        </button>
                                    <?php elseif ($order['status'] === 'shipped'): ?>
                                        <button class="btn btn-primary update-status" 
                                                data-order-id="<?php echo $order['id']; ?>"
                                                data-status="delivered">
                                            <i class="fas fa-check-circle"></i> Mark as Delivered
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status Update Modal remains the same -->

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation remains the same
            
            // Modal handling remains the same
            
            // Add this new code for order filtering
            const orderSearch = document.getElementById('orderSearch');
            const statusFilter = document.getElementById('statusFilter');
            
            if (orderSearch && statusFilter) {
                function filterOrders() {
                    const searchTerm = orderSearch.value.toLowerCase();
                    const statusValue = statusFilter.value.toLowerCase();
                    
                    const orderRows = document.querySelectorAll('.orders-list .order-card');
                    
                    orderRows.forEach(row => {
                        const rowText = row.textContent.toLowerCase();
                        const rowStatus = row.dataset.status;
                        
                        const matchesSearch = searchTerm === '' || rowText.includes(searchTerm);
                        const matchesStatus = statusValue === '' || rowStatus === statusValue;
                        
                        if (matchesSearch && matchesStatus) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                }
                
                orderSearch.addEventListener('input', filterOrders);
                statusFilter.addEventListener('change', filterOrders);
            }
        });
    </script>
    
    <style>
        /* Add these styles to your existing styles */
        .order-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1rem;
            flex: 1;
        }
        
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .order-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 1.5rem;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .order-main-info h3 {
            font-size: 1.25rem;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .order-date {
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .customer-info h4,
        .order-items h4 {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }
        
        .customer-info p {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .items-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: #f8fafc;
            border-radius: 0.5rem;
        }
        
        .order-summary {
            display: flex;
            gap: 2rem;
        }
        
        .summary-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .order-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-processing { background: #e0f2fe; color: #0369a1; }
        .status-shipped { background: #f0fdf4; color: #166534; }
        .status-delivered { background: #dcfce7; color: #166534; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
    </style>
</body>
</html>

