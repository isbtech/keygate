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

// Get user's active events for the dropdown
$user_events = get_event_admin_events($_SESSION['user_id']);
$active_events = array_filter($user_events, function($event) {
    return $event['status'] == 'Active';
});

// Set selected event
$selected_event_id = 0;
if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
    $selected_event_id = (int)$_GET['event_id'];
} elseif (!empty($active_events)) {
    // Default to first active event
    $selected_event_id = $active_events[array_key_first($active_events)]['id'];
}

// Get event details if an event is selected
$event = null;
$gates = [];
$delegates = [];

if ($selected_event_id > 0) {
    $event_details = get_event_details($selected_event_id, $_SESSION['user_id']);
    if ($event_details['success']) {
        $event = $event_details['event'];
        $gates = $event['gates'];
        
        // Get delegates for this event
        $delegates_result = get_event_delegates($selected_event_id, $_SESSION['user_id']);
        if ($delegates_result['success']) {
            $delegates = $delegates_result['delegates'];
        }
    }
}

// Process delegate creation
if (isset($_POST['create_delegate'])) {
    $event_id = (int)$_POST['event_id'];
    $name = sanitize_input($_POST['name']);
    $designation = sanitize_input($_POST['designation']);
    $email = sanitize_input($_POST['email']);
    $mobile = sanitize_input($_POST['mobile']);
    
    // Get selected gates
    $access_gates = isset($_POST['access_gates']) ? $_POST['access_gates'] : [];
    
    if (empty($name) || empty($email) || empty($mobile) || empty($access_gates)) {
        set_flash_message('danger', 'Please fill in all required fields and select at least one gate');
    } else {
        $result = create_event_delegate($event_id, $name, $designation, $email, $mobile, $access_gates, $_SESSION['user_id']);
        
        if ($result['success']) {
            set_flash_message('success', $result['message']);
            redirect('delegates.php?event_id=' . $event_id);
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
}

// Check if can add more delegates
$can_add_delegates = false;
$delegates_limit = 0;
if ($event) {
    $user = get_record('users', "id = {$_SESSION['user_id']}");
    if ($user && $user['plan_id']) {
        $plan = get_record('plans', "id = {$user['plan_id']}");
        if ($plan) {
            $delegates_limit = $plan['delegates_per_event'];
            $can_add_delegates = count($delegates) < $delegates_limit;
        }
    }
}

// Bulk import delegates - this would be handled in a separate file
$import_url = "import_delegates.php" . ($selected_event_id > 0 ? "?event_id=$selected_event_id" : "");

// Page title
$page_title = 'Manage Delegates';

// Additional CSS for select2
$additional_css = '<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />';

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
                        <a class="nav-link active text-white" href="delegates.php">
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
                <h1 class="h2">Manage Delegates</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="events.php" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Events
                    </a>
                    <?php if ($selected_event_id > 0): ?>
                        <a href="<?php echo $import_url; ?>" class="btn btn-sm btn-outline-success me-2">
                            <i class="bi bi-upload"></i> Bulk Import
                        </a>
                        <a href="export_delegates.php?event_id=<?php echo $selected_event_id; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download"></i> Export CSV
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Event Selector -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-6">
                            <label for="event_id" class="form-label">Select Event</label>
                            <select class="form-select" id="event_id" name="event_id" onchange="this.form.submit()">
                                <option value="">-- Select an Event --</option>
                                <?php foreach ($active_events as $evt): ?>
                                    <option value="<?php echo $evt['id']; ?>" <?php echo $selected_event_id == $evt['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($evt['event_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($event): ?>
                            <div class="col-md-6">
                                <div class="mt-4">
                                    <span class="badge bg-primary">Event: <?php echo htmlspecialchars($event['event_name']); ?></span>
                                    <span class="badge bg-info">Delegates: <?php echo count($delegates); ?> / <?php echo $delegates_limit; ?></span>
                                    <span class="badge bg-success">Gates: <?php echo count($gates); ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- No Event Selected Message -->
            <?php if (empty($active_events)): ?>
                <div class="alert alert-warning">
                    <h5>No Active Events Found</h5>
                    <p>You don't have any active events. Delegates can only be added to active events.</p>
                    <p><a href="events.php" class="btn btn-primary btn-sm">View My Events</a> or <a href="create_event.php" class="btn btn-success btn-sm">Create New Event</a></p>
                </div>
            <?php elseif ($selected_event_id == 0): ?>
                <div class="alert alert-info">
                    <h5>Select an Event</h5>
                    <p>Please select an event from the dropdown above to manage its delegates.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Create Delegate Form -->
                    <div class="col-md-4">
                        <div class="card sticky-top" style="top: 20px; z-index: 1000;">
                            <div class="card-header">
                                <h5 class="mb-0">Add New Delegate</h5>
                            </div>
                            <div class="card-body">
                                <?php if (!$can_add_delegates): ?>
                                    <div class="alert alert-warning">
                                        <p>You have reached the maximum number of delegates allowed for this event (<?php echo $delegates_limit; ?>).</p>
                                        <p>Please upgrade your plan to add more delegates or remove some existing delegates.</p>
                                    </div>
                                <?php elseif (empty($gates)): ?>
                                    <div class="alert alert-warning">
                                        <p>You need to create at least one gate before adding delegates.</p>
                                        <p><a href="manage_gates.php?event_id=<?php echo $selected_event_id; ?>" class="btn btn-primary btn-sm">Create Gates</a></p>
                                    </div>
                                <?php else: ?>
                                    <form method="post">
                                        <input type="hidden" name="event_id" value="<?php echo $selected_event_id; ?>">
                                        
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="name" name="name" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="designation" class="form-label">Designation</label>
                                            <input type="text" class="form-control" id="designation" name="designation">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                            <input type="email" class="form-control" id="email" name="email" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="mobile" name="mobile" required>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="access_gates" class="form-label">Access Gates <span class="text-danger">*</span></label>
                                            <select class="form-select select2" id="access_gates" name="access_gates[]" multiple required>
                                                <?php foreach ($gates as $gate): ?>
                                                    <option value="<?php echo $gate['id']; ?>">
                                                        <?php echo htmlspecialchars($gate['gate_name']); ?> 
                                                        (<?php echo date('h:i A', strtotime($gate['access_time_start'])); ?> - <?php echo date('h:i A', strtotime($gate['access_time_end'])); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">Select multiple gates by holding Ctrl or Cmd key</div>
                                        </div>
                                        
                                        <div class="d-grid">
                                            <button type="submit" name="create_delegate" class="btn btn-primary">Add Delegate</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Delegates List -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Delegates List</h5>
                                <input type="text" id="delegateSearch" class="form-control form-control-sm w-50" placeholder="Search by name, email, or designation...">
                            </div>
                            <div class="card-body">
                                <?php if (empty($delegates)): ?>
                                    <div class="alert alert-info">
                                        <p>No delegates found for this event. Use the form to add delegates.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="delegatesTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Designation</th>
                                                    <th>Email / Mobile</th>
                                                    <th>Access Gates</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($delegates as $delegate): ?>
                                                    <tr>
                                                        <td><?php echo $delegate['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($delegate['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($delegate['designation']); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($delegate['email']); ?><br>
                                                            <small><?php echo htmlspecialchars($delegate['mobile']); ?></small>
                                                        </td>
                                                        <td>
                                                            <?php if (empty($delegate['gates'])): ?>
                                                                <span class="badge bg-danger">No Access</span>
                                                            <?php else: ?>
                                                                <?php foreach ($delegate['gates'] as $index => $gate): ?>
                                                                    <?php if ($index < 2): ?>
                                                                        <span class="badge bg-primary"><?php echo htmlspecialchars($gate['gate_name']); ?></span>
                                                                    <?php elseif ($index == 2): ?>
                                                                        <span class="badge bg-secondary">+<?php echo (count($delegate['gates']) - 2); ?> more</span>
                                                                        <?php break; ?>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($delegate['checked_in']): ?>
                                                                <span class="badge bg-success">Checked In</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-warning">Not Checked In</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <a href="view_delegate.php?id=<?php echo $delegate['id']; ?>&event_id=<?php echo $selected_event_id; ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                                <a href="edit_delegate.php?id=<?php echo $delegate['id']; ?>&event_id=<?php echo $selected_event_id; ?>" class="btn btn-sm btn-outline-secondary">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $delegate['id']; ?>">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                            
                                                            <!-- Delete Modal -->
                                                            <div class="modal fade" id="deleteModal<?php echo $delegate['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $delegate['id']; ?>" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $delegate['id']; ?>">Confirm Delete</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <p>Are you sure you want to delete the delegate: <strong><?php echo htmlspecialchars($delegate['name']); ?></strong>?</p>
                                                                            <p class="text-danger">This action cannot be undone.</p>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <a href="delete_delegate.php?id=<?php echo $delegate['id']; ?>&event_id=<?php echo $selected_event_id; ?>" class="btn btn-danger">Delete</a>
                                                                        </div>
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
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Select2 and Search functionality -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize Select2
        $('.select2').select2({
            placeholder: 'Select gates',
            width: '100%'
        });
        
        // Initialize search functionality
        $("#delegateSearch").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#delegatesTable tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>

<?php
// Include footer
include '../includes/event_admin_footer.php';
?>