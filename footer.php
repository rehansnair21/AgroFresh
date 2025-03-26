<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>About AgroFresh</h3>
            <p>AgroFresh is your trusted source for fresh agricultural products, connecting farmers directly with consumers.</p>
        </div>
        
        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="about.php">About Us</a></li>
                <li><a href="contact.php">Contact</a></li>
            </ul>
        </div>
        
        <div class="footer-section">
            <h3>Contact Us</h3>
            <p>
                <i class="fas fa-phone"></i> +91 1234567890<br>
                <i class="fas fa-envelope"></i> info@agrofresh.com<br>
                <i class="fas fa-map-marker-alt"></i> Kerala, India
            </p>
        </div>
        
        <div class="footer-section">
            <h3>Follow Us</h3>
            <div class="social-links">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-linkedin"></i></a>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y'); ?> AgroFresh. All rights reserved.</p>
    </div>
</footer>

<style>
.footer {
    background-color: #1a1a1a;
    color: #ffffff;
    padding: 3rem 0 1rem 0;
    margin-top: 3rem;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    padding: 0 1rem;
}

.footer-section {
    margin-bottom: 1.5rem;
}

.footer-section h3 {
    color: #4CAF50;
    margin-bottom: 1rem;
    font-size: 1.2rem;
}

.footer-section ul {
    list-style: none;
    padding: 0;
}

.footer-section ul li {
    margin-bottom: 0.5rem;
}

.footer-section a {
    color: #ffffff;
    text-decoration: none;
    transition: color 0.3s ease;
}

.footer-section a:hover {
    color: #4CAF50;
}

.social-links {
    display: flex;
    gap: 1rem;
}

.social-links a {
    color: #ffffff;
    font-size: 1.5rem;
    transition: color 0.3s ease;
}

.social-links a:hover {
    color: #4CAF50;
}

.footer-bottom {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #333;
}

.footer i {
    margin-right: 0.5rem;
    color: #4CAF50;
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
    }
    
    .footer-section {
        text-align: center;
    }
    
    .social-links {
        justify-content: center;
    }
}
</style> 