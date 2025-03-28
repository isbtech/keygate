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

// Check if event ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'Event ID is required');
    redirect('events.php');
}

$event_id = (int)$_GET['id'];

// Get event details
$event_details = get_event_details($event_id, $_SESSION['user_id']);

if (!$event_details['success']) {
    set_flash_message('danger', $event_details['message']);
    redirect('events.php');
}

$event = $event_details['event'];

// Get delegates count
$delegates_result = get_event_delegates($event_id, $_SESSION['user_id']);
$delegates_count = 0;
if ($delegates_result['success']) {
    $delegates_count = count($delegates_result['delegates']);
}

// Get event users count
$event_users_result = get_event_users($event_id, $_SESSION['user_id']);
$event_users_count = 0;
if ($event_users_result['success']) {
    $event_users_count = count($event_users_result['event_users']);
}

// Page title
$page_title = 'View Event - ' . $event['event_name'];

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
                <h1 class="h2"><?php echo htmlspecialchars($event['event_name']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="events.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Events
                    </a>
                    <?php if ($event['status'] == 'Active'): ?>
                        <div class="btn-group me-2">
                            <a href="manage_gates.php?event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-door-open"></i> Manage Gates
                            </a>
                            <a href="manage_delegates.php?event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-success">
                                <i class="bi bi-person-badge"></i> Manage Delegates
                            </a>
                            <a href="manage_staff.php?event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-people"></i> Manage Staff
                            </a>
                        </div>
                        <a href="event_report.php?event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-graph-up"></i> View Reports
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Event Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Event Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Event Name:</th>
                                    <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>Organizer:</th>
                                    <td><?php echo htmlspecialchars($event['event_organiser']); ?></td>
                                </tr>
                                <tr>
                                    <th>Venue:</th>
                                    <td><?php echo htmlspecialchars($event['event_venue']); ?></td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
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
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <th width="30%">Event Dates:</th>
                                    <td>
                                        <?php if (!empty($event['dates'])): ?>
                                            <?php foreach ($event['dates'] as $date): ?>
                                                <div><?php echo date('F j, Y', strtotime($date)); ?></div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">No dates specified</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Created On:</th>
                                    <td><?php echo date('F j, Y, g:i a', strtotime($event['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Gates:</th>
                                    <td><?php echo count($event['gates']); ?> / <?php echo $event['access_gates_required']; ?></td>
                                </tr>
                                <tr>
                                    <th>Auto Approval:</th>
                                    <td>
                                        <?php 
                                        $user = get_record('users', "id = {$_SESSION['user_id']}");
                                        $auto_approval = 'No';
                                        if ($user && $user['plan_id']) {
                                            $plan = get_record('plans', "id = {$user['plan_id']}");
                                            if ($plan && $plan['event_auto_approval'] == 'yes') {
                                                $auto_approval = 'Yes';
                                            }
                                        }
                                        echo $auto_approval;
                                        ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <h3><?php echo count($event['gates']); ?></h3>
                            <p class="mb-0">Gates</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $delegates_count; ?></h3>
                            <p class="mb-0">Delegates</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <h3><?php echo $event_users_count; ?></h3>
                            <p class="mb-0">Staff Members</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark">
                        <div class="card-body text-center">
                            <h3><?php echo $event['total_check_ins']; ?></h3>
                            <p class="mb-0">Check-ins</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gates List -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Access Gates</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($event['gates'])): ?>
                        <div class="alert alert-info">
                            <p>No gates have been created for this event yet.</p>
                            <?php if ($event['status'] == 'Active'): ?>
                                <a href="manage_gates.php?event_id=<?php echo $event_id; ?>" class="btn btn-primary btn-sm">Create Gates</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Gate Name</th>
                                        <th>Access Time</th>
                                        <th>Status</th>
                                        <?php if ($event['status'] == 'Active'): ?>
                                            <th>Actions</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($event['gates'] as $gate): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($gate['gate_name']); ?></td>
                                            <td><?php echo date('h:i A', strtotime($gate['access_time_start'])); ?> - <?php echo date('h:i A', strtotime($gate['access_time_end'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $gate['gate_status'] == 'Open' ? 'success' : 'danger'; ?>">
                                                    <?php echo $gate['gate_status']; ?>
                                                </span>
                                            </td>
                                            <?php if ($event['status'] == 'Active'): ?>
                                                <td>
                                                    <a href="gate_stats.php?gate_id=<?php echo $gate['id']; ?>&event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-primary">
                                                        View Statistics
                                                    </a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($event['status'] == 'Active' && count($event['gates']) < $event['access_gates_required']): ?>
                            <div class="mt-3">
                                <a href="manage_gates.php?event_id=<?php echo $event_id; ?>" class="btn btn-primary btn-sm">
                                    <i class="bi bi-plus-circle"></i> Add More Gates
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <?php if ($event['status'] == 'Active'): ?>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body text-center">
                                <h5 class="mb-3">Event Management Actions</h5>
                                <a href="manage_gates.php?event_id=<?php echo $event_id; ?>" class="btn btn-primary me-2">
                                    <i class="bi bi-door-open"></i> Manage Gates
                                </a>
                                <a href="delegates.php?event_id=<?php echo $event_id; ?>" class="btn btn-success me-2">
                                    <i class="bi bi-person-badge"></i> Manage Delegates
                                </a>
                                <a href="event_users.php?event_id=<?php echo $event_id; ?>" class="btn btn-info me-2 text-white">
                                    <i class="bi bi-people"></i> Manage Staff
                                </a>
                                <a href="event_report.php?event_id=<?php echo $event_id; ?>" class="btn btn-secondary">
                                    <i class="bi bi-graph-up"></i> View Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php
// Include footer
include '../includes/event_admin_footer.php';
?>