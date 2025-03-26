<?php
require_once 'db_connection.php';

// Admin credentials
$admin_email = 'admin@agrofresh.com';
$admin_password = 'admin123';
$admin_name = 'Admin User';

// Hash the password
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);

// First ensure the is_admin column exists
$alter_table = "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin TINYINT(1) DEFAULT 0";
if (!$conn->query($alter_table)) {
    die("Error adding is_admin column: " . $conn->error);
}

// Delete existing admin user if exists
$delete_sql = "DELETE FROM users WHERE email = ?";
$stmt = $conn->prepare($delete_sql);
$stmt->bind_param("s", $admin_email);
$stmt->execute();

// Insert new admin user
$insert_sql = "INSERT INTO users (full_name, email, password, is_admin) VALUES (?, ?, ?, 1)";
$stmt = $conn->prepare($insert_sql);
$stmt->bind_param("sss", $admin_name, $admin_email, $hashed_password);

if ($stmt->execute()) {
    echo "Admin user created successfully!\n";
    echo "Email: " . $admin_email . "\n";
    echo "Password: " . $admin_password . "\n";
} else {
    echo "Error creating admin user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
