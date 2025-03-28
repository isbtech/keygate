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

// Check if user can create events
$can_create = can_create_event($_SESSION['user_id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if user can create events
    if (!$can_create['can_create']) {
        set_flash_message('danger', $can_create['message']);
        redirect('dashboard.php');
    }
    
    // Get form data
    $event_name = sanitize_input($_POST['event_name']);
    $event_organiser = sanitize_input($_POST['event_organiser']);
    $event_venue = sanitize_input($_POST['event_venue']);
    $access_gates_required = (int)$_POST['access_gates_required'];
    
    // Process event dates
    $event_dates = [];
    if (isset($_POST['event_dates']) && !empty($_POST['event_dates'])) {
        $dates = explode(',', $_POST['event_dates']);
        foreach ($dates as $date) {
            $date = trim($date);
            if (!empty($date)) {
                $event_dates[] = date('Y-m-d', strtotime($date));
            }
        }
    }
    
    // Validate inputs
    if (empty($event_name) || empty($event_organiser) || empty($event_venue) || $access_gates_required < 1) {
        set_flash_message('danger', 'Please fill in all required fields');
    } elseif (empty($event_dates)) {
        set_flash_message('danger', 'Please provide at least one event date');
    } else {
        // Create event
        $result = create_event($event_name, $event_organiser, $event_venue, $access_gates_required, $event_dates, $_SESSION['user_id']);
        
        if ($result['success']) {
            set_flash_message('success', $result['message']);
            redirect('events.php');
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
}

// Page title
$page_title = 'Create New Event';

// Additional CSS for date picker
$additional_css = '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">';

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
                        <a class="nav-link active text-white" href="create_event.php">
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
                <h1 class="h2">Create New Event</h1>
                <a href="events.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Events
                </a>
            </div>

            <?php display_flash_message(); ?>

            <?php if (!$can_create['can_create']): ?>
                <div class="alert alert-warning">
                    <h5>Cannot Create Event</h5>
                    <p><?php echo $can_create['message']; ?></p>
                    <p>Please contact the administrator to upgrade your plan or adjust your limits.</p>
                </div>
            <?php else: ?>
                <!-- Create Event Form -->
                <div class="card">
                    <div class="card-body">
                        <form method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="event_name" class="form-label">Event Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="event_name" name="event_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="event_organiser" class="form-label">Event Organizer <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="event_organiser" name="event_organiser" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="event_venue" class="form-label">Event Venue <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="event_venue" name="event_venue" rows="2" required></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="event_dates" class="form-label">Event Dates <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="event_dates" name="event_dates" placeholder="Select multiple dates" required>
                                    <small class="form-text text-muted">Click multiple times to select multiple dates</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="access_gates_required" class="form-label">Number of Access Gates <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="access_gates_required" name="access_gates_required" min="1" max="10" value="1" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Event Plan Details</label>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <p><strong>Plan:</strong> <?php echo isset($can_create['plan_name']) ? htmlspecialchars($can_create['plan_name']) : 'Standard'; ?></p>
                                        <p><strong>Maximum Delegates Allowed:</strong> <?php echo $can_create['delegates_allowed']; ?></p>
                                        <p><strong>Auto Approval:</strong> <?php echo $can_create['auto_approval'] == 'yes' ? 'Yes (Event will be automatically activated)' : 'No (Event will require admin approval)'; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Create Event</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Flatpickr for date picker -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize date picker for event dates
        flatpickr("#event_dates", {
            mode: "multiple",
            dateFormat: "Y-m-d",
            minDate: "today",
            showMonths: 2,
            altInput: true,
            altFormat: "F j, Y",
            conjunction: ", "
        });
    });
</script>

<?php
// Include footer
include '../includes/event_admin_footer.php';
?>