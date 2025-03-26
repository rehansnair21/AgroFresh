<?php
// Generate proper password hash for admin
$adminPassword = 'AgroAdmin@2025';
$hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
echo "Hashed password for admin: " . $hashedPassword;
