<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        :root {
            --primary: #4a6cf7;
            --primary-dark: #3955d1;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --sidebar-width: 250px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f5f7fb;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            background-color: #2c3e50;
            color: white;
            position: fixed;
            height: 100%;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu {
            padding: 20px 0;
        }
        
        .menu-item {
            padding: 12px 25px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
        }
        
        .menu-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .menu-item.active {
            background-color: var(--primary);
        }
        
        .menu-item i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all 0.3s;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .header-title h1 {
            font-size: 1.5rem;
            color: #333;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
        }
        
        .user-profile .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            display: flex;
            align-items: center;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
            color: white;
        }
        
        .user-icon { background-color: var(--primary); }
        .seller-icon { background-color: var(--success); }
        .product-icon { background-color: var(--warning); }
        .revenue-icon { background-color: var(--info); }
        
        .stat-info h3 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .stat-info p {
            color: var(--secondary);
            font-size: 0.9rem;
        }
        
        .panel {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .panel-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .panel-title {
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .data-table tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.02);
        }
        
        .btn {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .btn-action {
            margin-right: 5px;
        }
        
        .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
            cursor: pointer;
        }
        
        .edit-btn {
            background-color: var(--warning);
            color: white;
        }
        
        .delete-btn {
            background-color: var(--danger);
            color: white;
        }
        
        .view-btn {
            background-color: var(--info);
            color: white;
        }
        
        .search-container {
            display: flex;
            margin-bottom: 15px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 4px 0 0 4px;
            outline: none;
        }
        
        .search-btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .search-btn:hover {
            background-color: var(--primary-dark);
        }
        
        .pagination {
            display: flex;
            justify-content: flex-end;
            padding: 15px;
        }
        
        .page-link {
            padding: 5px 10px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            margin-right: 5px;
            text-decoration: none;
            color: var(--primary);
            border-radius: 4px;
        }
        
        .page-link.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .tab-container {
            display: none;
        }
        
        .tab-container.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            outline: none;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            width: 500px;
            max-width: 90%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .close-btn {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--secondary);
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            padding-top: 15px;
            border-top: 1px solid rgba(0, 0, 0, 0.1);
            margin-top: 15px;
        }
        
        .badge {
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: white;
            display: inline-block;
        }
        
        .badge-success { background-color: var(--success); }
        .badge-danger { background-color: var(--danger); }
        .badge-warning { background-color: var(--warning); }
        .badge-info { background-color: var(--info); }
        
        /* Dashboard specific */
        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .chart-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            min-height: 300px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 1000;
            }
            
            .content {
                margin-left: 0;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .mobile-toggle {
                display: block;
            }
            
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(100%, 1fr));
            }
            
            .charts-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <div class="sidebar-menu">
            <div class="menu-item active" data-tab="dashboard">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </div>
            <div class="menu-item" data-tab="users">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </div>
            <div class="menu-item" data-tab="sellers">
                <i class="fas fa-store"></i>
                <span>Sellers</span>
            </div>
            <div class="menu-item" data-tab="products">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </div>
            <div class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="header">
            <div class="header-title">
                <h1>Dashboard</h1>
            </div>
            <div class="user-profile">
                <div class="avatar">
                    <i class="fas fa-user"></i>
                </div>
                <div class="user-info">
                    <h3>Admin</h3>
                </div>
            </div>
        </div>

        <!-- Dashboard -->
        <div class="tab-container active" id="dashboard">
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon user-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>156</h3>
                        <p>Total Users</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon seller-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <div class="stat-info">
                        <h3>43</h3>
                        <p>Total Sellers</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon product-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="stat-info">
                        <h3>378</h3>
                        <p>Total Products</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon revenue-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="stat-info">
                        <h3>$24,500</h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </div>

            <div class="charts-container">
                <div class="chart-container">
                    <div class="panel-header">
                        <div class="panel-title">User Growth</div>
                    </div>
                    <canvas id="userGrowthChart"></canvas>
                </div>
                <div class="chart-container">
                    <div class="panel-header">
                        <div class="panel-title">Revenue Overview</div>
                    </div>
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">Recent Products</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addProductModal')">Add New</button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Seller</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Smartphone X</td>
                            <td>Electronics</td>
                            <td>$899.99</td>
                            <td>Tech Solutions</td>
                            <td>45</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Laptop Pro</td>
                            <td>Electronics</td>
                            <td>$1299.99</td>
                            <td>Tech Solutions</td>
                            <td>20</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Designer Watch</td>
                            <td>Fashion</td>
                            <td>$199.99</td>
                            <td>Fashion Hub</td>
                            <td>15</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Users -->
        <div class="tab-container" id="users">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">All Users</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addUserModal')">Add User</button>
                </div>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search users...">
                    <button class="search-btn"><i class="fas fa-search"></i></button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>State</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>John Doe</td>
                            <td>john@example.com</td>
                            <td>+1234567890</td>
                            <td>California</td>
                            <td>12 Jan 2023</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Jane Smith</td>
                            <td>jane@example.com</td>
                            <td>+1987654321</td>
                            <td>New York</td>
                            <td>18 Feb 2023</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Robert Johnson</td>
                            <td>robert@example.com</td>
                            <td>+1122334455</td>
                            <td>Texas</td>
                            <td>22 Mar 2023</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Sarah Williams</td>
                            <td>sarah@example.com</td>
                            <td>+1223344556</td>
                            <td>Florida</td>
                            <td>05 Apr 2023</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="pagination">
                    <a href="#" class="page-link active">1</a>
                    <a href="#" class="page-link">2</a>
                    <a href="#" class="page-link">3</a>
                    <a href="#" class="page-link">Next</a>
                </div>
            </div>
        </div>

        <!-- Sellers -->
        <div class="tab-container" id="sellers">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">All Sellers</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addSellerModal')">Add Seller</button>
                </div>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search sellers...">
                    <button class="search-btn"><i class="fas fa-search"></i></button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Seller ID</th>
                            <th>User ID</th>
                            <th>Seller Name</th>
                            <th>Location</th>
                            <th>Registered</th>
                            <th>Products</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>101</td>
                            <td>Tech Solutions</td>
                            <td>San Francisco, CA</td>
                            <td>10 Jan 2023</td>
                            <td>65</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>102</td>
                            <td>Fashion Hub</td>
                            <td>New York, NY</td>
                            <td>15 Jan 2023</td>
                            <td>42</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>103</td>
                            <td>Home Essentials</td>
                            <td>Chicago, IL</td>
                            <td>20 Jan 2023</td>
                            <td>31</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="pagination">
                    <a href="#" class="page-link active">1</a>
                    <a href="#" class="page-link">2</a>
                    <a href="#" class="page-link">Next</a>
                </div>
            </div>
        </div>

        <!-- Products -->
        <div class="tab-container" id="products">
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-title">All Products</div>
                    <button class="btn btn-primary btn-sm" onclick="openModal('addProductModal')">Add Product</button>
                </div>
                <div class="search-container">
                    <input type="text" class="search-input" placeholder="Search products...">
                    <button class="search-btn"><i class="fas fa-search"></i></button>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Seller</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>Smartphone X</td>
                            <td>Electronics</td>
                            <td>$899.99</td>
                            <td>Tech Solutions</td>
                            <td>45</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Laptop Pro</td>
                            <td>Electronics</td>
                            <td>$1299.99</td>
                            <td>Tech Solutions</td>
                            <td>20</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>3</td>
                            <td>Designer Watch</td>
                            <td>Fashion</td>
                            <td>$199.99</td>
                            <td>Fashion Hub</td>
                            <td>15</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>4</td>
                            <td>Coffee Maker</td>
                            <td>Home Appliances</td>
                            <td>$89.99</td>
                            <td>Home Essentials</td>
                            <td>30</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                        <tr>
                            <td>5</td>
                            <td>Wireless Earbuds</td>
                            <td>Electronics</td>
                            <td>$149.99</td>
                            <td>Tech Solutions</td>
                            <td>50</td>
                            <td>
                                <div class="action-btn view-btn"><i class="fas fa-eye"></i></div>
                                <div class="action-btn edit-btn"><i class="fas fa-edit"></i></div>
                                <div class="action-btn delete-btn"><i class="fas fa-trash"></i></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="pagination">
                    <a href="#" class="page-link active">1</a>
                    <a href="#" class="page-link">2</a>
                    <a href="#" class="page-link">3</a>
                    <a href="#"