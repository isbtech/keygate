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

// Check if event is active
if ($event['status'] != 'Active') {
    set_flash_message('warning', 'You can only manage staff for active events');
    redirect('events.php');
}

// Get event users for this event
$event_users_result = get_event_users($event_id, $_SESSION['user_id']);
$event_users = [];
if ($event_users_result['success']) {
    $event_users = $event_users_result['event_users'];
}

// Get gates for this event
$gates = $event['gates'];

// Get roles for dropdown
$conn = db_connect();
$roles_query = "SELECT * FROM event_user_roles ORDER BY id ASC";
$roles_result = $conn->query($roles_query);
$roles = [];
if ($roles_result->num_rows > 0) {
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Process gate assignment for gatekeepers
if (isset($_POST['assign_gate'])) {
    $user_id = (int)$_POST['user_id'];
    $gate_id = (int)$_POST['gate_id'];
    
    // Update event user's last active gate
    $updated = update_record('event_users', 
        ['last_active_gate' => $gate_id], 
        "id = $user_id AND event_id = $event_id"
    );
    
    if ($updated) {
        set_flash_message('success', 'Gate assigned successfully');
    } else {
        set_flash_message('danger', 'Failed to assign gate');
    }
    
    redirect('manage_staff.php?event_id=' . $event_id);
}

// Get staff by role
$gatekeepers = [];
$event_managers = [];
$volunteers = [];

foreach ($event_users as $user) {
    if ($user['role_name'] == 'GateKeeper') {
        $gatekeepers[] = $user;
    } elseif ($user['role_name'] == 'Event Manager') {
        $event_managers[] = $user;
    } elseif ($user['role_name'] == 'Volunteer') {
        $volunteers[] = $user;
    }
}

// Page title
$page_title = 'Manage Staff - ' . $event['event_name'];

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
                        <a class="nav-link active text-white" href="event_users.php">
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
                <h1 class="h2">Manage Staff - <?php echo htmlspecialchars($event['event_name']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="events.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Events
                    </a>
                    <a href="event_users.php?event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-person-plus"></i> Add Staff
                    </a>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Event Info Card -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['event_organiser']); ?></p>
                            <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['event_venue']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Gates:</strong> <?php echo count($gates); ?></p>
                            <p><strong>Staff:</strong> <?php echo count($event_users); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Dates:</strong></p>
                            <ul class="list-unstyled">
                                <?php foreach ($event['dates'] as $date): ?>
                                    <li><?php echo date('M d, Y', strtotime($date)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Management -->
            <div class="row">
                <!-- Gatekeepers -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Gatekeepers</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($gatekeepers)): ?>
                                <div class="alert alert-warning">
                                    <p>No gatekeepers assigned to this event yet.</p>
                                    <a href="event_users.php?event_id=<?php echo $event_id; ?>" class="btn btn-primary btn-sm">Add Gatekeepers</a>
                                </div>
                            <?php elseif (empty($gates)): ?>
                                <div class="alert alert-warning">
                                    <p>You need to create gates before assigning gatekeepers.</p>
                                    <a href="manage_gates.php?event_id=<?php echo $event_id; ?>" class="btn btn-primary btn-sm">Create Gates</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Assigned Gate</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($gatekeepers as $gatekeeper): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($gatekeeper['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($gatekeeper['email']); ?></td>
                                                    <td>
                                                        <?php if ($gatekeeper['last_active_gate']): ?>
                                                            <span class="badge bg-success"><?php echo htmlspecialchars($gatekeeper['last_active_gate_name']); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning">Not Assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignGateModal<?php echo $gatekeeper['id']; ?>">
                                                            Assign Gate
                                                        </button>
                                                        
                                                        <!-- Assign Gate Modal -->
                                                        <div class="modal fade" id="assignGateModal<?php echo $gatekeeper['id']; ?>" tabindex="-1" aria-labelledby="assignGateModalLabel<?php echo $gatekeeper['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="assignGateModalLabel<?php echo $gatekeeper['id']; ?>">Assign Gate to <?php echo htmlspecialchars($gatekeeper['name']); ?></h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <form method="post">
                                                                        <div class="modal-body">
                                                                            <input type="hidden" name="user_id" value="<?php echo $gatekeeper['id']; ?>">
                                                                            <div class="mb-3">
                                                                                <label for="gate_id<?php echo $gatekeeper['id']; ?>" class="form-label">Select Gate</label>
                                                                                <select class="form-select" id="gate_id<?php echo $gatekeeper['id']; ?>" name="gate_id" required>
                                                                                    <option value="">-- Select Gate --</option>
                                                                                    <?php foreach ($gates as $gate): ?>
                                                                                        <option value="<?php echo $gate['id']; ?>" <?php echo $gatekeeper['last_active_gate'] == $gate['id'] ? 'selected' : ''; ?>>
                                                                                            <?php echo htmlspecialchars($gate['gate_name']); ?> 
                                                                                            (<?php echo date('h:i A', strtotime($gate['access_time_start'])); ?> - <?php echo date('h:i A', strtotime($gate['access_time_end'])); ?>)
                                                                                            <?php echo $gate['gate_status'] == 'Open' ? '- OPEN' : '- CLOSED'; ?>
                                                                                        </option>
                                                                                    <?php endforeach; ?>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="submit" name="assign_gate" class="btn btn-primary">Assign Gate</button>
                                                                        </div>
                                                                    </form>
                                                                </div>
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
                </div>
                
                <!-- Event Managers & Volunteers -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Event Managers</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($event_managers)): ?>
                                <div class="alert alert-info">
                                    <p>No event managers assigned to this event yet.</p>
                                    <a href="event_users.php?event_id=<?php echo $event_id; ?>" class="btn btn-primary btn-sm">Add Event Managers</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Last Active</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($event_managers as $manager): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($manager['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($manager['email']); ?></td>
                                                    <td>
                                                        <?php if ($manager['last_active']): ?>
                                                            <?php echo date('M d, Y H:i', strtotime($manager['last_active'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Never</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Volunteers</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($volunteers)): ?>
                                <div class="alert alert-info">
                                    <p>No volunteers assigned to this event yet.</p>
                                    <a href="event_users.php?event_id=<?php echo $event_id; ?>" class="btn btn-primary btn-sm">Add Volunteers</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Last Active</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($volunteers as $volunteer): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($volunteer['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($volunteer['email']); ?></td>
                                                    <td>
                                                        <?php if ($volunteer['last_active']): ?>
                                                            <?php echo date('M d, Y H:i', strtotime($volunteer['last_active'])); ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Never</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
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