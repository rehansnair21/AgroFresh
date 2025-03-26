<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header("Location: login.php?redirect=orders.php");
    exit();
}

$user_id = $_SESSION['id'];

// Check if order_id is provided
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    $_SESSION['error'] = "Invalid order ID.";
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['order_id'];

// Verify that the order belongs to the current user and is in 'pending' status
$check_query = "SELECT * FROM orders WHERE id = ? AND user_id = ? AND status = 'pending'";
$stmt = $conn->prepare($check_query);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "You cannot cancel this order. It may not exist, belong to another user, or have already been processed.";
    header("Location: orders.php");
    exit();
}

// Update the order status to 'cancelled'
$update_query = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " has been cancelled successfully.";
} else {
    $_SESSION['error'] = "Failed to cancel the order. Please try again.";
}

$stmt->close();
$conn->close();

// Redirect back to orders page
header("Location: orders.php");
exit();
?> 