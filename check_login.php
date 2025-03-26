<?php
session_start();
include 'db_config.php'; // Make sure this file has your database connection

$response = array(
    'isLoggedIn' => false,
    'username' => '',
    'error' => null
);

if (isset($_SESSION['user_id'])) {
    try {
        // Prepare SQL to fetch user's name using the session user_id
        $stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $response['isLoggedIn'] = true;
            $response['username'] = $row['name']; // Get the actual name from database
        }
        
        $stmt->close();
    } catch (Exception $e) {
        $response['error'] = "Database error: " . $e->getMessage();
    }
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 