<?php
require_once 'session_config.php';
initSession();

// Redirect to template.php if already logged in
if (isset($_SESSION['id']) && isSessionValid()) {
    header("Location: template.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to AgroFresh</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #22c55e;
            --primary-dark: #16a34a;
            --secondary: #0ea5e9;
            --accent: #f59e0b;
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
            overflow-x: hidden;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1.25rem;
            position: fixed;
            width: 100%;
            z-index: 1000;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .nav {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 2rem;
        }

        .logo {
            font-size: 2rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            gap: 3.5rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .login-button {
            background: var(--gradient);
            color: white;
            padding: 0.9rem 2.2rem;
            border-radius: 100px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(34, 197, 94, 0.2);
        }

        .login-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(34, 197, 94, 0.3);
        }

        .hero {
            min-height: 100vh;
            background: radial-gradient(circle at 10% 20%, rgba(34, 197, 94, 0.1), transparent),
                        radial-gradient(circle at 90% 80%, rgba(14, 165, 233, 0.1), transparent);
            display: flex;
            align-items: center;
        }

        .hero-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-text {
            animation: slideIn 1s ease-out;
        }

        .hero-text h1 {
            font-size: 4.5rem;
            line-height: 1.1;
            font-weight: 800;
            margin-bottom: 2rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero-text p {
            font-size: 1.25rem;
            color: #64748b;
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        .cta-button {
            background: var(--gradient);
            color: white;
            padding: 1.25rem 2.5rem;
            border-radius: 100px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(34, 197, 94, 0.2);
        }

        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(34, 197, 94, 0.3);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (max-width: 1024px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .hero-text h1 {
                font-size: 3.5rem;
            }
        }

        @media (max-width: 768px) {
            .hero-text h1 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <div class="logo">𝑨𝑮𝑹𝑶 𝑭𝑹𝑬𝑺𝑯</div>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="about.html">About</a>
                <a href="login.php" class="login-button">
                    <i class="fas fa-user"></i>
                    Login
                </a>
            </div>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Welcome to AgroFresh</h1>
                <p>Join our innovative agricultural marketplace platform. Connect with farmers, suppliers, and buyers worldwide. Experience secure trading and smart logistics.</p>
                <a href="signup.php" class="cta-button">Sign Up Now</a>
            </div>
        </div>
    </section>

    <script>
        document.querySelector('.cta-button').addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = 'signup.php';
        });
    </script>
</body>
</html>
