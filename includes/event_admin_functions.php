<?php
// Include configuration
require_once 'config.php';

// Event Admin Dashboard Functions

// Get event admin stats
function get_event_admin_stats($user_id) {
    $conn = db_connect();
    
    // Total events
    $sql_events = "SELECT COUNT(*) as total FROM events WHERE created_by = $user_id";
    $result_events = $conn->query($sql_events);
    $total_events = $result_events->fetch_assoc()['total'];
    
    // Events by status
    $event_statuses = ['Active', 'Completed', 'Cancelled', 'Scheduled', 'Pending Approval'];
    $events_by_status = [];
    
    foreach ($event_statuses as $status) {
        $sql_status = "SELECT COUNT(*) as total FROM events WHERE created_by = $user_id AND status = '$status'";
        $result_status = $conn->query($sql_status);
        $events_by_status[$status] = $result_status->fetch_assoc()['total'];
    }
    
    // User's plan details
    $user = get_record('users', "id = $user_id");
    $plan = null;
    $events_remaining = 0;
    
    if ($user && $user['plan_id']) {
        $plan = get_record('plans', "id = {$user['plan_id']}");
        if ($plan) {
            $events_remaining = $plan['events_allowed'] - $total_events;
            if ($events_remaining < 0) $events_remaining = 0;
        }
    }
    
    // Recent events
    $sql_recent_events = "SELECT id, event_name, event_organiser, status, created_at 
                           FROM events 
                           WHERE created_by = $user_id 
                           ORDER BY created_at DESC LIMIT 5";
    $result_recent_events = $conn->query($sql_recent_events);
    $recent_events = [];
    
    if ($result_recent_events->num_rows > 0) {
        while ($row = $result_recent_events->fetch_assoc()) {
            $recent_events[] = $row;
        }
    }
    
    $conn->close();
    
    return [
        'total_events' => $total_events,
        'events_by_status' => $events_by_status,
        'plan' => $plan,
        'events_remaining' => $events_remaining,
        'recent_events' => $recent_events
    ];
}

// Check if user can create more events
function can_create_event($user_id) {
    $user = get_record('users', "id = $user_id");
    
    if (!$user || !$user['plan_id']) {
        return [
            'can_create' => false,
            'message' => 'No plan assigned'
        ];
    }
    
    $plan = get_record('plans', "id = {$user['plan_id']}");
    
    if (!$plan) {
        return [
            'can_create' => false,
            'message' => 'Invalid plan'
        ];
    }
    
    // Count user's events
    $conn = db_connect();
    $sql = "SELECT COUNT(*) as total FROM events WHERE created_by = $user_id";
    $result = $conn->query($sql);
    $total_events = $result->fetch_assoc()['total'];
    $conn->close();
    
    if ($total_events >= $plan['events_allowed']) {
        return [
            'can_create' => false,
            'message' => 'Event limit reached for your plan'
        ];
    }
    
    return [
        'can_create' => true,
        'message' => 'You can create an event',
        'delegates_allowed' => $plan['delegates_per_event'],
        'auto_approval' => $plan['event_auto_approval']
    ];
}

// Create new event
function create_event($event_name, $event_organiser, $event_venue, $access_gates_required, $event_dates, $created_by) {
    // Check if user can create more events
    $can_create = can_create_event($created_by);
    
    if (!$can_create['can_create']) {
        return [
            'success' => false,
            'message' => $can_create['message']
        ];
    }
    
    // Prepare event data
    $event_data = [
        'event_name' => $event_name,
        'event_organiser' => $event_organiser,
        'event_venue' => $event_venue,
        'access_gates_required' => $access_gates_required,
        'created_by' => $created_by
    ];
    
    // Check if auto-approval is enabled
    if ($can_create['auto_approval'] == 'yes') {
        $event_data['status'] = 'Active';
        $event_data['approved_by'] = $created_by; // Self-approval
    } else {
        $event_data['status'] = 'Pending Approval';
    }
    
    // Insert event
    $event_id = insert_record('events', $event_data);
    
    if (!$event_id) {
        return [
            'success' => false,
            'message' => 'Failed to create event'
        ];
    }
    
    // Insert event dates
    foreach ($event_dates as $date) {
        $date_data = [
            'event_id' => $event_id,
            'event_date' => $date
        ];
        
        insert_record('event_dates', $date_data);
    }
    
    // Notify admin if approval is required
    if ($can_create['auto_approval'] != 'yes') {
        $user = get_record('users', "id = $created_by");
        $admin_email = ADMIN_EMAIL;
        
        $message = "
            <html>
            <head>
                <title>New Event Pending Approval</title>
            </head>
            <body>
                <h2>New Event Pending Approval</h2>
                <p>A new event has been created and is pending approval:</p>
                <p><strong>Event Name:</strong> $event_name</p>
                <p><strong>Organiser:</strong> $event_organiser</p>
                <p><strong>Venue:</strong> $event_venue</p>
                <p><strong>Created By:</strong> {$user['name']} ({$user['email']})</p>
                <p>Please login to approve or reject this event.</p>
                <p>Regards,<br>Keygate System</p>
            </body>
            </html>
        ";
        
        send_email($admin_email, 'New Event Pending Approval: ' . $event_name, $message);
    }
    
    return [
        'success' => true,
        'event_id' => $event_id,
        'message' => 'Event created successfully' . ($can_create['auto_approval'] == 'yes' ? '' : '. Awaiting admin approval.')
    ];
}

// Get event details
function get_event_details($event_id, $user_id = null) {
    // Get event
    $event = get_record('events', "id = $event_id" . ($user_id ? " AND created_by = $user_id" : ""));
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Event not found'
        ];
    }
    
    // Get event dates
    $dates = get_records('event_dates', "event_id = $event_id", 'event_date', 'event_date ASC');
    $event['dates'] = array_column($dates, 'event_date');
    
    // Get gates
    $gates = get_records('access_gates', "event_id = $event_id", '*', 'id ASC');
    $event['gates'] = $gates;
    
    // Get total delegates
    $conn = db_connect();
    $sql_delegates = "SELECT COUNT(*) as total FROM event_delegates WHERE event_id = $event_id";
    $result_delegates = $conn->query($sql_delegates);
    $event['total_delegates'] = $result_delegates->fetch_assoc()['total'];
    
    // Get total approved delegates
    $sql_approved = "SELECT COUNT(*) as total FROM event_delegates WHERE event_id = $event_id AND approved = 'Yes'";
    $result_approved = $conn->query($sql_approved);
    $event['approved_delegates'] = $result_approved->fetch_assoc()['total'];
    
    // Get total event users
    $sql_users = "SELECT COUNT(*) as total FROM event_users WHERE event_id = $event_id";
    $result_users = $conn->query($sql_users);
    $event['total_event_users'] = $result_users->fetch_assoc()['total'];
    
    // Get attendances (check-ins)
    $sql_attendance = "SELECT COUNT(*) as total FROM event_attendance WHERE event_id = $event_id";
    $result_attendance = $conn->query($sql_attendance);
    $event['total_check_ins'] = $result_attendance->fetch_assoc()['total'];
    
    $conn->close();
    
    return [
        'success' => true,
        'event' => $event
    ];
}

// Get all events for an event admin
function get_event_admin_events($user_id) {
    $events = get_records('events', "created_by = $user_id", '*', 'created_at DESC');
    
    foreach ($events as &$event) {
        // Get event dates
        $dates = get_records('event_dates', "event_id = {$event['id']}", 'event_date', 'event_date ASC');
        $event['dates'] = array_column($dates, 'event_date');
        
        // Get gates count
        $conn = db_connect();
        $sql_gates = "SELECT COUNT(*) as total FROM access_gates WHERE event_id = {$event['id']}";
        $result_gates = $conn->query($sql_gates);
        $event['gates_count'] = $result_gates->fetch_assoc()['total'];
        
        // Get delegates count
        $sql_delegates = "SELECT COUNT(*) as total FROM event_delegates WHERE event_id = {$event['id']}";
        $result_delegates = $conn->query($sql_delegates);
        $event['delegates_count'] = $result_delegates->fetch_assoc()['total'];
        
        $conn->close();
    }
    
    return $events;
}

// Create access gate
function create_access_gate($event_id, $gate_name, $access_time_start, $access_time_end, $user_id) {
    // Check if user owns the event
    $event = get_record('events', "id = $event_id AND created_by = $user_id");
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Event not found or you do not have access'
        ];
    }
    
    // Count existing gates
    $conn = db_connect();
    $sql = "SELECT COUNT(*) as total FROM access_gates WHERE event_id = $event_id";
    $result = $conn->query($sql);
    $gates_count = $result->fetch_assoc()['total'];
    $conn->close();
    
    // Check if max gates reached
    if ($gates_count >= $event['access_gates_required']) {
        return [
            'success' => false,
            'message' => 'Maximum number of gates reached for this event'
        ];
    }
    
    // Prepare gate data
    $gate_data = [
        'gate_name' => $gate_name,
        'gate_status' => 'Close', // Default to closed
        'event_id' => $event_id,
        'access_time_start' => $access_time_start,
        'access_time_end' => $access_time_end
    ];
    
    // Insert gate
    $gate_id = insert_record('access_gates', $gate_data);
    
    if ($gate_id) {
        return [
            'success' => true,
            'gate_id' => $gate_id,
            'message' => 'Gate created successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to create gate'
    ];
}

// Update gate status
function update_gate_status($gate_id, $status, $user_id) {
    // Check if user owns the gate's event
    $conn = db_connect();
    $sql = "SELECT e.* FROM events e 
            JOIN access_gates g ON e.id = g.event_id 
            WHERE g.id = $gate_id AND e.created_by = $user_id";
    $result = $conn->query($sql);
    
    if ($result->num_rows == 0) {
        $conn->close();
        return [
            'success' => false,
            'message' => 'Gate not found or you do not have access'
        ];
    }
    
    $conn->close();
    
    // Update gate status
    $updated = update_record('access_gates', ['gate_status' => $status], "id = $gate_id");
    
    if ($updated) {
        return [
            'success' => true,
            'message' => 'Gate status updated successfully'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to update gate status'
    ];
}

// Create event delegates
function create_event_delegate($event_id, $name, $designation, $email, $mobile, $access_gates, $user_id) {
    // Check if user owns the event
    $event = get_record('events', "id = $event_id AND created_by = $user_id");
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Event not found or you do not have access'
        ];
    }
    
    // Check if delegate limit reached
    $user = get_record('users', "id = $user_id");
    $plan = get_record('plans', "id = {$user['plan_id']}");
    
    if ($plan) {
        $conn = db_connect();
        $sql = "SELECT COUNT(*) as total FROM event_delegates WHERE event_id = $event_id";
        $result = $conn->query($sql);
        $delegates_count = $result->fetch_assoc()['total'];
        $conn->close();
        
        if ($delegates_count >= $plan['delegates_per_event']) {
            return [
                'success' => false,
                'message' => 'Maximum number of delegates reached for this event'
            ];
        }
    }
    
    // Check if email already exists for this event
    $existing_delegate = get_record('event_delegates', "event_id = $event_id AND email = '$email'");
    
    if ($existing_delegate) {
        return [
            'success' => false,
            'message' => 'Delegate with this email already exists for this event'
        ];
    }
    
    // Generate barcode
    $barcode = generate_barcode();
    
    // Prepare delegate data
    $delegate_data = [
        'event_id' => $event_id,
        'name' => $name,
        'designation' => $designation,
        'email' => $email,
        'mobile' => $mobile,
        'barcode' => $barcode,
        'verified_by' => $user_id,
        'approved' => 'Yes' // Auto-approve when created by event admin
    ];
    
    // Insert delegate
    $delegate_id = insert_record('event_delegates', $delegate_data);
    
    if (!$delegate_id) {
        return [
            'success' => false,
            'message' => 'Failed to create delegate'
        ];
    }
    
    // Assign access gates
    foreach ($access_gates as $gate_id) {
        $access_data = [
            'delegate_id' => $delegate_id,
            'gate_id' => $gate_id
        ];
        
        insert_record('delegate_gate_access', $access_data);
    }
    
    // Send welcome email to delegate
    $event_name = $event['event_name'];
    $message = "
        <html>
        <head>
            <title>Event Registration Confirmation</title>
        </head>
        <body>
            <h2>Event Registration Confirmation</h2>
            <p>Hello $name,</p>
            <p>You have been registered for the following event:</p>
            <p><strong>Event:</strong> $event_name</p>
            <p><strong>Venue:</strong> {$event['event_venue']}</p>";
    
    // Add event dates
    $dates = get_records('event_dates', "event_id = $event_id", 'event_date', 'event_date ASC');
    if (!empty($dates)) {
        $message .= "<p><strong>Date(s):</strong></p><ul>";
        foreach ($dates as $date) {
            $formatted_date = date('F j, Y', strtotime($date['event_date']));
            $message .= "<li>$formatted_date</li>";
        }
        $message .= "</ul>";
    }
    
    // Add gates information
    if (!empty($access_gates)) {
        $message .= "<p><strong>Access Gates:</strong></p><ul>";
        foreach ($access_gates as $gate_id) {
            $gate = get_record('access_gates', "id = $gate_id");
            if ($gate) {
                $message .= "<li>{$gate['gate_name']} (Access time: {$gate['access_time_start']} - {$gate['access_time_end']})</li>";
            }
        }
        $message .= "</ul>";
    }
    
    $message .= "
            <p><strong>Your Registration Barcode:</strong> $barcode</p>
            <p>Please keep this information for your reference and present your barcode at the event for check-in.</p>
            <p>Regards,<br>{$event['event_organiser']}</p>
        </body>
        </html>
    ";
    
    send_email($email, "Registration Confirmation: $event_name", $message);
    
    return [
        'success' => true,
        'delegate_id' => $delegate_id,
        'message' => 'Delegate created successfully'
    ];
}

// Get all delegates for an event
function get_event_delegates($event_id, $user_id) {
    // Check if user owns the event
    $event = get_record('events', "id = $event_id AND created_by = $user_id");
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Event not found or you do not have access'
        ];
    }
    
    $delegates = get_records('event_delegates', "event_id = $event_id", '*', 'name ASC');
    
    foreach ($delegates as &$delegate) {
        // Get assigned gates
        $conn = db_connect();
        $sql = "SELECT g.* FROM access_gates g 
                JOIN delegate_gate_access a ON g.id = a.gate_id 
                WHERE a.delegate_id = {$delegate['id']}";
        $result = $conn->query($sql);
        $gates = [];
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $gates[] = $row;
            }
        }
        
        $delegate['gates'] = $gates;
        
        // Check if delegate has checked in
        $sql_checkin = "SELECT COUNT(*) as checked_in FROM event_attendance 
                        WHERE delegate_id = {$delegate['id']}";
        $result_checkin = $conn->query($sql_checkin);
        $delegate['checked_in'] = $result_checkin->fetch_assoc()['checked_in'] > 0;
        
        $conn->close();
    }
    
    return [
        'success' => true,
        'delegates' => $delegates
    ];
}

// Create event user (staff)
function create_event_user($event_id, $name, $mobile, $email, $role_id, $user_id) {
    // Check if user owns the event
    $event = get_record('events', "id = $event_id AND created_by = $user_id");
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Event not found or you do not have access'
        ];
    }
    
    // Check if role exists
    $role = get_record('event_user_roles', "id = $role_id");
    
    if (!$role) {
        return [
            'success' => false,
            'message' => 'Invalid role'
        ];
    }
    
    // Check if email already exists for this event
    $existing_user = get_record('event_users', "event_id = $event_id AND email = '$email'");
    
    if ($existing_user) {
        return [
            'success' => false,
            'message' => 'User with this email already exists for this event'
        ];
    }
    
    // Generate random password
    $password = generate_password(10);
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Prepare user data
    $user_data = [
        'name' => $name,
        'mobile' => $mobile,
        'email' => $email,
        'password' => $hashed_password,
        'event_id' => $event_id,
        'role_id' => $role_id,
        'approved_by' => $user_id
    ];
    
    // Insert user
    $event_user_id = insert_record('event_users', $user_data);
    
    if (!$event_user_id) {
        return [
            'success' => false,
            'message' => 'Failed to create event user'
        ];
    }
    
    // Send welcome email with login details
    $message = "
        <html>
        <head>
            <title>Event Staff Login Details</title>
        </head>
        <body>
            <h2>Welcome to {$event['event_name']}</h2>
            <p>Hello $name,</p>
            <p>You have been assigned as {$role['role_name']} for the event:</p>
            <p><strong>Event:</strong> {$event['event_name']}</p>
            <p><strong>Venue:</strong> {$event['event_venue']}</p>
            <p><strong>Your Login Details:</strong></p>
            <p>Email: $email<br>Password: $password</p>
            <p>Please visit " . SITE_URL . "/event_login.php to login.</p>
            <p>Regards,<br>{$event['event_organiser']}</p>
        </body>
        </html>
    ";
    
    send_email($email, "Event Staff Login Details: {$event['event_name']}", $message);
    
    return [
        'success' => true,
        'event_user_id' => $event_user_id,
        'message' => 'Event user created successfully'
    ];
}

// Get all event users for an event
function get_event_users($event_id, $user_id) {
    // Check if user owns the event
    $event = get_record('events', "id = $event_id AND created_by = $user_id");
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Event not found or you do not have access'
        ];
    }
    
    $event_users = get_records('event_users', "event_id = $event_id", '*', 'name ASC');
    
    foreach ($event_users as &$event_user) {
        // Get role details
        $role = get_record('event_user_roles', "id = {$event_user['role_id']}");
        $event_user['role_name'] = $role ? $role['role_name'] : 'Unknown';
        
        // Get last active gate details if any
        if ($event_user['last_active_gate']) {
            $gate = get_record('access_gates', "id = {$event_user['last_active_gate']}");
            $event_user['last_active_gate_name'] = $gate ? $gate['gate_name'] : 'Unknown';
        } else {
            $event_user['last_active_gate_name'] = 'N/A';
        }
    }
    
    return [
        'success' => true,
        'event_users' => $event_users
    ];
}

// Get event attendance report
function get_event_attendance_report($event_id, $user_id) {
    // Check if user owns the event
    $event = get_record('events', "id = $event_id AND created_by = $user_id");
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Event not found or you do not have access'
        ];
    }
    
    // Get all gates for the event
    $gates = get_records('access_gates', "event_id = $event_id", '*', 'id ASC');
    
    $attendance_report = [];
    
    foreach ($gates as $gate) {
        $gate_id = $gate['id'];
        
        // Total delegates with access to this gate
        $conn = db_connect();
        $sql_access = "SELECT COUNT(DISTINCT d.id) as total
                        FROM event_delegates d 
                        JOIN delegate_gate_access a ON d.id = a.delegate_id 
                        WHERE d.event_id = $event_id AND a.gate_id = $gate_id";
        $result_access = $conn->query($sql_access);
        $total_access = $result_access->fetch_assoc()['total'];
        
        // Total check-ins for this gate
        $sql_checkins = "SELECT COUNT(*) as total FROM event_attendance 
                         WHERE event_id = $event_id AND gate_id = $gate_id";
        $result_checkins = $conn->query($sql_checkins);
        $total_checkins = $result_checkins->fetch_assoc()['total'];
        
        $conn->close();
        
        $attendance_report[] = [
            'gate_id' => $gate_id,
            'gate_name' => $gate['gate_name'],
            'total_access' => $total_access,
            'total_checkins' => $total_checkins,
            'percentage' => $total_access > 0 ? round(($total_checkins / $total_access) * 100, 2) : 0
        ];
    }
    
    // Overall statistics
    $conn = db_connect();
    
    // Total delegates
    $sql_delegates = "SELECT COUNT(*) as total FROM event_delegates WHERE event_id = $event_id";
    $result_delegates = $conn->query($sql_delegates);
    $total_delegates = $result_delegates->fetch_assoc()['total'];
    
    // Total unique check-ins (delegates who checked in at least one gate)
    $sql_unique = "SELECT COUNT(DISTINCT delegate_id) as total FROM event_attendance 
                   WHERE event_id = $event_id";
    $result_unique = $conn->query($sql_unique);
    $total_unique_checkins = $result_unique->fetch_assoc()['total'];
    
    $conn->close();
    
    $overall = [
        'total_delegates' => $total_delegates,
        'total_unique_checkins' => $total_unique_checkins,
        'percentage' => $total_delegates > 0 ? round(($total_unique_checkins / $total_delegates) * 100, 2) : 0
    ];
    
    return [
        'success' => true,
        'gate_report' => $attendance_report,
        'overall' => $overall
    ];
}

// Get event meeting report
function get_event_meeting_report($event_id, $user_id) {
    // Check if user owns the event
    $event = get_record('events', "id = $event_id AND created_by = $user_id");
    
    if (!$event) {
        return [
            'success' => false,
            'message' => 'Event not found or you do not have access'
        ];
    }
    
    $conn = db_connect();
    
    // Total delegates
    $sql_delegates = "SELECT COUNT(*) as total FROM event_delegates WHERE event_id = $event_id";
    $result_delegates = $conn->query($sql_delegates);
    $total_delegates = $result_delegates->fetch_assoc()['total'];
    
    // Total checked-in delegates
    $sql_checked = "SELECT COUNT(DISTINCT delegate_id) as total FROM event_attendance 
                     WHERE event_id = $event_id";
    $result_checked = $conn->query($sql_checked);
    $total_checked = $result_checked->fetch_assoc()['total'];
    
    // Delegates not checked in
    $not_checked = $total_delegates - $total_checked;
    
    // Get check-in data by date
    $sql_by_date = "SELECT DATE(check_in_time) as check_date, 
                     COUNT(DISTINCT delegate_id) as total 
                     FROM event_attendance 
                     WHERE event_id = $event_id 
                     GROUP BY DATE(check_in_time) 
                     ORDER BY check_date ASC";
    $result_by_date = $conn->query($sql_by_date);
    $checkins_by_date = [];
    
    if ($result_by_date->num_rows > 0) {
        while ($row = $result_by_date->fetch_assoc()) {
            $checkins_by_date[] = [
                'date' => $row['check_date'],
                'total' => $row['total']
            ];
        }
    }
    
    // Get delegates list with check-in status
    $sql_delegates_list = "SELECT d.id, d.name, d.email, d.designation, d.mobile, 
                          IF(a.delegate_id IS NULL, 'No', 'Yes') as checked_in 
                          FROM event_delegates d 
                          LEFT JOIN (
                             // ... continuing from where we left off
                              SELECT DISTINCT delegate_id 
                              FROM event_attendance 
                              WHERE event_id = $event_id
                          ) a ON d.id = a.delegate_id 
                          WHERE d.event_id = $event_id 
                          ORDER BY d.name ASC";
    $result_delegates_list = $conn->query($sql_delegates_list);
    $delegates_list = [];
    
    if ($result_delegates_list->num_rows > 0) {
        while ($row = $result_delegates_list->fetch_assoc()) {
            $delegates_list[] = $row;
        }
    }
    
    $conn->close();
    
    return [
        'success' => true,
        'total_delegates' => $total_delegates,
        'total_checked_in' => $total_checked,
        'total_not_checked_in' => $not_checked,
        'check_in_percentage' => $total_delegates > 0 ? round(($total_checked / $total_delegates) * 100, 2) : 0,
        'checkins_by_date' => $checkins_by_date,
        'delegates_list' => $delegates_list
    ];
}
?>