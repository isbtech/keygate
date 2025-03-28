<?php
// Include configuration
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/event_admin_functions.php';
require_once '../includes/delegate_checkin.php';

// Check if user is logged in and is event admin
if (!is_event_admin()) {
    set_flash_message('danger', 'Unauthorized access');
    redirect('../login.php');
}

// Check if event_id and gate_id are provided
if (!isset($_GET['event_id']) || empty($_GET['event_id']) || !isset($_GET['gate_id']) || empty($_GET['gate_id'])) {
    set_flash_message('danger', 'Both Event ID and Gate ID are required');
    redirect('events.php');
}

$event_id = (int)$_GET['event_id'];
$gate_id = (int)$_GET['gate_id'];

// Get event details and check ownership
$event_details = get_event_details($event_id, $_SESSION['user_id']);

if (!$event_details['success']) {
    set_flash_message('danger', $event_details['message']);
    redirect('events.php');
}

$event = $event_details['event'];

// Check if gate belongs to this event
$gate = null;
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

// Get gate summary stats
$gate_summary = get_gate_checkin_summary($gate_id, $event_id);

// Get time-based stats (hourly checkins)
$conn = db_connect();
$hourly_stats_query = "SELECT HOUR(check_in_time) as hour, COUNT(*) as count 
                      FROM event_attendance 
                      WHERE event_id = $event_id AND gate_id = $gate_id 
                      GROUP BY HOUR(check_in_time) 
                      ORDER BY hour ASC";
$hourly_stats_result = $conn->query($hourly_stats_query);
$hourly_stats = [];

if ($hourly_stats_result->num_rows > 0) {
    while ($row = $hourly_stats_result->fetch_assoc()) {
        $hourly_stats[] = $row;
    }
}

// Get date-based stats
$date_stats_query = "SELECT DATE(check_in_time) as date, COUNT(*) as count 
                     FROM event_attendance 
                     WHERE event_id = $event_id AND gate_id = $gate_id 
                     GROUP BY DATE(check_in_time) 
                     ORDER BY date ASC";
$date_stats_result = $conn->query($date_stats_query);
$date_stats = [];

if ($date_stats_result->num_rows > 0) {
    while ($row = $date_stats_result->fetch_assoc()) {
        $date_stats[] = $row;
    }
}

// Get recent check-ins
$recent_checkins = get_recent_checkins($gate_id, $event_id, 20);

// Get gatekeepers assigned to this gate
$gatekeepers_query = "SELECT eu.id, eu.name, eu.email, eu.last_active, COUNT(ea.id) as checkins 
                      FROM event_users eu 
                      LEFT JOIN event_attendance ea ON eu.id = ea.marked_by AND ea.gate_id = $gate_id 
                      JOIN event_user_roles r ON eu.role_id = r.id 
                      WHERE eu.event_id = $event_id AND r.role_name = 'GateKeeper' AND eu.last_active_gate = $gate_id 
                      GROUP BY eu.id 
                      ORDER BY eu.name ASC";
$gatekeepers_result = $conn->query($gatekeepers_query);
$gatekeepers = [];

if ($gatekeepers_result->num_rows > 0) {
    while ($row = $gatekeepers_result->fetch_assoc()) {
        $gatekeepers[] = $row;
    }
}

$conn->close();

// Page title
$page_title = 'Gate Statistics - ' . $gate['gate_name'];

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
                        <a class="nav-link active text-white" href="events.php">
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
                        <a class="nav-link text-white" href="reports.php">
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
                <h1 class="h2"><?php echo htmlspecialchars($gate['gate_name']); ?> - Gate Statistics</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view_event.php?id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Event
                    </a>
                    <a href="manage_gates.php?event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-door-open"></i> Manage Gates
                    </a>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Gate Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Event:</strong> <?php echo htmlspecialchars($event['event_name']); ?></p>
                            <p><strong>Gate Name:</strong> <?php echo htmlspecialchars($gate['gate_name']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Access Time:</strong> <?php echo date('h:i A', strtotime($gate['access_time_start'])); ?> - <?php echo date('h:i A', strtotime($gate['access_time_end'])); ?></p>
                            <p><strong>Current Status:</strong> 
                                <span class="badge bg-<?php echo $gate['gate_status'] == 'Open' ? 'success' : 'danger'; ?>">
                                    <?php echo $gate['gate_status']; ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Assigned Gatekeepers:</strong> <?php echo count($gatekeepers); ?></p>
                            <p><strong>Last Hour Check-ins:</strong> <?php echo $gate_summary['recent_checkins']; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Summary -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3><?php echo $gate_summary['total_with_access']; ?></h3>
                            <p class="mb-0">Delegates with Access</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $gate_summary['total_checked_in']; ?></h3>
                            <p class="mb-0">Checked In</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $gate_summary['percentage']; ?>%</h3>
                            <p class="mb-0">Attendance Rate</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <!-- Hourly Check-ins Chart -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Hourly Check-ins</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($hourly_stats)): ?>
                                <div class="alert alert-info">
                                    <p>No check-in data available for this gate yet.</p>
                                </div>
                            <?php else: ?>
                                <canvas id="hourlyChart" height="250"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Daily Check-ins Chart -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Daily Check-ins</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($date_stats)): ?>
                                <div class="alert alert-info">
                                    <p>No check-in data available for this gate yet.</p>
                                </div>
                            <?php else: ?>
                                <canvas id="dailyChart" height="250"></canvas>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <!-- Gatekeepers List -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Assigned Gatekeepers</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($gatekeepers)): ?>
                                <div class="alert alert-warning">
                                    <p>No gatekeepers assigned to this gate yet.</p>
                                    <a href="manage_staff.php?event_id=<?php echo $event_id; ?>" class="btn btn-primary btn-sm">Assign Gatekeepers</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Last Active</th>
                                                <th>Check-ins</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($gatekeepers as $gatekeeper): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($gatekeeper['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($gatekeeper['email']); ?></td>
                                                    <td>
                                                        <?php if ($gatekeeper['last_active']): ?>
                                                            <?php echo date('M d, Y H:i', strtotime($gatekeeper['last_active'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Never</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo $gatekeeper['checkins']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Check-ins -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Check-ins</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_checkins)): ?>
                                <div class="alert alert-info">
                                    <p>No check-ins recorded for this gate yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Delegate</th>
                                                <th>Time</th>
                                                <th>Marked By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_checkins as $checkin): ?>
                                                <?php 
                                                // Get gatekeeper name
                                                $gatekeeper_name = 'Unknown';
                                                foreach ($gatekeepers as $gk) {
                                                    if ($gk['id'] == $checkin['marked_by']) {
                                                        $gatekeeper_name = $gk['name'];
                                                        break;
                                                    }
                                                }
                                                
                                                // If not found in current gatekeepers, fetch from database
                                                if ($gatekeeper_name == 'Unknown') {
                                                    $gk = get_record('event_users', "id = {$checkin['marked_by']}");
                                                    if ($gk) {
                                                        $gatekeeper_name = $gk['name'];
                                                    }
                                                }
                                                ?>
                                                <tr>
                                                    <td>
                                                        <?php echo htmlspecialchars($checkin['name']); ?><br>
                                                        <small><?php echo htmlspecialchars($checkin['designation']); ?></small>
                                                    </td>
                                                    <td><?php echo date('M d, Y H:i:s', strtotime($checkin['check_in_time'])); ?></td>
                                                    <td><?php echo htmlspecialchars($gatekeeper_name); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <?php if (count($recent_checkins) == 20): ?>
                                    <div class="mt-2 text-center">
                                        <a href="export_report.php?event_id=<?php echo $event_id; ?>&gate_id=<?php echo $gate_id; ?>&type=checkins" class="btn btn-sm btn-outline-primary">
                                            Export All Check-ins
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Gate Control Panel</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Gate Status</h6>
                            <form method="post" action="manage_gates.php?event_id=<?php echo $event_id; ?>">
                                <input type="hidden" name="gate_id" value="<?php echo $gate_id; ?>">
                                <input type="hidden" name="status" value="<?php echo $gate['gate_status'] == 'Open' ? 'Close' : 'Open'; ?>">
                                <button type="submit" name="update_gate_status" class="btn <?php echo $gate['gate_status'] == 'Open' ? 'btn-danger' : 'btn-success'; ?> mb-3">
                                    <?php echo $gate['gate_status'] == 'Open' ? 'Close Gate' : 'Open Gate'; ?>
                                </button>
                            </form>
                            
                            <p><strong>Current Status:</strong> 
                                <span class="badge bg-<?php echo $gate['gate_status'] == 'Open' ? 'success' : 'danger'; ?>">
                                    <?php echo $gate['gate_status']; ?>
                                </span>
                            </p>
                            <p><strong>Access Hours:</strong> <?php echo date('h:i A', strtotime($gate['access_time_start'])); ?> - <?php echo date('h:i A', strtotime($gate['access_time_end'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Quick Actions</h6>
                            <div class="d-grid gap-2">
                                <a href="manage_staff.php?event_id=<?php echo $event_id; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-people"></i> Manage Gatekeepers
                                </a>
                                <a href="export_report.php?event_id=<?php echo $event_id; ?>&gate_id=<?php echo $gate_id; ?>&type=gate_report" class="btn btn-outline-success">
                                    <i class="bi bi-download"></i> Export Gate Report
                                </a>
                                <a href="print_report.php?event_id=<?php echo $event_id; ?>&gate_id=<?php echo $gate_id; ?>&type=gate_stats" class="btn btn-outline-secondary" target="_blank">
                                    <i class="bi bi-printer"></i> Print Gate Statistics
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($hourly_stats)): ?>
            // Hourly Check-ins Chart
            const hourlyLabels = <?php 
                $labels = [];
                foreach ($hourly_stats as $stat) {
                    $hour = (int)$stat['hour'];
                    $labels[] = date('h:i A', strtotime("$hour:00"));
                }
                echo json_encode($labels); 
            ?>;
            
            const hourlyData = <?php echo json_encode(array_column($hourly_stats, 'count')); ?>;
            
            new Chart(document.getElementById('hourlyChart'), {
                type: 'bar',
                data: {
                    labels: hourlyLabels,
                    datasets: [{
                        label: 'Check-ins by Hour',
                        data: hourlyData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
        
        <?php if (!empty($date_stats)): ?>
            // Daily Check-ins Chart
            const dailyLabels = <?php 
                $labels = [];
                foreach ($date_stats as $stat) {
                    $labels[] = date('M d', strtotime($stat['date']));
                }
                echo json_encode($labels); 
            ?>;
            
            const dailyData = <?php echo json_encode(array_column($date_stats, 'count')); ?>;
            
            new Chart(document.getElementById('dailyChart'), {
                type: 'bar',
                data: {
                    labels: dailyLabels,
                    datasets: [{
                        label: 'Check-ins by Date',
                        data: dailyData,
                        backgroundColor: 'rgba(75, 192, 192, 0.5)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    });
</script>

<?php
// Include footer
include '../includes/event_admin_footer.php';
?>