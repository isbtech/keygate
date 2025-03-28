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

// Get event admin stats
$stats = get_event_admin_stats($_SESSION['user_id']);

// Page title
$page_title = 'Event Admin Dashboard';

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
                        <a class="nav-link active text-white" href="dashboard.php">
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
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <?php if ($stats['events_remaining'] > 0): ?>
                            <a href="create_event.php" class="btn btn-sm btn-outline-primary">Create New Event</a>
                        <?php endif; ?>
                        <a href="events.php" class="btn btn-sm btn-outline-secondary">View All Events</a>
                    </div>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Plan Info -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5 class="card-title">Your Plan</h5>
                            <?php if ($stats['plan']): ?>
                                <div class="row">
                                    <div class="col-md-3">
                                        <p><strong>Plan Name:</strong> <?php echo htmlspecialchars($stats['plan']['plan_name']); ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Events Allowed:</strong> <?php echo $stats['plan']['events_allowed']; ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Events Remaining:</strong> <?php echo $stats['events_remaining']; ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <p><strong>Delegates Per Event:</strong> <?php echo $stats['plan']['delegates_per_event']; ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    No plan assigned. Please contact admin to assign a plan.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
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
                    <div class="card text-white bg-success">
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
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Pending Events</h6>
                                    <h2><?php echo $stats['events_by_status']['Pending Approval']; ?></h2>
                                </div>
                                <i class="bi bi-hourglass-split fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Completed Events</h6>
                                    <h2><?php echo $stats['events_by_status']['Completed']; ?></h2>
                                </div>
                                <i class="bi bi-check-circle-fill fs-1"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events by Status Chart -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Events by Status</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="eventsByStatusChart" height="250"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php if ($stats['events_remaining'] > 0): ?>
                                    <a href="create_event.php" class="list-group-item list-group-item-action">
                                        <i class="bi bi-plus-circle me-2"></i> Create New Event
                                    </a>
                                <?php endif; ?>
                                <a href="events.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-list-check me-2"></i> Manage Events
                                </a>
                                <a href="delegates.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-person-plus me-2"></i> Add Delegates
                                </a>
                                <a href="event_users.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-people me-2"></i> Manage Event Staff
                                </a>
                                <a href="reports.php" class="list-group-item list-group-item-action">
                                    <i class="bi bi-file-earmark-bar-graph me-2"></i> View Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Events -->
            <div class="row">
                <div class="col-md-12">
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
                                            <th>Venue</th>
                                            <th>Status</th>
                                            <th>Created On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($stats['recent_events'])): ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No events found.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($stats['recent_events'] as $event): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($event['event_organiser']); ?></td>
                                                    <td><?php echo htmlspecialchars(substr($event['event_venue'], 0, 30)) . (strlen($event['event_venue']) > 30 ? '...' : ''); ?></td>
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
                                                    <td><?php echo date('M d, Y', strtotime($event['created_at'])); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                                            <?php if ($event['status'] == 'Active'): ?>
                                                                <a href="manage_gates.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-success">Gates</a>
                                                                <a href="manage_delegates.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-info">Delegates</a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (!empty($stats['recent_events'])): ?>
                                <div class="text-end">
                                    <a href="events.php" class="btn btn-sm btn-outline-primary">View All Events</a>
                                </div>
                            <?php endif; ?>
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
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                label: 'Events',
                data: statusData,
                backgroundColor: statusColors,
                borderColor: statusColors,
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
</script>

<?php
// Include footer
include '../includes/event_admin_footer.php';
?>