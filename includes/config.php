<?php
// config.php - Configuration settings
define('DB_HOST', 'localhost');
define('DB_USER', 'keygatevfull_clakeygateusr'); // Change as needed
define('DB_PASS', 'juKS4peVnpOWg5Y'); // Change as needed
define('DB_NAME', 'keygatevfull_clakeygatedbs');
define('SITE_URL', 'https://keygate.vfull.in/'); // Change to your actual URL
define('EMAIL_FROM', 'noreply@keygate.com');
define('ADMIN_EMAIL', 'admin@keygate.com');

// Database connection
function db_connect() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    
    return $conn;
}

// Get single record
function get_record($table, $condition = '1', $fields = '*') {
    $conn = db_connect();
    $sql = "SELECT $fields FROM $table WHERE $condition LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $conn->close();
        return $row;
    }
    
    $conn->close();
    return false;
}

// Get multiple records
function get_records($table, $condition = '1', $fields = '*', $order_by = '', $limit = '') {
    $conn = db_connect();
    $sql = "SELECT $fields FROM $table WHERE $condition";
    
    if (!empty($order_by)) {
        $sql .= " ORDER BY $order_by";
    }
    
    if (!empty($limit)) {
        $sql .= " LIMIT $limit";
    }
    
    $result = $conn->query($sql);
    $records = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
    }
    
    $conn->close();
    return $records;
}

// Insert record
function insert_record($table, $data) {
    $conn = db_connect();
    $fields = [];
    $values = [];
    
    foreach ($data as $field => $value) {
        $fields[] = $field;
        if ($value === null) {
            $values[] = "NULL";
        } else {
            $values[] = "'" . $conn->real_escape_string($value) . "'";
        }
    }
    
    $sql = "INSERT INTO $table (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
    
    if ($conn->query($sql)) {
        $last_id = $conn->insert_id;
        $conn->close();
        return $last_id;
    }
    
    $conn->close();
    return false;
}

// Update record
function update_record($table, $data, $condition) {
    $conn = db_connect();
    $set = [];
    
    foreach ($data as $field => $value) {
        if ($value === null) {
            $set[] = "$field = NULL";
        } else {
            $set[] = "$field = '" . $conn->real_escape_string($value) . "'";
        }
    }
    
    $sql = "UPDATE $table SET " . implode(', ', $set) . " WHERE $condition";
    
    if ($conn->query($sql)) {
        $affected_rows = $conn->affected_rows;
        $conn->close();
        return $affected_rows;
    }
    
    $conn->close();
    return false;
}

// Delete record
function delete_record($table, $condition) {
    $conn = db_connect();
    $sql = "DELETE FROM $table WHERE $condition";
    
    if ($conn->query($sql)) {
        $affected_rows = $conn->affected_rows;
        $conn->close();
        return $affected_rows;
    }
    
    $conn->close();
    return false;
}

// Email function
function send_email($to, $subject, $message) {
    $headers = "From: " . EMAIL_FROM . "\r\n";
    $headers .= "Reply-To: " . EMAIL_FROM . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Generate random password
function generate_password($length = 10) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    return substr(str_shuffle($chars), 0, $length);
}

// Generate barcode for delegates
function generate_barcode() {
    return uniqid('KEG', true);
}

// Session handling
session_start();

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Get current user role
function get_user_role() {
    return $_SESSION['user_role'] ?? '';
}

// Get current event admin event ID
function get_event_admin_event_id() {
    return $_SESSION['event_id'] ?? 0;
}

// Check if user has admin rights
function is_admin() {
    return is_logged_in() && get_user_role() == 'Admin';
}

// Check if user is event admin
function is_event_admin() {
    return is_logged_in() && get_user_role() == 'Event_admin';
}

// Check if user is event user (with specified role)
function is_event_user($role = '') {
    if (is_logged_in() && isset($_SESSION['is_event_user']) && $_SESSION['is_event_user']) {
        if (empty($role)) {
            return true;
        }
        return $_SESSION['event_user_role'] == $role;
    }
    return false;
}

// Redirect function
function redirect($url) {
    header("Location: $url");
    exit;
}

// Sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Flash messages
function set_flash_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Function to display flash messages
function display_flash_message() {
    $message = get_flash_message();
    if ($message) {
        $type = $message['type'];
        $text = $message['message'];
        echo "<div class='alert alert-$type'>$text</div>";
    }
}
?>