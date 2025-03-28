<?php
// Include configuration
require_once 'config.php';

// User registration
function register_user($name, $email, $mobile, $password, $role = 'Event_admin', $plan_id = null) {
    // Check if email already exists
    $existing_user = get_record('users', "email = '$email'");
    
    if ($existing_user) {
        return [
            'success' => false,
            'message' => 'Email already registered'
        ];
    }
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare user data
    $user_data = [
        'name' => $name,
        'email' => $email,
        'mobile' => $mobile,
        'password' => $hashed_password,
        'role' => $role,
        'plan_id' => $plan_id,
        'activated' => 'no'
    ];
    
    // Insert user
    $user_id = insert_record('users', $user_data);
    
    if ($user_id) {
        return [
            'success' => true,
            'user_id' => $user_id,
            'message' => 'Registration successful. Please wait for admin approval.'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Registration failed. Please try again.'
    ];
}

// User login
function login_user($email, $password) {
    // Get user by email
    $user = get_record('users', "email = '$email'");
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    // Check if user is activated
    if ($user['activated'] != 'yes') {
        return [
            'success' => false,
            'message' => 'Your account is not activated. Please contact admin.'
        ];
    }
    
    // Verify password
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        return [
            'success' => true,
            'user' => $user,
            'message' => 'Login successful'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Invalid email or password'
    ];
}

// Event user login
function login_event_user($email, $password) {
    // Get event user by email
    $event_user = get_record('event_users', "email = '$email'");
    
    if (!$event_user) {
        return [
            'success' => false,
            'message' => 'Invalid email or password'
        ];
    }
    
    // Verify password
    if (password_verify($password, $event_user['password'])) {
        // Get event details
        $event = get_record('events', "id = {$event_user['event_id']}");
        
        if (!$event || $event['status'] != 'Active') {
            return [
                'success' => false,
                'message' => 'Associated event is not active'
            ];
        }
        
        // Get role details
        $role = get_record('event_user_roles', "id = {$event_user['role_id']}");
        
        // Set session variables
        $_SESSION['is_event_user'] = true;
        $_SESSION['event_user_id'] = $event_user['id'];
        $_SESSION['event_user_name'] = $event_user['name'];
        $_SESSION['event_user_email'] = $event_user['email'];
        $_SESSION['event_id'] = $event_user['event_id'];
        $_SESSION['event_name'] = $event['event_name'];
        $_SESSION['event_user_role'] = $role['role_name'];
        
        // Update last active time
        update_record('event_users', 
            ['last_active' => date('Y-m-d H:i:s')], 
            "id = {$event_user['id']}"
        );
        
        return [
            'success' => true,
            'event_user' => $event_user,
            'message' => 'Login successful'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Invalid email or password'
    ];
}

// User logout
function logout_user() {
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    session_destroy();
    
    return [
        'success' => true,
        'message' => 'Logout successful'
    ];
}

// Password reset request
function request_password_reset($email) {
    // Check if user exists
    $user = get_record('users', "email = '$email'");
    
    if (!$user) {
        return [
            'success' => false,
            'message' => 'Email not found'
        ];
    }
    
    // Generate reset token
    $reset_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store token in database
    $token_data = [
        'user_id' => $user['id'],
        'token' => $reset_token,
        'expires_at' => $expires_at,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Create password_resets table if it doesn't exist
    $conn = db_connect();
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->query($sql);
    $conn->close();
    
    // Insert token
    $token_id = insert_record('password_resets', $token_data);
    
    if (!$token_id) {
        return [
            'success' => false,
            'message' => 'Failed to generate reset token'
        ];
    }
    
    // Send reset email
    $reset_url = SITE_URL . "/reset_password.php?token=$reset_token";
    $message = "
        <html>
        <head>
            <title>Password Reset</title>
        </head>
        <body>
            <h2>Password Reset Request</h2>
            <p>Hello {$user['name']},</p>
            <p>We received a request to reset your password. Please click the link below to reset your password:</p>
            <p><a href='$reset_url'>Reset Password</a></p>
            <p>This link will expire in 1 hour.</p>
            <p>If you did not request a password reset, please ignore this email.</p>
            <p>Regards,<br>Keygate Team</p>
        </body>
        </html>
    ";
    
    $sent = send_email($user['email'], 'Password Reset Request', $message);
    
    if ($sent) {
        return [
            'success' => true,
            'message' => 'Password reset instructions sent to your email'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to send reset email. Please try again.'
    ];
}

// Reset password with token
function reset_password_with_token($token, $new_password) {
    // Get token record
    $token_record = get_record('password_resets', "token = '$token' AND expires_at > NOW()");
    
    if (!$token_record) {
        return [
            'success' => false,
            'message' => 'Invalid or expired token'
        ];
    }
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update user password
    $updated = update_record('users', 
        ['password' => $hashed_password], 
        "id = {$token_record['user_id']}"
    );
    
    if (!$updated) {
        return [
            'success' => false,
            'message' => 'Failed to update password'
        ];
    }
    
    // Delete used token
    delete_record('password_resets', "token = '$token'");
    
    return [
        'success' => true,
        'message' => 'Password has been reset successfully'
    ];
}
?>