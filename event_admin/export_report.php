<?php
// Include configuration
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/event_admin_functions.php';

// Check if user is logged in and is event admin
if (!is_event_admin()) {
    set_flash_message('danger', 'Unauthorized access');
    redirect('../login.php');
}

// Check if event_id is provided
if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    set_flash_message('danger', 'Event ID is required');
    redirect('events.php');
}

$event_id = (int)$_GET['event_id'];

// Get event details and check ownership
$event_details = get_event_details($event_id, $_SESSION['user_id']);

if (!$event_details['success']) {
    set_flash_message('danger', $event_details['message']);
    redirect('events.php');
}

$event = $event_details['event'];

// Get report type
$report_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'attendance';

// Get gate_id if provided (for gate-specific reports)
$gate_id = isset($_GET['gate_id']) ? (int)$_GET['gate_id'] : 0;
$gate = null;

if ($gate_id > 0) {
    // Find the gate in the event gates
    foreach ($event['gates'] as $g) {
        if ($g['id'] == $gate_id) {
            $gate = $g;
            break;
        }
    }
    
    if (!$gate) {
        set_flash_message('danger', 'Gate not found or not part of this event');
        redirect('view_event.php?id=' . $event_id);
    }
}

// Initialize database connection
$conn = db_connect();

// Generate CSV data based on report type
$csv_data = [];
$filename = '';

switch ($report_type) {
    case 'attendance':
        // Gate-wise attendance report
        $filename = 'gate_attendance_report_' . $event_id . '_' . date('Ymd_His') . '.csv';
        
        // Headers
        $csv_data[] = ['Gate Name', 'Delegates with Access', 'Checked In', 'Attendance Percentage (%)'];
        
        // Get gate report data
        $report_data = get_event_attendance_report($event_id, $_SESSION['user_id']);
        
        if ($report_data['success']) {
            foreach ($report_data['gate_report'] as $gate) {
                $csv_data[] = [
                    $gate['gate_name'],
                    $gate['total_access'],
                    $gate['total_checkins'],
                    $gate['percentage']
                ];
            }
            
            // Add overall stats
            $csv_data[] = [''];
            $csv_data[] = ['Overall Statistics', '', '', ''];
            $csv_data[] = ['Total Delegates', $report_data['overall']['total_delegates'], '', ''];
            $csv_data[] = ['Total Unique Check-ins', $report_data['overall']['total_unique_checkins'], '', ''];
            $csv_data[] = ['Overall Attendance Rate', $report_data['overall']['percentage'] . '%', '', ''];
        }
        break;
        
    case 'meeting':
        // Meeting/delegates report
        $filename = 'delegates_report_' . $event_id . '_' . date('Ymd_His') . '.csv';
        
        // Headers
        $csv_data[] = ['Name', 'Email', 'Designation', 'Mobile', 'Status'];
        
        // Get meeting report data
        $report_data = get_event_meeting_report($event_id, $_SESSION['user_id']);
        
        if ($report_data['success']) {
            foreach ($report_data['delegates_list'] as $delegate) {
                $csv_data[] = [
                    $delegate['name'],
                    $delegate['email'],
                    $delegate['designation'],
                    $delegate['mobile'],
                    $delegate['checked_in'] == 'Yes' ? 'Checked In' : 'Not Checked In'
                ];
            }
            
            // Add summary stats
            $csv_data[] = [''];
            $csv_data[] = ['Summary Statistics', '', '', '', ''];
            $csv_data[] = ['Total Delegates', $report_data['total_delegates'], '', '', ''];
            $csv_data[] = ['Total Checked In', $report_data['total_checked_in'], '', '', ''];
            $csv_data[] = ['Total Not Checked In', $report_data['total_not_checked_in'], '', '', ''];
            $csv_data[] = ['Check-in Percentage', $report_data['check_in_percentage'] . '%', '', '', ''];
            
            // Add check-ins by date
            if (!empty($report_data['checkins_by_date'])) {
                $csv_data[] = [''];
                $csv_data[] = ['Check-ins by Date', '', '', '', ''];
                $csv_data[] = ['Date', 'Unique Delegates', 'Percentage', '', ''];
                
                foreach ($report_data['checkins_by_date'] as $date_data) {
                    $percentage = $report_data['total_delegates'] > 0 
                        ? round(($date_data['total'] / $report_data['total_delegates']) * 100, 2) 
                        : 0;
                    
                    $csv_data[] = [
                        date('F j, Y', strtotime($date_data['date'])),
                        $date_data['total'],
                        $percentage . '%',
                        '',
                        ''
                    ];
                }
            }
        }
        break;
        
    case 'gate_report':
        // Single gate report
        if (!$gate) {
            set_flash_message('danger', 'Gate ID is required for gate report');
            redirect('view_event.php?id=' . $event_id);
        }
        
        $filename = 'gate_report_' . $gate_id . '_' . date('Ymd_His') . '.csv';
        
        // Headers for gate info
        $csv_data[] = ['Gate Information', '', '', ''];
        $csv_data[] = ['Gate Name', $gate['gate_name'], '', ''];
        $csv_data[] = ['Access Time', date('h:i A', strtotime($gate['access_time_start'])) . ' - ' . date('h:i A', strtotime($gate['access_time_end'])), '', ''];
        $csv_data[] = ['Status', $gate['gate_status'], '', ''];
        $csv_data[] = ['', '', '', ''];
        
        // Get gate summary
        $sql_access = "SELECT COUNT(DISTINCT d.id) as total
                        FROM event_delegates d 
                        JOIN delegate_gate_access a ON d.id = a.delegate_id 
                        WHERE d.event_id = $event_id AND a.gate_id = $gate_id";
        $result_access = $conn->query($sql_access);
        $total_access = $result_access->fetch_assoc()['total'];
        
        $sql_checkins = "SELECT COUNT(*) as total FROM event_attendance 
                         WHERE event_id = $event_id AND gate_id = $gate_id";
        $result_checkins = $conn->query($sql_checkins);
        $total_checkins = $result_checkins->fetch_assoc()['total'];
        
        $percentage = $total_access > 0 ? round(($total_checkins / $total_access) * 100, 2) : 0;
        
        // Add summary stats
        $csv_data[] = ['Summary Statistics', '', '', ''];
        $csv_data[] = ['Delegates with Access', $total_access, '', ''];
        $csv_data[] = ['Checked In', $total_checkins, '', ''];
        $csv_data[] = ['Attendance Rate', $percentage . '%', '', ''];
        $csv_data[] = ['', '', '', ''];
        
        // Add hourly check-ins
        $sql_hourly = "SELECT HOUR(check_in_time) as hour, COUNT(*) as count 
                       FROM event_attendance 
                       WHERE event_id = $event_id AND gate_id = $gate_id 
                       GROUP BY HOUR(check_in_time) 
                       ORDER BY hour ASC";
        $result_hourly = $conn->query($sql_hourly);
        
        if ($result_hourly->num_rows > 0) {
            $csv_data[] = ['Check-ins by Hour', '', '', ''];
            $csv_data[] = ['Hour', 'Check-ins', 'Percentage', ''];
            
            while ($row = $result_hourly->fetch_assoc()) {
                $hour_percentage = $total_checkins > 0 ? round(($row['count'] / $total_checkins) * 100, 2) : 0;
                $csv_data[] = [
                    date('h:i A', strtotime($row['hour'] . ':00')),
                    $row['count'],
                    $hour_percentage . '%',
                    ''
                ];
            }
            
            $csv_data[] = ['', '', '', ''];
        }
        
        // Add dates check-ins
        $sql_date = "SELECT DATE(check_in_time) as date, COUNT(*) as count 
                     FROM event_attendance 
                     WHERE event_id = $event_id AND gate_id = $gate_id 
                     GROUP BY DATE(check_in_time) 
                     ORDER BY date ASC";
        $result_date = $conn->query($sql_date);
        
        if ($result_date->num_rows > 0) {
            $csv_data[] = ['Check-ins by Date', '', '', ''];
            $csv_data[] = ['Date', 'Check-ins', 'Percentage', ''];
            
            while ($row = $result_date->fetch_assoc()) {
                $date_percentage = $total_checkins > 0 ? round(($row['count'] / $total_checkins) * 100, 2) : 0;
                $csv_data[] = [
                    date('F j, Y', strtotime($row['date'])),
                    $row['count'],
                    $date_percentage . '%',
                    ''
                ];
            }
        }
        break;
        
    case 'checkins':
        // All check-ins for a specific gate
        if (!$gate) {
            set_flash_message('danger', 'Gate ID is required for check-ins export');
            redirect('view_event.php?id=' . $event_id);
        }
        
        $filename = 'checkins_' . $gate_id . '_' . date('Ymd_His') . '.csv';
        
        // Headers
        $csv_data[] = ['Name', 'Email', 'Designation', 'Mobile', 'Check-in Time', 'Marked By'];
        
        // Get check-ins data
        $sql = "SELECT d.name, d.email, d.designation, d.mobile, a.check_in_time, eu.name as gatekeeper_name 
                FROM event_delegates d 
                JOIN event_attendance a ON d.id = a.delegate_id 
                LEFT JOIN event_users eu ON a.marked_by = eu.id
                WHERE a.event_id = $event_id AND a.gate_id = $gate_id 
                ORDER BY a.check_in_time DESC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $csv_data[] = [
                    $row['name'],
                    $row['email'],
                    $row['designation'],
                    $row['mobile'],
                    date('F j, Y, g:i a', strtotime($row['check_in_time'])),
                    $row['gatekeeper_name'] ?: 'Unknown'
                ];
            }
        }
        break;
        
    default:
        set_flash_message('danger', 'Invalid report type');
        redirect('view_event.php?id=' . $event_id);
        break;
}

$conn->close();

// Output CSV file
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$output = fopen('php://output', 'w');

foreach ($csv_data as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
?>