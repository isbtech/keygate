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
$event_users = [];

if ($selected_event_id > 0) {
    $event_details = get_event_details($selected_event_id, $_SESSION['user_id']);
    if ($event_details['success']) {
        $event = $event_details['event'];
        $gates = $event['gates'];
        
        // Get event users for this event
        $event_users_result = get_event_users($selected_event_id, $_SESSION['user_id']);
        if ($event_users_result['success']) {
            $event_users = $event_users_result['event_users'];
        }
    }
}

// Get available roles
$conn = db_connect();
$roles_query = "SELECT * FROM event_user_roles ORDER BY id ASC";
$roles_result = $conn->query($roles_query);
$roles = [];
if ($roles_result->num_rows > 0) {
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row;
    }
}
$conn->close();
// Check if there's a default password set for the organization
$default_password = '';
$event_admin = get_record('users', "id = {$_SESSION['user_id']}");
if ($event_admin && isset($event_admin['default_staff_password']) && !empty($event_admin['default_staff_password'])) {
    $default_password = $event_admin['default_staff_password'];
}

// Process event user creation
if (isset($_POST['create_event_user'])) {
    $event_id = (int)$_POST['event_id'];
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $mobile = sanitize_input($_POST['mobile']);
    $role_id = (int)$_POST['role_id'];
    
    if (empty($name) || empty($email) || empty($mobile) || $role_id < 1) {
        set_flash_message('danger', 'Please fill in all required fields');
    } else {
        // Use default password if available, otherwise generate a random one
        $password = !empty($default_password) ? $default_password : generate_password(10);
        
        $result = create_event_user($event_id, $name, $mobile, $email, $role_id, $_SESSION['user_id'], $password);
        
        if ($result['success']) {
            set_flash_message('success', $result['message']);
            redirect('event_users.php?event_id=' . $event_id);
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
}

// Page title
$page_title = 'Manage Event Staff';

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
                <h1 class="h2">Manage Event Staff</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="events.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Events
                    </a>
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
                                    <span class="badge bg-info">Staff: <?php echo count($event_users); ?></span>
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
                    <p>You don't have any active events. Staff can only be added to active events.</p>
                    <p><a href="events.php" class="btn btn-primary btn-sm">View My Events</a> or <a href="create_event.php" class="btn btn-success btn-sm">Create New Event</a></p>
                </div>
            <?php elseif ($selected_event_id == 0): ?>
                <div class="alert alert-info">
                    <h5>Select an Event</h5>
                    <p>Please select an event from the dropdown above to manage its staff.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <!-- Create Staff Form -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Add Event Staff Member</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($gates) && in_array_values("GateKeeper", array_column($roles, 'role_name'))): ?>
                                    <div class="alert alert-warning">
                                        <p>You need to create at least one gate before adding Gatekeepers.</p>
                                        <p><a href="manage_gates.php?event_id=<?php echo $selected_event_id; ?>" class="btn btn-primary btn-sm">Create Gates</a></p>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="post">
                                    <input type="hidden" name="event_id" value="<?php echo $selected_event_id; ?>">
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required>
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
                                        <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                                        <select class="form-select" id="role_id" name="role_id" required>
                                            <option value="">-- Select Role --</option>
                                            <?php foreach ($roles as $role): ?>
                                                <?php 
                                                // Disable GateKeeper option if no gates exist
                                                $disabled = ($role['role_name'] == 'GateKeeper' && empty($gates)) ? 'disabled' : '';
                                                ?>
                                                <option value="<?php echo $role['id']; ?>" <?php echo $disabled; ?>>
                                                    <?php echo htmlspecialchars($role['role_name']); ?> - <?php echo htmlspecialchars($role['description']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (empty($gates)): ?>
                                            <div class="form-text text-danger">GateKeeper role requires at least one gate to be created.</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" name="create_event_user" class="btn btn-primary">Add Staff Member</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Role Information -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0">Role Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="accordion" id="roleAccordion">
                                    <?php foreach ($roles as $role): ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading<?php echo $role['id']; ?>">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $role['id']; ?>" aria-expanded="false" aria-controls="collapse<?php echo $role['id']; ?>">
                                                    <?php echo htmlspecialchars($role['role_name']); ?>
                                                </button>
                                            </h2>
                                            <div id="collapse<?php echo $role['id']; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $role['id']; ?>" data-bs-parent="#roleAccordion">
                                                <div class="accordion-body">
                                                    <p><?php echo htmlspecialchars($role['description']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Staff List -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Event Staff</h5>
                                <div class="input-group" style="width: 50%;">
                                    <input type="text" id="staffSearch" class="form-control form-control-sm" placeholder="Search staff...">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="clearSearch">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($event_users)): ?>
                                    <div class="alert alert-info">
                                        <p>No staff members found for this event. Use the form to add staff members.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover" id="staffTable">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Name</th>
                                                    <th>Email / Mobile</th>
                                                    <th>Role</th>
                                                    <th>Last Active</th>
                                                    <th>Last Gate</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($event_users as $user): ?>
                                                    <tr>
                                                        <td><?php echo $user['id']; ?></td>
                                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($user['email']); ?><br>
                                                            <small><?php echo htmlspecialchars($user['mobile']); ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                if ($user['role_name'] == 'Event Manager') echo 'primary';
                                                                elseif ($user['role_name'] == 'GateKeeper') echo 'success';
                                                                else echo 'info';
                                                            ?>">
                                                                <?php echo htmlspecialchars($user['role_name']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if ($user['last_active']): ?>
                                                                <?php echo date('M d, Y H:i', strtotime($user['last_active'])); ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">Never</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($user['last_active_gate']): ?>
                                                                <?php echo htmlspecialchars($user['last_active_gate_name']); ?>
                                                            <?php else: ?>
                                                                <span class="text-muted">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <a href="edit_event_user.php?id=<?php echo $user['id']; ?>&event_id=<?php echo $selected_event_id; ?>" class="btn btn-sm btn-outline-primary">
                                                                    <i class="bi bi-pencil"></i>
                                                                </a>
                                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['id']; ?>">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                                <a href="reset_password.php?id=<?php echo $user['id']; ?>&event_id=<?php echo $selected_event_id; ?>" class="btn btn-sm btn-outline-warning" title="Reset Password">
                                                                    <i class="bi bi-key"></i>
                                                                </a>
                                                            </div>
                                                            
                                                            <!-- Delete Modal -->
                                                            <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $user['id']; ?>">Confirm Delete</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body">
                                                                            <p>Are you sure you want to delete the staff member: <strong><?php echo htmlspecialchars($user['name']); ?></strong>?</p>
                                                                            <p class="text-danger">This action cannot be undone.</p>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <a href="delete_event_user.php?id=<?php echo $user['id']; ?>&event_id=<?php echo $selected_event_id; ?>" class="btn btn-danger">Delete</a>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality
        const searchInput = document.getElementById('staffSearch');
        const clearButton = document.getElementById('clearSearch');
        const table = document.getElementById('staffTable');
        
        if (searchInput && table) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
                
                for (let i = 0; i < rows.length; i++) {
                    const rowText = rows[i].textContent.toLowerCase();
                    rows[i].style.display = rowText.includes(searchTerm) ? '' : 'none';
                }
            });
            
            if (clearButton) {
                clearButton.addEventListener('click', function() {
                    searchInput.value = '';
                    const event = new Event('keyup');
                    searchInput.dispatchEvent(event);
                });
            }
        }
    });
</script>

<?php
// Helper function for role check
function in_array_values($needle, $haystack) {
    return in_array($needle, $haystack);
}

// Include footer
include '../includes/event_admin_footer.php';
?>