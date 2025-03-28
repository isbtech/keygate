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

// Get user's events for the dropdown
$user_events = get_event_admin_events($_SESSION['user_id']);

// Set selected event and report type
$selected_event_id = 0;
$report_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'attendance';

if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
    $selected_event_id = (int)$_GET['event_id'];
} elseif (!empty($user_events)) {
    // Default to first event
    $selected_event_id = $user_events[array_key_first($user_events)]['id'];
}

// Get event details if an event is selected
$event = null;
$report_data = null;

if ($selected_event_id > 0) {
    $event_details = get_event_details($selected_event_id, $_SESSION['user_id']);
    if ($event_details['success']) {
        $event = $event_details['event'];
        
        // Get report data based on type
        switch ($report_type) {
            case 'attendance':
                $report_data = get_event_attendance_report($selected_event_id, $_SESSION['user_id']);
                break;
            case 'meeting':
                $report_data = get_event_meeting_report($selected_event_id, $_SESSION['user_id']);
                break;
            default:
                $report_type = 'attendance';
                $report_data = get_event_attendance_report($selected_event_id, $_SESSION['user_id']);
                break;
        }
    }
}

// Page title
$page_title = 'Event Reports';

// Include header
include '../includes/event_admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="events.php">
                            <i class="bi bi-calendar-event me-2"></i> My Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="create_event.php">
                            <i class="bi bi-plus-circle me-2"></i> Create Event
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="delegates.php">
                            <i class="bi bi-person-badge me-2"></i> Delegates
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="event_users.php">
                            <i class="bi bi-people me-2"></i> Event Staff
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active text-white" href="reports.php">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Event Reports</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="events.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Events
                    </a>
                    <?php if ($event && $report_data && $report_data['success']): ?>
                        <div class="btn-group me-2">
                            <a href="export_report.php?event_id=<?php echo $selected_event_id; ?>&type=<?php echo $report_type; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download"></i> Export to CSV
                            </a>
                            <a href="print_report.php?event_id=<?php echo $selected_event_id; ?>&type=<?php echo $report_type; ?>" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="bi bi-printer"></i> Print Report
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Report Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-5">
                            <label for="event_id" class="form-label">Select Event</label>
                            <select class="form-select" id="event_id" name="event_id">
                                <option value="">-- Select Event --</option>
                                <?php foreach ($user_events as $evt): ?>
                                    <option value="<?php echo $evt['id']; ?>" <?php echo $selected_event_id == $evt['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($evt['event_name']); ?> 
                                        (<?php echo $evt['status']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label for="type" class="form-label">Report Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="attendance" <?php echo $report_type == 'attendance' ? 'selected' : ''; ?>>
                                    Gate-wise Attendance Report
                                </option>
                                <option value="meeting" <?php echo $report_type == 'meeting' ? 'selected' : ''; ?>>
                                    Meeting / Delegates Report
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($user_events)): ?>
                <div class="alert alert-warning">
                    <h5>No Events Found</h5>
                    <p>You don't have any events yet. Create an event to view reports.</p>
                    <p><a href="create_event.php" class="btn btn-primary btn-sm">Create New Event</a></p>
                </div>
            <?php elseif ($selected_event_id == 0): ?>
                <div class="alert alert-info">
                    <h5>Select an Event</h5>
                    <p>Please select an event from the dropdown above to view reports.</p>
                </div>
            <?php elseif (!$report_data || !$report_data['success']): ?>
                <div class="alert alert-danger">
                    <h5>Error Loading Report</h5>
                    <p><?php echo $report_data ? $report_data['message'] : 'Failed to load report data'; ?></p>
                </div>
            <?php elseif ($report_type == 'attendance'): ?>
                <!-- Attendance Report -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Gate-wise Attendance Report - <?php echo htmlspecialchars($event['event_name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <!-- Overall Summary Stats -->
                        <div class="row mb-4">
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
                                        <p class="mb-0">Checked In at Least One Gate</p>
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
                        
                        <!-- Gate-wise Attendance Table -->
                        <h5 class="mb-3">Gate-wise Attendance</h5>
                        <?php if (empty($report_data['gate_report'])): ?>
                            <div class="alert alert-info">
                                <p>No gates have been created for this event yet. Create gates to track attendance.</p>
                                <a href="manage_gates.php?event_id=<?php echo $selected_event_id; ?>" class="btn btn-primary btn-sm">Manage Gates</a>
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
                                            <th>Details</th>
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
                                                <td>
                                                    <a href="gate_stats.php?gate_id=<?php echo $gate['gate_id']; ?>&event_id=<?php echo $selected_event_id; ?>" class="btn btn-sm btn-outline-primary">
                                                        View Details
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Visualization -->
                        <div class="mt-4">
                            <h5 class="mb-3">Attendance Visualization</h5>
                            <canvas id="attendanceChart" height="300"></canvas>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Meeting / Delegates Report -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Meeting Report - <?php echo htmlspecialchars($event['event_name']); ?></h5>
                    </div>
                    <div class="card-body">
                        <!-- Overall Summary Stats -->
                        <div class="row mb-4">
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
                        
                        <!-- Check-ins by Date -->
                        <h5 class="mb-3">Check-ins by Date</h5>
                        <?php if (empty($report_data['checkins_by_date'])): ?>
                            <div class="alert alert-info">
                                <p>No check-in data available for this event yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive mb-4">
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
                        
                        <!-- Visualization -->
                        <div class="mb-4">
                            <h5 class="mb-3">Check-in Status</h5>
                            <canvas id="checkinsChart" height="300"></canvas>
                        </div>
                        
                        <!-- Delegates List -->
                        <h5 class="mb-3">Delegates List</h5>
                        <div class="mb-3">
                            <input type="text" id="delegateSearch" class="form-control" placeholder="Search delegates...">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="delegatesTable">
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
                                                <span class="badge bg-<?php echo $delegate['checked_in'] == 'Yes' ? 'success' : 'warning'; ?>">
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
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($report_type == 'attendance' && !empty($report_data['gate_report'])): ?>
            // Attendance Chart for Gate Report
            const labels = <?php echo json_encode(array_column($report_data['gate_report'], 'gate_name')); ?>;
            const totalAccessData = <?php echo json_encode(array_column($report_data['gate_report'], 'total_access')); ?>;
            const checkinsData = <?php echo json_encode(array_column($report_data['gate_report'], 'total_checkins')); ?>;
            
            const attendanceChart = new Chart(document.getElementById('attendanceChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Delegates with Access',
                            data: totalAccessData,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Checked In',
                            data: checkinsData,
                            backgroundColor: 'rgba(75, 192, 192, 0.5)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        <?php endif; ?>
        
        <?php if ($report_type == 'meeting'): ?>
            // Check-ins Chart for Meeting Report
            const checkinsChart = new Chart(document.getElementById('checkinsChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Checked In', 'Not Checked In'],
                    datasets: [{
                        data: [
                            <?php echo $report_data['total_checked_in']; ?>,
                            <?php echo $report_data['total_not_checked_in']; ?>
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Delegate search functionality
            const searchInput = document.getElementById('delegateSearch');
            const table = document.getElementById('delegatesTable');
            
            if (searchInput && table) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                    
                    for (let i = 0; i < rows.length; i++) {
                        const rowText = rows[i].textContent.toLowerCase();
                        rows[i].style.display = rowText.includes(searchTerm) ? '' : 'none';
                    }
                });
            }
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include '../includes/event_admin_footer.php';
?>