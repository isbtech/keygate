<?php
// Include configuration
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/admin_functions.php';

// Check if user is logged in and is admin
if (!is_admin()) {
    set_flash_message('danger', 'Unauthorized access');
    redirect('../login.php');
}

// Get report type from query string
$report_type = isset($_GET['type']) ? sanitize_input($_GET['type']) : 'users';

// Get date range for filtering
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');

// Get report data based on type
$report_data = [];
$conn = db_connect();

switch ($report_type) {
    case 'users':
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM users 
                WHERE role != 'Admin' AND created_at BETWEEN '$start_date' AND '$end_date 23:59:59' 
                GROUP BY DATE(created_at) 
                ORDER BY date ASC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
        }
        
        // Get user stats
        $sql_stats = "SELECT 
                        COUNT(*) as total_users,
                        SUM(CASE WHEN activated = 'yes' THEN 1 ELSE 0 END) as active_users,
                        SUM(CASE WHEN activated = 'no' THEN 1 ELSE 0 END) as inactive_users,
                        SUM(CASE WHEN role = 'Event_admin' THEN 1 ELSE 0 END) as event_admins,
                        SUM(CASE WHEN role = 'keygate_staff' THEN 1 ELSE 0 END) as keygate_staff
                      FROM users
                      WHERE role != 'Admin'";
        $result_stats = $conn->query($sql_stats);
        $user_stats = $result_stats->fetch_assoc();
        
        break;
    
    case 'events':
        $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
                FROM events 
                WHERE created_at BETWEEN '$start_date' AND '$end_date 23:59:59' 
                GROUP BY DATE(created_at) 
                ORDER BY date ASC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
        }
        
        // Get event stats
        $sql_stats = "SELECT 
                        COUNT(*) as total_events,
                        SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_events,
                        SUM(CASE WHEN status = 'Pending Approval' THEN 1 ELSE 0 END) as pending_events,
                        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_events,
                        SUM(CASE WHEN status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_events
                      FROM events";
        $result_stats = $conn->query($sql_stats);
        $event_stats = $result_stats->fetch_assoc();
        
        break;
    
    case 'attendances':
        $sql = "SELECT DATE(a.check_in_time) as date, COUNT(*) as count 
                FROM event_attendance a
                WHERE a.check_in_time BETWEEN '$start_date' AND '$end_date 23:59:59' 
                GROUP BY DATE(a.check_in_time) 
                ORDER BY date ASC";
        $result = $conn->query($sql);
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
        }
        
        // Get attendance stats
        $sql_stats = "SELECT 
                        COUNT(DISTINCT event_id) as total_events,
                        COUNT(DISTINCT gate_id) as total_gates,
                        COUNT(DISTINCT delegate_id) as total_delegates,
                        COUNT(*) as total_checkins
                      FROM event_attendance
                      WHERE check_in_time BETWEEN '$start_date' AND '$end_date 23:59:59'";
        $result_stats = $conn->query($sql_stats);
        $attendance_stats = $result_stats->fetch_assoc();
        
        // Get top events by attendance
        $sql_top_events = "SELECT e.id, e.event_name, COUNT(*) as checkin_count
                           FROM event_attendance a
                           JOIN events e ON a.event_id = e.id
                           WHERE a.check_in_time BETWEEN '$start_date' AND '$end_date 23:59:59'
                           GROUP BY a.event_id
                           ORDER BY checkin_count DESC
                           LIMIT 5";
        $result_top_events = $conn->query($sql_top_events);
        $top_events = [];
        
        if ($result_top_events->num_rows > 0) {
            while ($row = $result_top_events->fetch_assoc()) {
                $top_events[] = $row;
            }
        }
        
        break;
    
    default:
        $report_type = 'users';
        redirect('reports.php?type=users');
        break;
}

$conn->close();

// Page title
$page_title = 'System Reports';

// Include header
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people me-2"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="bi bi-calendar-event me-2"></i> Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="plans.php">
                            <i class="bi bi-grid-3x3-gap me-2"></i> Plans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="reports.php">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">System Reports</h1>
            </div>

            <?php display_flash_message(); ?>

            <!-- Report Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="report_type" class="form-label">Report Type</label>
                            <select class="form-select" id="report_type" name="type" onchange="this.form.submit()">
                                <option value="users" <?php echo $report_type == 'users' ? 'selected' : ''; ?>>User Registrations</option>
                                <option value="events" <?php echo $report_type == 'events' ? 'selected' : ''; ?>>Event Creations</option>
                                <option value="attendances" <?php echo $report_type == 'attendances' ? 'selected' : ''; ?>>Event Attendances</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Report Stats -->
            <div class="row mb-4">
                <?php if ($report_type == 'users'): ?>
                    <div class="col-md-2">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total Users</h5>
                                <h2><?php echo $user_stats['total_users']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h5 class="card-title">Active Users</h5>
                                <h2><?php echo $user_stats['active_users']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white bg-danger">
                            <div class="card-body text-center">
                                <h5 class="card-title">Inactive Users</h5>
                                <h2><?php echo $user_stats['inactive_users']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h5 class="card-title">Event Admins</h5>
                                <h2><?php echo $user_stats['event_admins']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-secondary">
                            <div class="card-body text-center">
                                <h5 class="card-title">Keygate Staff</h5>
                                <h2><?php echo $user_stats['keygate_staff']; ?></h2>
                            </div>
                        </div>
                    </div>
                <?php elseif ($report_type == 'events'): ?>
                    <div class="col-md-2">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total Events</h5>
                                <h2><?php echo $event_stats['total_events']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h5 class="card-title">Active</h5>
                                <h2><?php echo $event_stats['active_events']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h5 class="card-title">Pending</h5>
                                <h2><?php echo $event_stats['pending_events']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h5 class="card-title">Completed</h5>
                                <h2><?php echo $event_stats['completed_events']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body text-center">
                                <h5 class="card-title">Cancelled</h5>
                                <h2><?php echo $event_stats['cancelled_events']; ?></h2>
                            </div>
                        </div>
                    </div>
                <?php elseif ($report_type == 'attendances'): ?>
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total Check-ins</h5>
                                <h2><?php echo $attendance_stats['total_checkins']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h5 class="card-title">Events</h5>
                                <h2><?php echo $attendance_stats['total_events']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h5 class="card-title">Gates</h5>
                                <h2><?php echo $attendance_stats['total_gates']; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h5 class="card-title">Unique Delegates</h5>
                                <h2><?php echo $attendance_stats['total_delegates']; ?></h2>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php 
                        if ($report_type == 'users') {
                            echo 'User Registrations Over Time';
                        } elseif ($report_type == 'events') {
                            echo 'Event Creations Over Time';
                        } elseif ($report_type == 'attendances') {
                            echo 'Event Check-ins Over Time';
                        }
                        ?>
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="reportChart" height="300"></canvas>
                </div>
            </div>

            <?php if ($report_type == 'attendances' && !empty($top_events)): ?>
                <!-- Top Events by Attendance -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Top Events by Attendance</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Event ID</th>
                                    <th>Event Name</th>
                                    <th>Check-ins</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_events as $event): ?>
                                    <tr>
                                        <td><?php echo $event['id']; ?></td>
                                        <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                        <td><?php echo $event['checkin_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Raw Data Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Raw Data</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($report_data)): ?>
                                    <tr>
                                        <td colspan="2" class="text-center">No data found for the selected period.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($report_data as $data): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($data['date'])); ?></td>
                                            <td><?php echo $data['count']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
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
        // Prepare data for chart
        const reportData = <?php echo json_encode($report_data); ?>;
        const dates = reportData.map(item => item.date);
        const counts = reportData.map(item => item.count);
        
        // Create chart
        const ctx = document.getElementById('reportChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: '<?php 
                        if ($report_type == 'users') {
                            echo 'User Registrations';
                        } elseif ($report_type == 'events') {
                            echo 'Event Creations';
                        } elseif ($report_type == 'attendances') {
                            echo 'Event Check-ins';
                        }
                    ?>',
                    data: counts,
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        precision: 0
                    }
                }
            }
        });
    });
</script>

<?php
// Include footer
include '../includes/admin_footer.php';
?>