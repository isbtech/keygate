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
    set_flash_message('warning', 'You can only manage gates for active events');
    redirect('events.php');
}

// Process gate creation
if (isset($_POST['create_gate'])) {
    $gate_name = sanitize_input($_POST['gate_name']);
    $access_time_start = sanitize_input($_POST['access_time_start']);
    $access_time_end = sanitize_input($_POST['access_time_end']);
    
    if (empty($gate_name) || empty($access_time_start) || empty($access_time_end)) {
        set_flash_message('danger', 'Please fill in all required fields');
    } else {
        $result = create_access_gate($event_id, $gate_name, $access_time_start, $access_time_end, $_SESSION['user_id']);
        
        if ($result['success']) {
            set_flash_message('success', $result['message']);
            redirect('manage_gates.php?event_id=' . $event_id);
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
}

// Process gate status update
if (isset($_POST['update_gate_status'], $_POST['gate_id'], $_POST['status'])) {
    $gate_id = (int)$_POST['gate_id'];
    $status = sanitize_input($_POST['status']);
    
    $result = update_gate_status($gate_id, $status, $_SESSION['user_id']);
    
    if ($result['success']) {
        set_flash_message('success', $result['message']);
    } else {
        set_flash_message('danger', $result['message']);
    }
    
    redirect('manage_gates.php?event_id=' . $event_id);
}

// Get gates for this event
$gates = $event['gates'];

// Page title
$page_title = 'Manage Gates - ' . $event['event_name'];

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
                <h1 class="h2">Manage Gates - <?php echo htmlspecialchars($event['event_name']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="events.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Events
                    </a>
                    <a href="view_event.php?id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View Event Details
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
                            <p><strong>Gates:</strong> <?php echo count($gates); ?> / <?php echo $event['access_gates_required']; ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-<?php echo $event['status'] == 'Active' ? 'success' : 'secondary'; ?>">
                                    <?php echo $event['status']; ?>
                                </span>
                            </p>
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

            <div class="row">
                <!-- Create Gate Form -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Create New Gate</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($gates) >= $event['access_gates_required']): ?>
                                <div class="alert alert-warning">
                                    <p>You have reached the maximum number of gates allowed for this event.</p>
                                    <p>Maximum gates: <?php echo $event['access_gates_required']; ?></p>
                                </div>
                            <?php else: ?>
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="gate_name" class="form-label">Gate Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="gate_name" name="gate_name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="access_time_start" class="form-label">Access Start Time <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="access_time_start" name="access_time_start" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="access_time_end" class="form-label">Access End Time <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control" id="access_time_end" name="access_time_end" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" name="create_gate" class="btn btn-primary">Create Gate</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Gates List -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Access Gates</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($gates)): ?>
                                <div class="alert alert-info">
                                    <p>No gates have been created yet. Create your first gate using the form.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Gate Name</th>
                                                <th>Access Time</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($gates as $gate): ?>
                                                <tr>
                                                    <td><?php echo $gate['id']; ?></td>
                                                    <td><?php echo htmlspecialchars($gate['gate_name']); ?></td>
                                                    <td><?php echo date('h:i A', strtotime($gate['access_time_start'])); ?> - <?php echo date('h:i A', strtotime($gate['access_time_end'])); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $gate['gate_status'] == 'Open' ? 'success' : 'danger'; ?>">
                                                            <?php echo $gate['gate_status']; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="gate_id" value="<?php echo $gate['id']; ?>">
                                                            <input type="hidden" name="status" value="<?php echo $gate['gate_status'] == 'Open' ? 'Close' : 'Open'; ?>">
                                                            <button type="submit" name="update_gate_status" class="btn btn-sm btn-<?php echo $gate['gate_status'] == 'Open' ? 'danger' : 'success'; ?>">
                                                                <?php echo $gate['gate_status'] == 'Open' ? 'Close Gate' : 'Open Gate'; ?>
                                                            </button>
                                                        </form>
                                                        <a href="gate_stats.php?gate_id=<?php echo $gate['id']; ?>&event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-info">
                                                            Statistics
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Gate Information -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Gate Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h5>Managing Gates</h5>
                                <p>Gates control delegate access to your event. Here's how to use them effectively:</p>
                                <ul>
                                    <li>Create different gates for different entry points or areas of your event.</li>
                                    <li>Set access time windows for each gate to control when delegates can enter.</li>
                                    <li>Open/close gates as needed during the event.</li>
                                    <li>Each gate can be assigned to gatekeepers who will scan and validate delegate barcodes.</li>
                                </ul>
                                <p>Remember that delegates need to be specifically granted access to a gate to be allowed entry.</p>
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