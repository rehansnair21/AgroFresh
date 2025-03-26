<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get cart count if not already set
if (!isset($cartCount)) {
    require_once 'db_connection.php';
    
    $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : session_id();
    $is_guest = !isset($_SESSION['id']);
    
    $cart_query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ? AND is_guest = ?";
    $stmt = $conn->prepare($cart_query);
    $stmt->bind_param("si", $user_id, $is_guest);
    $stmt->execute();
    $result = $stmt->get_result();
    $cartCount = $result->fetch_assoc()['count'];
    $stmt->close();
}
?>

<header class="header">
    <nav class="nav">
        <div class="logo">
            <a href="template.php" style="text-decoration: none;">
                <span class="logo-text">AgroFresh</span>
            </a>
        </div>

        <div class="nav-links">
            <a href="template.php" class="nav-link">Home</a>
            <a href="sale.php" class="nav-link">Products</a>
            
            <?php if (isset($_SESSION['id'])): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="admin_dashboard.php" class="nav-link">Admin Dashboard</a>
                <?php elseif ($_SESSION['role'] === 'delivery'): ?>
                    <a href="delivery_dashboard.php" class="nav-link">Delivery Dashboard</a>
                <?php endif; ?>
                
                <div class="user-menu">
                    <button class="user-menu-btn">
                        <div class="user-profile">
                            <?php if (isset($_SESSION['id'])): ?>
                                <div class="profile-picture">
                                    <img src="<?php echo !empty($_SESSION['profile_picture']) ? htmlspecialchars($_SESSION['profile_picture']) : 'images/default-profile.png'; ?>" 
                                         alt="Profile Picture"
                                         class="profile-picture"
                                         onerror="this.src='images/default-profile.png';">
                                </div>
                            <?php endif; ?>
                        </div>
                        <span><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                        <a href="my_orders.php">
                            <i class="fas fa-shopping-bag"></i> My Orders
                        </a>
                        <?php if ($_SESSION['role'] === 'seller'): ?>
                            <a href="seller_dashboard.php">
                                <i class="fas fa-store"></i> Seller Dashboard
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="nav-link">Login</a>
                <a href="signup.php" class="nav-link">Sign Up</a>
                <a href="seller_register.php" class="become-seller-btn">Become a Seller</a>
            <?php endif; ?>

            <a href="cart.php" class="cart-button">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count"><?php echo $cartCount; ?></span>
            </a>
        </div>

        <button class="mobile-menu-btn">
            <i class="fas fa-bars"></i>
        </button>
    </nav>
</header>

<style>
    .header {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .nav {
        max-width: 1400px;
        margin: 0 auto;
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        font-size: 1.5rem;
        font-weight: 800;
    }

    .logo-text {
        background: linear-gradient(135deg, #22c55e, #0ea5e9);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .nav-link {
        color: #1f2937;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .nav-link:hover {
        color: #22c55e;
    }

    .become-seller-btn {
        background: #22c55e;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .become-seller-btn:hover {
        background: #16a34a;
    }

    .cart-button {
        position: relative;
        background: linear-gradient(135deg, #22c55e, #0ea5e9);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: transform 0.3s ease;
    }

    .cart-button:hover {
        transform: translateY(-2px);
    }

    .cart-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background: #ef4444;
        color: white;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 1rem;
        min-width: 1.5rem;
        text-align: center;
    }

    .user-menu {
        position: relative;
    }

    .user-menu-btn {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        border: none;
        background: none;
        cursor: pointer;
        color: #1f2937;
    }

    .profile-pic {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 0.5rem;
        min-width: 200px;
        display: none;
    }

    .dropdown-menu a {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        color: #1f2937;
        text-decoration: none;
        transition: background-color 0.3s ease;
    }

    .dropdown-menu a:hover {
        background: #f3f4f6;
        border-radius: 0.25rem;
    }

    .logout-btn {
        color: #ef4444 !important;
    }

    .mobile-menu-btn {
        display: none;
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #1f2937;
        cursor: pointer;
    }

    @media (max-width: 768px) {
        .nav-links {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            padding: 1rem;
            flex-direction: column;
            align-items: stretch;
            gap: 1rem;
        }

        .nav-links.active {
            display: flex;
        }

        .mobile-menu-btn {
            display: block;
        }

        .user-menu {
            width: 100%;
        }

        .dropdown-menu {
            position: static;
            box-shadow: none;
            display: none;
        }

        .dropdown-menu.active {
            display: block;
        }
    }

    .profile-picture {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary);
    }

    .profile-picture img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User menu dropdown
    const userMenuBtn = document.querySelector('.user-menu-btn');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    if (userMenuBtn && dropdownMenu) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownMenu.style.display = dropdownMenu.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', function() {
            dropdownMenu.style.display = 'none';
        });
    }

    // Mobile menu
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', function() {
            navLinks.classList.toggle('active');
        });
    }
});
</script> 