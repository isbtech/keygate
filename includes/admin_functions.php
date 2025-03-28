<?php
// Include configuration
require_once 'config.php';

// Admin Dashboard Functions

// Get dashboard statistics
function get_admin_dashboard_stats() {
    $conn = db_connect();
    
    // Total users
    $sql_users = "SELECT COUNT(*) as total FROM users WHERE role != 'Admin'";
    $result_users = $conn->query($sql_users);
    $total_users = $result_users->fetch_assoc()['total'];
    
    // Total events
    $sql_events = "SELECT COUNT(*) as total FROM events";
    $result_events = $conn->query($sql_events);
    $total_events = $result_events->fetch_assoc()['total'];
    
    // Events by status
    $event_statuses = ['Active', 'Completed', 'Cancelled', 'Scheduled', 'Pending Approval'];
    $events_by_status = [];
    
    foreach ($event_statuses as $status) {
        $sql_status = "SELECT COUNT(*) as total FROM events WHERE status = '$status'";
        $result_status = $conn->query($sql_status);
        $events_by_status[$status] = $result_status->fetch_assoc()['total'];
    }
    
    // Pending approvals
    $sql_pending = "SELECT COUNT(*) as total FROM events WHERE status = 'Pending Approval'";
    $result_pending = $conn->query($sql_pending);
    $pending_approvals = $result_pending->fetch_assoc()['total'];
    
    // Recent users
    $sql_recent_users = "SELECT id, name, email, role, activated, created_at FROM users 
                          WHERE role != 'Admin' 
                          ORDER BY created_at DESC LIMIT 5";
    $result_recent_users = $conn->query($sql_recent_users);
    $recent_users = [];
    
    if ($result_recent_users->num_rows > 0) {
        while ($row = $result_recent_users->fetch_assoc()) {
            $recent_users[] = $row;
        }
    }
    
    // Recent events
    $sql_recent_events = "SELECT e.id, e.event_name, e.event_organiser, e.status, e.created_at, u.name as created_by_name 
                           FROM events e
                           JOIN users u ON e.created_by = u.id
                           ORDER BY e.created_at DESC LIMIT 5";
    $result_recent_events = $conn->query($sql_recent_events);
    $recent_events = [];
    
    if ($result_recent_events->num_rows > 0) {
        while ($row = $result_recent_events->fetch_assoc()) {
            $recent_events[] = $row;
        }
    }
    
    $conn->close();
    
    return [
        'total_users' => $total_users,
        'total_events' => $total_events,
        'events_by_status' => $events_by_status,
        'pending_approvals' => $pending_approvals,
        'recent_users' => $recent_users,
        'recent_events' => $recent_events
    ];
}

// Create new user (by admin)
function admin_create_user($name, $email, $mobile, $password, $role, $plan_id = null, $activated = 'yes') {
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
        'activated' => $activated
    ];
    
    // Insert user
    $user_id = insert_record('users', $user_data);
    
    if ($user_id) {
        // Send welcome email
        $message = "
            <html>
            <head>
                <title>Welcome to Keygate</title>
            </head>
            <body>
                <h2>Welcome to Keygate</h2>
                <p>Hello $name,</p>
                <p>Your account has been created successfully.</p>
                <p><strong>Your login details:</strong></p>
                <p>Email: $email<br>Password: $password</p>
                <p>You can login at: " . SITE_URL . "/login.php</p>
                <p>Regards,<br>Keygate Admin</p>
            </body>
            </html>
        ";
        
        send_email($email, 'Welcome to Keygate', $message);
        
        return [
            'success' => true,
            'user_id' => $user_id,
            'message' => 'User created successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to create user. Please try again.'
    ];
}

// Get all users
function get_all_users($exclude_admin = true) {
    $condition = $exclude_admin ? "role != 'Admin'" : "1";
    $users = get_records('users', $condition, '*', 'created_at DESC');
    
    foreach ($users as &$user) {
        if ($user['plan_id']) {
            $plan = get_record('plans', "id = {$user['plan_id']}");
            $user['plan_name'] = $plan ? $plan['plan_name'] : 'N/A';
        } else {
            $user['plan_name'] = 'N/A';
        }
    }
    
    return $users;
}

// Update user status
function update_user_status($user_id, $activated) {
    $updated = update_record('users', ['activated' => $activated], "id = $user_id");
    
    if ($updated) {
        $user = get_record('users', "id = $user_id");
        
        // Send activation email if activated
        if ($activated == 'yes') {
            $message = "
                <html>
                <head>
                    <title>Account Activated</title>
                </head>
                <body>
                    <h2>Your Account Has Been Activated</h2>
                    <p>Hello {$user['name']},</p>
                    <p>Your Keygate account has been activated. You can now login to access your dashboard.</p>
                    <p>Login at: " . SITE_URL . "/login.php</p>
                    <p>Regards,<br>Keygate Admin</p>
                </body>
                </html>
            ";
            
            send_email($user['email'], 'Account Activated', $message);
        }
        
        return [
            'success' => true,
            'message' => 'User status updated successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to update user status'
    ];
}

// Get all pending events for approval
function get_pending_events() {
    $events = get_records('events', "status = 'Pending Approval'", '*', 'created_at DESC');
    
    foreach ($events as &$event) {
        $creator = get_record('users', "id = {$event['created_by']}");
        $event['creator_name'] = $creator ? $creator['name'] : 'Unknown';
        
        // Get event dates
        $dates = get_records('event_dates', "event_id = {$event['id']}", 'event_date', 'event_date ASC');
        $event['dates'] = array_column($dates, 'event_date');
    }
    
    return $events;
}

// Approve event
function approve_event($event_id, $admin_id) {
    // Get event details
    $event = get_record('events', "id = $event_id");
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Event not found'
        ];
    }
    
    // Get creator details
    $creator = get_record('users', "id = {$event['created_by']}");
    
    if (!$creator) {
        return [
            'success' => false,
            'message' => 'Event creator not found'
        ];
    }
    
    // Check if auto-approval is enabled for the user's plan
    $auto_status = 'Scheduled';
    if ($creator['plan_id']) {
        $plan = get_record('plans', "id = {$creator['plan_id']}");
        if ($plan && $plan['event_auto_approval'] == 'yes') {
            $auto_status = 'Active';
        }
    }
    
    // Update event status
    $updated = update_record('events', [
        'status' => $auto_status,
        'approved_by' => $admin_id
    ], "id = $event_id");
    
    if (!$updated) {
        return [
            'success' => false,
            'message' => 'Failed to approve event'
        ];
    }
    
    // Send approval email
    $message = "
        <html>
        <head>
            <title>Event Approved</title>
        </head>
        <body>
            <h2>Your Event Has Been Approved</h2>
            <p>Hello {$creator['name']},</p>
            <p>Your event '{$event['event_name']}' has been approved.</p>
            <p>Current status: $auto_status</p>
            <p>You can now set up gates and manage your event.</p>
            <p>Login at: " . SITE_URL . "/login.php</p>
            <p>Regards,<br>Keygate Admin</p>
        </body>
        </html>
    ";
    
    send_email($creator['email'], 'Event Approved: ' . $event['event_name'], $message);
    
    return [
        'success' => true,
        'message' => 'Event approved successfully'
    ];
}

// Reject event
function reject_event($event_id, $reason = '') {
    // Get event details
    $event = get_record('events', "id = $event_id");
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Event not found'
        ];
    }
    
    // Get creator details
    $creator = get_record('users', "id = {$event['created_by']}");
    
    if (!$creator) {
        return [
            'success' => false,
            'message' => 'Event creator not found'
        ];
    }
    
    // Update event status
    $updated = update_record('events', [
        'status' => 'Cancelled'
    ], "id = $event_id");
    
    if (!$updated) {
        return [
            'success' => false,
            'message' => 'Failed to reject event'
        ];
    }
    
    // Send rejection email
    $message = "
        <html>
        <head>
            <title>Event Rejected</title>
        </head>
        <body>
            <h2>Your Event Has Been Rejected</h2>
            <p>Hello {$creator['name']},</p>
            <p>We regret to inform you that your event '{$event['event_name']}' has been rejected.</p>";
    
    if (!empty($reason)) {
        $message .= "<p><strong>Reason:</strong> $reason</p>";
    }
    
    $message .= "
            <p>If you have any questions, please contact the admin.</p>
            <p>Regards,<br>Keygate Admin</p>
        </body>
        </html>
    ";
    
    send_email($creator['email'], 'Event Rejected: ' . $event['event_name'], $message);
    
    return [
        'success' => true,
        'message' => 'Event rejected successfully'
    ];
}

// Manage plans
function get_all_plans() {
    return get_records('plans', '1', '*', 'id ASC');
}

// Create new plan
function create_plan($plan_name, $events_allowed, $delegates_per_event, $event_auto_approval) {
    $plan_data = [
        'plan_name' => $plan_name,
        'events_allowed' => $events_allowed,
        'delegates_per_event' => $delegates_per_event,
        'event_auto_approval' => $event_auto_approval
    ];
    
    $plan_id = insert_record('plans', $plan_data);
    
    if ($plan_id) {
        return [
            'success' => true,
            'plan_id' => $plan_id,
            'message' => 'Plan created successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to create plan'
    ];
}

// Update plan
function update_plan($plan_id, $plan_name, $events_allowed, $delegates_per_event, $event_auto_approval) {
    $plan_data = [
        'plan_name' => $plan_name,
        'events_allowed' => $events_allowed,
        'delegates_per_event' => $delegates_per_event,
        'event_auto_approval' => $event_auto_approval
    ];
    
    $updated = update_record('plans', $plan_data, "id = $plan_id");
    
    if ($updated) {
        return [
            'success' => true,
            'message' => 'Plan updated successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to update plan'
    ];
}

// Delete plan
function delete_plan($plan_id) {
    // Check if plan is assigned to any users
    $users_with_plan = get_records('users', "plan_id = $plan_id");
    
    if (!empty($users_with_plan)) {
        return [
            'success' => false,
            'message' => 'Cannot delete plan as it is assigned to users'
        ];
    }
    
    $deleted = delete_record('plans', "id = $plan_id");
    
    if ($deleted) {
        return [
            'success' => true,
            'message' => 'Plan deleted successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to delete plan'
    ];
}

// Assign plan to user
function assign_plan_to_user($user_id, $plan_id) {
    $updated = update_record('users', ['plan_id' => $plan_id], "id = $user_id");
    
    if ($updated) {
        $user = get_record('users', "id = $user_id");
        $plan = get_record('plans', "id = $plan_id");
        
        // Send plan assignment email
        $message = "
            <html>
            <head>
                <title>Plan Assigned</title>
            </head>
            <body>
                <h2>Plan Assigned to Your Account</h2>
               // ... continuing from where we left off

                <p>Hello {$user['name']},</p>
                <p>A new plan has been assigned to your account:</p>
                <p><strong>Plan:</strong> {$plan['plan_name']}</p>
                <p><strong>Events Allowed:</strong> {$plan['events_allowed']}</p>
                <p><strong>Delegates Per Event:</strong> {$plan['delegates_per_event']}</p>
                <p><strong>Auto Approval:</strong> {$plan['event_auto_approval']}</p>
                <p>You can now create events according to your plan limits.</p>
                <p>Regards,<br>Keygate Admin</p>
            </body>
            </html>
        ";
        
        send_email($user['email'], 'Plan Assigned: ' . $plan['plan_name'], $message);
        
        return [
            'success' => true,
            'message' => 'Plan assigned successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to assign plan'
    ];
}
?>