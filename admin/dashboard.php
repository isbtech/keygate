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

// Get dashboard stats
$stats = get_admin_dashboard_stats();

// Page title
$page_title = 'Admin Dashboard';

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
                        <a class="nav-link active" href="dashboard.php">
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
                        <a class="nav-link" href="reports.php">
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
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="create_user.php" class="btn btn-sm btn-outline-secondary">Add User</a>
                        <a href="pending_events.php" class="btn btn-sm btn-outline-primary">Pending Events</a>
                    </div>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Users</h6>
                                    <h2><?php echo $stats['total_users']; ?></h2>
                                </div>
                                <i class="bi bi-people-fill fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Events</h6>
                                    <h2><?php echo $stats['total_events']; ?></h2>
                                </div>
                                <i class="bi bi-calendar-event-fill fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Active Events</h6>
                                    <h2><?php echo $stats['events_by_status']['Active']; ?></h2>
                                </div>
                                <i class="bi bi-lightning-fill fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Pending Approvals</h6>
                                    <h2><?php echo $stats['pending_approvals']; ?></h2>
                                </div>
                                <i class="bi bi-hourglass-split fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events by Status Chart -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Events by Status</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="eventsByStatusChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Users -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Users</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['recent_users'] as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars($user['role']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $user['activated'] == 'yes' ? 'success' : 'danger'; ?>">
                                                        <?php echo $user['activated'] == 'yes' ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end">
                                <a href="users.php" class="btn btn-sm btn-outline-primary">View All Users</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Events -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Recent Events</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Event Name</th>
                                            <th>Organizer</th>
                                            <th>Created By</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['recent_events'] as $event): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                                <td><?php echo htmlspecialchars($event['event_organiser']); ?></td>
                                                <td><?php echo htmlspecialchars($event['created_by_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        if ($event['status'] == 'Active') echo 'success';
                                                        elseif ($event['status'] == 'Pending Approval') echo 'warning';
                                                        elseif ($event['status'] == 'Cancelled') echo 'danger';
                                                        elseif ($event['status'] == 'Completed') echo 'info';
                                                        else echo 'secondary';
                                                    ?>">
                                                        <?php echo $event['status']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end">
                                <a href="events.php" class="btn btn-sm btn-outline-primary">View All Events</a>
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
    // Events by Status Chart
    const statusLabels = <?php echo json_encode(array_keys($stats['events_by_status'])); ?>;
    const statusData = <?php echo json_encode(array_values($stats['events_by_status'])); ?>;
    const statusColors = [
        'rgba(40, 167, 69, 0.8)',  // Active - Green
        'rgba(23, 162, 184, 0.8)', // Completed - Cyan
        'rgba(220, 53, 69, 0.8)',  // Cancelled - Red
        'rgba(108, 117, 125, 0.8)', // Scheduled - Gray
        'rgba(255, 193, 7, 0.8)'   // Pending Approval - Yellow
    ];
    
    const ctx = document.getElementById('eventsByStatusChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: statusLabels,
            datasets: [{
                label: 'Number of Events',
                data: statusData,
                backgroundColor: statusColors,
                borderColor: statusColors,
                borderWidth: 1
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
</script>

<?php
// Include footer
include '../includes/admin_footer.php';
?>