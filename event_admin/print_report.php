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

// Get report data based on type
$report_data = null;
$report_title = '';

switch ($report_type) {
    case 'attendance':
        $report_data = get_event_attendance_report($event_id, $_SESSION['user_id']);
        $report_title = 'Gate-wise Attendance Report';
        break;
    case 'meeting':
        $report_data = get_event_meeting_report($event_id, $_SESSION['user_id']);
        $report_title = 'Meeting / Delegates Report';
        break;
    case 'gate_stats':
        if (!$gate) {
            set_flash_message('danger', 'Gate ID is required for gate statistics');
            redirect('view_event.php?id=' . $event_id);
        }
        
        // Get gate summary
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
        
        // Check-ins by hour
        $sql_hourly = "SELECT HOUR(check_in_time) as hour, COUNT(*) as count 
                       FROM event_attendance 
                       WHERE event_id = $event_id AND gate_id = $gate_id 
                       GROUP BY HOUR(check_in_time) 
                       ORDER BY hour ASC";
        $result_hourly = $conn->query($sql_hourly);
        $hourly_data = [];
        
        if ($result_hourly->num_rows > 0) {
            while ($row = $result_hourly->fetch_assoc()) {
                $hourly_data[] = $row;
            }
        }
        
        // Check-ins by date
        $sql_date = "SELECT DATE(check_in_time) as date, COUNT(*) as count 
                     FROM event_attendance 
                     WHERE event_id = $event_id AND gate_id = $gate_id 
                     GROUP BY DATE(check_in_time) 
                     ORDER BY date ASC";
        $result_date = $conn->query($sql_date);
        $date_data = [];
        
        if ($result_date->num_rows > 0) {
            while ($row = $result_date->fetch_assoc()) {
                $date_data[] = $row;
            }
        }
        
        // Get delegates checked in at this gate
        $sql_delegates = "SELECT d.name, d.email, d.designation, d.mobile, a.check_in_time 
                          FROM event_delegates d 
                          JOIN event_attendance a ON d.id = a.delegate_id 
                          WHERE a.event_id = $event_id AND a.gate_id = $gate_id 
                          ORDER BY a.check_in_time DESC";
        $result_delegates = $conn->query($sql_delegates);
        $delegates_data = [];
        
        if ($result_delegates->num_rows > 0) {
            while ($row = $result_delegates->fetch_assoc()) {
                $delegates_data[] = $row;
            }
        }
        
        $conn->close();
        
        $report_data = [
            'success' => true,
            'total_access' => $total_access,
            'total_checkins' => $total_checkins,
            'percentage' => $total_access > 0 ? round(($total_checkins / $total_access) * 100, 2) : 0,
            'hourly_data' => $hourly_data,
            'date_data' => $date_data,
            'delegates_data' => $delegates_data
        ];
        
        $report_title = 'Gate Statistics - ' . $gate['gate_name'];
        break;
    default:
        set_flash_message('danger', 'Invalid report type');
        redirect('view_event.php?id=' . $event_id);
        break;
}

// Prepare date for report header
$report_date = date('F j, Y, g:i a');

// Set page title
$page_title = $report_title . ' - ' . $event['event_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .report-header {
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .print-only {
            display: none;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .print-only {
                display: block;
            }
            .page-break {
                page-break-before: always;
            }
            body {
                padding: 0;
                margin: 0;
            }
            .container-fluid {
                width: 100%;
                padding: 0;
            }
            .card {
                border: none;
            }
            .card-header {
                background-color: #f1f1f1 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
            }
            .table {
                width: 100%;
                border-collapse: collapse;
            }
            .table th, .table td {
                border: 1px solid #ddd;
                padding: 8px;
            }
            .badge {
                border: 1px solid #ccc;
            }
            .badge-success {
                background-color: #28a745 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
            .badge-warning {
                background-color: #ffc107 !important;
                color: black !important;
                -webkit-print-color-adjust: exact;
            }
            .badge-danger {
                background-color: #dc3545 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
            }
            .progress {
                border: 1px solid #ddd;
                height: 20px !important;
            }
            .progress-bar {
                background-color: #28a745 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print controls -->
        <div class="text-end mb-3 no-print">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
            <a href="javascript:window.close()" class="btn btn-secondary">
                Close
            </a>
        </div>
        
        <!-- Report Header -->
        <div class="report-header">
            <div class="row">
                <div class="col-md-6">
                    <h2><?php echo htmlspecialchars($report_title); ?></h2>
                    <h4><?php echo htmlspecialchars($event['event_name']); ?></h4>
                    <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['event_organiser']); ?></p>
                    <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['event_venue']); ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <p><strong>Date:</strong> <?php echo $report_date; ?></p>
                    <p><strong>Event Dates:</strong> 
                        <?php 
                        if (!empty($event['dates'])) {
                            foreach ($event['dates'] as $index => $date) {
                                echo date('M d, Y', strtotime($date));
                                if ($index < count($event['dates']) - 1) {
                                    echo ', ';
                                }
                            }
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </p>
                    <p><strong>Generated by:</strong> <?php echo $_SESSION['user_name']; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Report Content -->
        <?php if ($report_type == 'attendance'): ?>
            <!-- Attendance Report -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Overall Attendance Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3><?php echo $report_data['overall']['total_delegates']; ?></h3>
                                    <p class="mb-0">Total Delegates</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report_data['overall']['total_unique_checkins']; ?></h3>
                                    <p class="mb-0">Checked In</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report_data['overall']['percentage']; ?>%</h3>
                                    <p class="mb-0">Overall Attendance Rate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gate-wise Attendance Table -->
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Gate-wise Attendance</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($report_data['gate_report'])): ?>
                        <div class="alert alert-info">
                            <p>No gates have been created for this event.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Gate Name</th>
                                        <th>Delegates with Access</th>
                                        <th>Checked In</th>
                                        <th>Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['gate_report'] as $gate): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($gate['gate_name']); ?></td>
                                            <td><?php echo $gate['total_access']; ?></td>
                                            <td><?php echo $gate['total_checkins']; ?></td>
                                            <td>
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?php echo $gate['percentage']; ?>%;" 
                                                         aria-valuenow="<?php echo $gate['percentage']; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $gate['percentage']; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php elseif ($report_type == 'meeting'): ?>
            <!-- Meeting Report -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Delegates Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3><?php echo $report_data['total_delegates']; ?></h3>
                                    <p class="mb-0">Total Delegates</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report_data['total_checked_in']; ?></h3>
                                    <p class="mb-0">Checked In</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h3><?php echo $report_data['total_not_checked_in']; ?></h3>
                                    <p class="mb-0">Not Checked In</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report_data['check_in_percentage']; ?>%</h3>
                                    <p class="mb-0">Attendance Rate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Check-ins by Date -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Check-ins by Date</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($report_data['checkins_by_date'])): ?>
                        <div class="alert alert-info">
                            <p>No check-in data available for this event yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Unique Delegates</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['checkins_by_date'] as $date_data): ?>
                                        <tr>
                                            <td><?php echo date('F j, Y', strtotime($date_data['date'])); ?></td>
                                            <td><?php echo $date_data['total']; ?></td>
                                            <td>
                                                <?php $percentage = $report_data['total_delegates'] > 0 ? round(($date_data['total'] / $report_data['total_delegates']) * 100, 2) : 0; ?>
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar bg-info" role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%;" 
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $percentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Delegates List -->
            <div class="card page-break">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Delegates List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Designation</th>
                                    <th>Mobile</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report_data['delegates_list'] as $delegate): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($delegate['name']); ?></td>
                                        <td><?php echo htmlspecialchars($delegate['email']); ?></td>
                                        <td><?php echo htmlspecialchars($delegate['designation']); ?></td>
                                        <td><?php echo htmlspecialchars($delegate['mobile']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $delegate['checked_in'] == 'Yes' ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo $delegate['checked_in'] == 'Yes' ? 'Checked In' : 'Not Checked In'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
        <?php elseif ($report_type == 'gate_stats'): ?>
            <!-- Gate Statistics Report -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Gate Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Gate Name:</strong> <?php echo htmlspecialchars($gate['gate_name']); ?></p>
                            <p><strong>Access Time:</strong> <?php echo date('h:i A', strtotime($gate['access_time_start'])); ?> - <?php echo date('h:i A', strtotime($gate['access_time_end'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status:</strong> 
                                <span class="badge <?php echo $gate['gate_status'] == 'Open' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $gate['gate_status']; ?>
                                </span>
                            </p>
                            <p><strong>Last Updated:</strong> <?php echo date('F j, Y, g:i a', strtotime($gate['updated_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Attendance Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3><?php echo $report_data['total_access']; ?></h3>
                                    <p class="mb-0">Delegates with Access</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report_data['total_checkins']; ?></h3>
                                    <p class="mb-0">Checked In</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3><?php echo $report_data['percentage']; ?>%</h3>
                                    <p class="mb-0">Attendance Rate</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Check-ins by Hour -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Check-ins by Hour</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($report_data['hourly_data'])): ?>
                        <div class="alert alert-info">
                            <p>No check-in data available for this gate yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Hour</th>
                                        <th>Check-ins</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['hourly_data'] as $hour_data): ?>
                                        <tr>
                                            <td><?php echo date('h:i A', strtotime($hour_data['hour'] . ':00')); ?></td>
                                            <td><?php echo $hour_data['count']; ?></td>
                                            <td>
                                                <?php $percentage = $report_data['total_checkins'] > 0 ? round(($hour_data['count'] / $report_data['total_checkins']) * 100, 2) : 0; ?>
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar bg-info" role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%;" 
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $percentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Check-ins by Date -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Check-ins by Date</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($report_data['date_data'])): ?>
                        <div class="alert alert-info">
                            <p>No check-in data available for this gate yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Check-ins</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['date_data'] as $date_data): ?>
                                        <tr>
                                            <td><?php echo date('F j, Y', strtotime($date_data['date'])); ?></td>
                                            <td><?php echo $date_data['count']; ?></td>
                                            <td>
                                                <?php $percentage = $report_data['total_checkins'] > 0 ? round(($date_data['count'] / $report_data['total_checkins']) * 100, 2) : 0; ?>
                                                <div class="progress" style="height: 25px;">
                                                    <div class="progress-bar bg-warning" role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%;" 
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" aria-valuemax="100">
                                                        <?php echo $percentage; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Delegates Checked In -->
            <div class="card page-break">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Delegates Checked In</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($report_data['delegates_data'])): ?>
                        <div class="alert alert-info">
                            <p>No delegates have checked in at this gate yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Designation</th>
                                        <th>Mobile</th>
                                        <th>Check-in Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report_data['delegates_data'] as $delegate): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($delegate['name']); ?></td>
                                            <td><?php echo htmlspecialchars($delegate['email']); ?></td>
                                            <td><?php echo htmlspecialchars($delegate['designation']); ?></td>
                                            <td><?php echo htmlspecialchars($delegate['mobile']); ?></td>
                                            <td><?php echo date('F j, Y, g:i a', strtotime($delegate['check_in_time'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Report Footer -->
        <div class="mt-4 print-only">
            <p class="text-center">
                Report generated on <?php echo $report_date; ?><br>
                Keygate Event Management System<br>
                &copy; <?php echo date('Y'); ?> Keygate. All rights reserved.
            </p>
        </div>
        
        <!-- Print controls (bottom) -->
        <div class="text-end mt-4 no-print">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Print Report
            </button>
            <a href="javascript:window.close()" class="btn btn-secondary">
                Close
            </a>
        </div>
    </div>
    
    <script>
        // Auto-print when the page loads (uncomment if desired)
        // window.onload = function() {
        //     window.print();
        // };
    </script>
</body>
</html>