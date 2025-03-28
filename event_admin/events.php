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

// Get current user's events
$events = get_event_admin_events($_SESSION['user_id']);

// Check if user can create more events
$can_create = can_create_event($_SESSION['user_id']);

// Page title
$page_title = 'My Events';

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
                <h1 class="h2">My Events</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($can_create['can_create']): ?>
                        <a href="create_event.php" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle"></i> Create New Event
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <?php if (!$can_create['can_create']): ?>
                <div class="alert alert-warning">
                    <p><strong>Note:</strong> <?php echo $can_create['message']; ?></p>
                    <p>Please contact administrator to upgrade your plan if you need to create more events.</p>
                </div>
            <?php endif; ?>

            <!-- Events Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Name</th>
                                    <th>Organizer</th>
                                    <th>Venue</th>
                                    <th>Dates</th>
                                    <th>Gates</th>
                                    <th>Delegates</th>
                                    <th>Status</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($events)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No events found. <a href="create_event.php">Create your first event</a>.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($events as $event): ?>
                                        <tr>
                                            <td><?php echo $event['id']; ?></td>
                                            <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                            <td><?php echo htmlspecialchars($event['event_organiser']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($event['event_venue'], 0, 30)) . (strlen($event['event_venue']) > 30 ? '...' : ''); ?></td>
                                            <td>
                                                <?php if (!empty($event['dates'])): ?>
                                                    <?php foreach ($event['dates'] as $index => $date): ?>
                                                        <?php if ($index < 2): ?>
                                                            <?php echo date('M d, Y', strtotime($date)); ?><br>
                                                        <?php elseif ($index == 2): ?>
                                                            <span class="text-muted">+<?php echo (count($event['dates']) - 2); ?> more</span>
                                                            <?php break; ?>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No dates</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $event['gates_count']; ?> / <?php echo $event['access_gates_required']; ?></td>
                                            <td><?php echo $event['delegates_count']; ?></td>
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
                                                    <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    
                                                    <?php if ($event['status'] == 'Active'): ?>
                                                        <a href="manage_gates.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-success" title="Manage Gates">
                                                            <i class="bi bi-door-open"></i>
                                                        </a>
                                                        <a href="manage_delegates.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-info" title="Manage Delegates">
                                                            <i class="bi bi-person-badge"></i>
                                                        </a>
                                                        <a href="manage_staff.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-warning" title="Manage Staff">
                                                            <i class="bi bi-people"></i>
                                                        </a>
                                                        <a href="event_report.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-secondary" title="View Reports">
                                                            <i class="bi bi-graph-up"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Event Status Guide -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Event Status Guide</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-warning me-2">Pending Approval</span>
                                <span>Event awaiting admin approval</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-secondary me-2">Scheduled</span>
                                <span>Event approved but not yet active</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-success me-2">Active</span>
                                <span>Event is currently active</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-info me-2">Completed</span>
                                <span>Event has ended</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include footer
include '../includes/event_admin_footer.php';
?>