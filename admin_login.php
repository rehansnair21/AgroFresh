<?php
session_start();

// If already logged in, redirect to appropriate page
if (isset($_SESSION['id'])) {
    $redirect = isset($_SESSION['role']) && $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'template.php';
    header("Location: $redirect");
    exit();
}

require_once 'db_connection.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['admin_email'];
    $password = $_POST['admin_password'];
    
    // Get user with admin credentials
    $stmt = $conn->prepare("SELECT id, full_name, admin_email, admin_password, role, is_admin FROM users 
                           WHERE admin_email = ? AND role = 'admin'");
    
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $admin['admin_password'])) {
                // Set session variables
                $_SESSION['id'] = $admin['id'];
                $_SESSION['name'] = $admin['full_name'];
                $_SESSION['email'] = $admin['admin_email'];
                $_SESSION['role'] = 'admin';
                $_SESSION['is_admin'] = 1;
                
                header("Location: admin_dashboard.php");
                exit();
            }
        }
        
        $error_message = "Invalid admin email or password. Please try again.";
        $stmt->close();
    } else {
        $error_message = "Database error. Please try again later.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AgroFresh</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --dark: #0f172a;
            --light: #f8fafc;
            --gradient: linear-gradient(135deg, #22c55e, #0ea5e9);
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
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 400px;
            background-color: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        }

        .title {
            text-align: center;
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 2rem;
        }

        .admin-badge {
            background: var(--gradient);
            color: white;
            padding: 0.4rem 1rem;
            border-radius: 100px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .error-message {
            background-color: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            color: #9ca3af;
        }

        .input-wrapper input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            outline: none;
        }

        .show-password {
            position: absolute;
            right: 1rem;
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
        }

        .login-button {
            width: 100%;
            padding: 1rem;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
        }

        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(34, 197, 94, 0.2);
        }

        .login-button:active {
            transform: translateY(0);
            box-shadow: none;
        }

        .login-link {
            text-align: center;
            margin-bottom: 1rem;
        }

        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }

        .home-button {
            position: absolute;
            top: 2rem;
            left: 2rem;
            background: white;
            color: var(--dark);
            padding: 0.75rem 1.5rem;
            border-radius: 100px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .home-button:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <a href="guest.php" class="home-button">
        <i class="fas fa-home"></i> Home
    </a>

    <div class="container">
        <div class="admin-badge">Admin Portal</div>
        <div class="title">Admin Login</div>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <div class="input-wrapper">
                    <input type="email" placeholder="Admin Email" name="admin_email" required>
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <div class="input-wrapper">
                    <input type="password" name="admin_password" placeholder="Admin Password" required id="password">
                    <i class="fas fa-lock input-icon"></i>
                </div>
            </div>

            <button type="submit" class="login-button">Login</button>
        </form>
    </div>
</body>
</html>
