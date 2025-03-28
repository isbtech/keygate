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
    set_flash_message('warning', 'You can only manage delegates for active events');
    redirect('events.php');
}

// Get gates for this event
$gates = $event['gates'];

// Get delegates for this event
$delegates_result = get_event_delegates($event_id, $_SESSION['user_id']);
$delegates = [];
if ($delegates_result['success']) {
    $delegates = $delegates_result['delegates'];
}

// Check if can add more delegates
$can_add_delegates = false;
$delegates_limit = 0;
$user = get_record('users', "id = {$_SESSION['user_id']}");
if ($user && $user['plan_id']) {
    $plan = get_record('plans', "id = {$user['plan_id']}");
    if ($plan) {
        $delegates_limit = $plan['delegates_per_event'];
        $can_add_delegates = count($delegates) < $delegates_limit;
    }
}

// Process delegate creation
if (isset($_POST['create_delegate'])) {
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
            redirect('manage_delegates.php?event_id=' . $event_id);
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
}

// Process delegate deletion
if (isset($_POST['delete_delegate'])) {
    $delegate_id = (int)$_POST['delegate_id'];
    
    // Check if delegate exists and belongs to this event
    $delegate = get_record('event_delegates', "id = $delegate_id AND event_id = $event_id");
    
    if ($delegate) {
        // Delete delegate
        $deleted = delete_record('event_delegates', "id = $delegate_id");
        
        if ($deleted) {
            // Delete associated gate access permissions
            delete_record('delegate_gate_access', "delegate_id = $delegate_id");
            
            // Delete attendance records
            delete_record('event_attendance', "delegate_id = $delegate_id");
            
            set_flash_message('success', 'Delegate deleted successfully');
        } else {
            set_flash_message('danger', 'Failed to delete delegate');
        }
    } else {
        set_flash_message('danger', 'Delegate not found or not part of this event');
    }
    
    redirect('manage_delegates.php?event_id=' . $event_id);
}

// Page title
$page_title = 'Manage Delegates - ' . $event['event_name'];

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
                <h1 class="h2">Manage Delegates - <?php echo htmlspecialchars($event['event_name']); ?></h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="view_event.php?id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left"></i> Back to Event
                    </a>
                    <a href="import_delegates.php?event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-success me-2">
                        <i class="bi bi-upload"></i> Bulk Import
                    </a>
                    <a href="export_report.php?event_id=<?php echo $event_id; ?>&type=meeting" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download"></i> Export CSV
                    </a>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Event Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <p><strong>Event:</strong> <?php echo htmlspecialchars($event['event_name']); ?></p>
                            <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['event_organiser']); ?></p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Venue:</strong> <?php echo htmlspecialchars($event['event_venue']); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge bg-success">
                                    <?php echo $event['status']; ?>
                                </span>
                            </p>
                        </div>
                        <div class="col-md-4">
                            <p><strong>Delegates:</strong> <?php echo count($delegates); ?> / <?php echo $delegates_limit; ?></p>
                            <p><strong>Gates:</strong> <?php echo count($gates); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Create Delegate Form -->
                <div class="col-md-4">
                    <div class="card">
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
                                    <p><a href="manage_gates.php?event_id=<?php echo $event_id; ?>" class="btn btn-primary btn-sm">Create Gates</a></p>
                                </div>
                            <?php else: ?>
                                <form method="post">
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
                    
                    <!-- Stats Card -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Delegate Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="card bg-light text-center">
                                        <div class="card-body">
                                            <h3><?php echo count($delegates); ?></h3>
                                            <p class="mb-0">Total</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-success text-white text-center">
                                        <div class="card-body">
                                            <?php 
                                            $checked_in = 0;
                                            foreach ($delegates as $delegate) {
                                                if ($delegate['checked_in']) {
                                                    $checked_in++;
                                                }
                                            }
                                            ?>
                                            <h3><?php echo $checked_in; ?></h3>
                                            <p class="mb-0">Checked In</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <div class="progress" style="height: 25px;">
                                    <?php $percentage = count($delegates) > 0 ? round(($checked_in / count($delegates)) * 100, 2) : 0; ?>
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%;" 
                                         aria-valuenow="<?php echo $percentage; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $percentage; ?>%
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <small>Checked-in Percentage</small>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <a href="event_report.php?event_id=<?php echo $event_id; ?>&type=meeting" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="bi bi-graph-up"></i> Detailed Statistics
                                </a>
                            </div>
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
                                                <th>Name</th>
                                                <th>Email / Mobile</th>
                                                <th>Designation</th>
                                                <th>Access Gates</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($delegates as $delegate): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($delegate['name']); ?></td>
                                                    <td>
                                                        <?php echo htmlspecialchars($delegate['email']); ?><br>
                                                        <small><?php echo htmlspecialchars($delegate['mobile']); ?></small>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($delegate['designation']); ?></td>
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
                                                            <a href="edit_delegate.php?id=<?php echo $delegate['id']; ?>&event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-primary">
                                                                <i class="bi bi-pencil"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $delegate['id']; ?>">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#barcodeModal<?php echo $delegate['id']; ?>">
                                                                <i class="bi bi-upc-scan"></i>
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
                                                                        <p class="text-danger">This action cannot be undone. All attendance records for this delegate will also be deleted.</p>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <form method="post">
                                                                            <input type="hidden" name="delegate_id" value="<?php echo $delegate['id']; ?>">
                                                                            <button type="submit" name="delete_delegate" class="btn btn-danger">Delete</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- Barcode Modal -->
                                                        <div class="modal fade" id="barcodeModal<?php echo $delegate['id']; ?>" tabindex="-1" aria-labelledby="barcodeModalLabel<?php echo $delegate['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="barcodeModalLabel<?php echo $delegate['id']; ?>">Delegate Barcode</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body text-center">
                                                                        <h5><?php echo htmlspecialchars($delegate['name']); ?></h5>
                                                                        <p><?php echo htmlspecialchars($delegate['designation']); ?></p>
                                                                        
                                                                        <!-- Display barcode -->
                                                                        <div class="d-flex justify-content-center">
                                                                            <div class="border p-3 mb-3">
                                                                                <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text=<?php echo urlencode($delegate['barcode']); ?>&scale=2&includetext" alt="Barcode">
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <p><strong>Barcode:</strong> <?php echo htmlspecialchars($delegate['barcode']); ?></p>
                                                                        
                                                                        <div class="alert alert-info">
                                                                            <small>This barcode should be presented at the event gates for check-in.</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <a href="print_badge.php?id=<?php echo $delegate['id']; ?>&event_id=<?php echo $event_id; ?>" class="btn btn-primary" target="_blank">
                                                                            <i class="bi bi-printer"></i> Print Badge
                                                                        </a>
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