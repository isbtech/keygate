<?php
// Include configuration
require_once 'config.php';

// Gatekeeper Functions for Delegate Check-in

// Get gatekeeper info
function get_gatekeeper_info($event_user_id) {
    // Get event user details
    $event_user = get_record('event_users', "id = $event_user_id");
    
    if (!$event_user) {
        return [
            'success' => false,
            'message' => 'Gatekeeper not found'
        ];
    }
    
    // Check if user is a gatekeeper
    $role = get_record('event_user_roles', "id = {$event_user['role_id']}");
    
    if (!$role || $role['role_name'] != 'GateKeeper') {
        return [
            'success' => false,
            'message' => 'User is not a gatekeeper'
        ];
    }
    
    // Get event details
    $event = get_record('events', "id = {$event_user['event_id']}");
    
    if (!$event || $event['status'] != 'Active') {
        return [
            'success' => false,
            'message' => 'Event not active'
        ];
    }
    
    // Get assigned gate info
    $gate = null;
    if ($event_user['last_active_gate']) {
        $gate = get_record('access_gates', "id = {$event_user['last_active_gate']}");
    }
    
    // If no gate assigned or gate not found, get any available gate
    if (!$gate) {
        $gate = get_record('access_gates', "event_id = {$event_user['event_id']} AND gate_status = 'Open'", '*', 'id ASC');
    }
    
    if (!$gate) {
        return [
            'success' => false,
            'message' => 'No open gates available'
        ];
    }
    
    // Update last active gate for the gatekeeper
    update_record('event_users', 
        ['last_active_gate' => $gate['id'], 'last_active' => date('Y-m-d H:i:s')], 
        "id = $event_user_id"
    );
    
    return [
        'success' => true,
        'event' => $event,
        'gate' => $gate,
        'gatekeeper' => $event_user
    ];
}

// Check if gate is open at current time
function is_gate_open($gate_id) {
    $gate = get_record('access_gates', "id = $gate_id");
    
    if (!$gate || $gate['gate_status'] != 'Open') {
        return false;
    }
    
    // Check if current time is within access time range
    $current_time = date('H:i:s');
    $access_start = $gate['access_time_start'];
    $access_end = $gate['access_time_end'];
    
    if ($current_time >= $access_start && $current_time <= $access_end) {
        return true;
    }
    
    return false;
}

// Process barcode scan for delegate check-in
function process_delegate_scan($barcode, $gate_id, $event_id, $gatekeeper_id) {
    // Check if gate is open
    if (!is_gate_open($gate_id)) {
        return [
            'success' => false,
            'message' => 'Gate is not open at this time',
            'access_granted' => false
        ];
    }
    
    // Get delegate by barcode
    $delegate = get_record('event_delegates', "barcode = '$barcode' AND event_id = $event_id");
    
    if (!$delegate) {
        return [
            'success' => false,
            'message' => 'Invalid barcode or delegate not found for this event',
            'access_granted' => false
        ];
    }
    
    // Check if delegate is approved
    if ($delegate['approved'] != 'Yes') {
        return [
            'success' => false,
            'message' => 'Delegate not approved',
            'access_granted' => false
        ];
    }
    
    // Check if delegate has access to this gate
    $access = get_record('delegate_gate_access', "delegate_id = {$delegate['id']} AND gate_id = $gate_id");
    
    if (!$access) {
        return [
            'success' => false,
            'message' => 'Access denied: Delegate does not have access to this gate',
            'access_granted' => false,
            'delegate' => [
                'name' => $delegate['name'],
                'email' => $delegate['email'],
                'designation' => $delegate['designation']
            ]
        ];
    }
    
    // Check if already checked in at this gate
    $existing_checkin = get_record('event_attendance', 
        "event_id = $event_id AND gate_id = $gate_id AND delegate_id = {$delegate['id']}"
    );
    
    if ($existing_checkin) {
        return [
            'success' => true,
            'message' => 'Delegate already checked in at this gate',
            'access_granted' => true,
            'already_checked_in' => true,
            'check_in_time' => $existing_checkin['check_in_time'],
            'delegate' => [
                'name' => $delegate['name'],
                'email' => $delegate['email'],
                'designation' => $delegate['designation']
            ]
        ];
    }
    
    // Record attendance
    $attendance_data = [
        'event_id' => $event_id,
        'gate_id' => $gate_id,
        'delegate_id' => $delegate['id'],
        'marked_by' => $gatekeeper_id,
        'check_in_time' => date('Y-m-d H:i:s')
    ];
    
    $attendance_id = insert_record('event_attendance', $attendance_data);
    
    if ($attendance_id) {
        // Get event and gate names for the response
        $event = get_record('events', "id = $event_id");
        $gate = get_record('access_gates', "id = $gate_id");
        
        return [
            'success' => true,
            'message' => 'Access granted. Check-in successful.',
            'access_granted' => true,
            'already_checked_in' => false,
            'check_in_time' => date('Y-m-d H:i:s'),
            'delegate' => [
                'name' => $delegate['name'],
                'email' => $delegate['email'],
                'designation' => $delegate['designation']
            ],
            'event_name' => $event ? $event['event_name'] : 'Unknown Event',
            'gate_name' => $gate ? $gate['gate_name'] : 'Unknown Gate'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'Failed to record attendance',
        'access_granted' => true
    ];
}

// Get recent check-ins for a gate
function get_recent_checkins($gate_id, $event_id, $limit = 10) {
    $conn = db_connect();
    $sql = "SELECT a.*, d.name, d.email, d.designation 
            FROM event_attendance a 
            JOIN event_delegates d ON a.delegate_id = d.id 
            WHERE a.event_id = $event_id AND a.gate_id = $gate_id 
            ORDER BY a.check_in_time DESC 
            LIMIT $limit";
    $result = $conn->query($sql);
    $checkins = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $checkins[] = $row;
        }
    }
    
    $conn->close();
    
    return $checkins;
}

// Get gate check-in summary
function get_gate_checkin_summary($gate_id, $event_id) {
    $conn = db_connect();
    
    // Total delegates with access to this gate
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
    
    // Check-ins in the last hour
    $sql_recent = "SELECT COUNT(*) as total FROM event_attendance 
                   WHERE event_id = $event_id AND gate_id = $gate_id 
                   AND check_in_time >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $result_recent = $conn->query($sql_recent);
    $recent_checkins = $result_recent->fetch_assoc()['total'];
    
    $conn->close();
    
    return [
        'total_with_access' => $total_access,
        'total_checked_in' => $total_checkins,
        'recent_checkins' => $recent_checkins,
        'percentage' => $total_access > 0 ? round(($total_checkins / $total_access) * 100, 2) : 0
    ];
}

// Manual check-in by searching delegate
function search_delegates($search_term, $event_id, $gate_id) {
    $conn = db_connect();
    $search_term = $conn->real_escape_string($search_term);
    
    $sql = "SELECT d.id, d.name, d.email, d.designation, d.barcode,
            (SELECT COUNT(*) FROM delegate_gate_access a WHERE a.delegate_id = d.id AND a.gate_id = $gate_id) as has_access,
            (SELECT COUNT(*) FROM event_attendance a WHERE a.delegate_id = d.id AND a.gate_id = $gate_id) as checked_in
            FROM event_delegates d
            WHERE d.event_id = $event_id 
            AND (d.name LIKE '%$search_term%' OR d.email LIKE '%$search_term%' OR d.mobile LIKE '%$search_term%')
            ORDER BY d.name ASC
            LIMIT 10";
            
    $result = $conn->query($sql);
    $delegates = [];
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['has_access'] = $row['has_access'] > 0;
            $row['checked_in'] = $row['checked_in'] > 0;
            $delegates[] = $row;
        }
    }
    
    $conn->close();
    
    return $delegates;
}
?>