<?php
// Include configuration
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Check if user is logged in and is event admin
if (!is_event_admin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if password is provided
if (!isset($_POST['password']) || empty($_POST['password'])) {
    echo json_encode(['success' => false, 'message' => 'Password is required']);
    exit;
}

$password = sanitize_input($_POST['password']);

// Add default_staff_password column to users table if it doesn't exist
$conn = db_connect();
$sql = "SHOW COLUMNS FROM users LIKE 'default_staff_password'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    // Column doesn't exist, add it
    $sql = "ALTER TABLE users ADD COLUMN default_staff_password VARCHAR(100) NULL";
    if (!$conn->query($sql)) {
        $conn->close();
        echo json_encode(['success' => false, 'message' => 'Failed to add default password column']);
        exit;
    }
}

// Update the user's default password
$updated = update_record('users', 
    ['default_staff_password' => $password], 
    "id = {$_SESSION['user_id']}"
);

$conn->close();

if ($updated) {
    echo json_encode(['success' => true, 'message' => 'Default password saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save default password']);
}
?>