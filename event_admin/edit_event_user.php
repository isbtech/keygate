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

// Check if event_id and user ID are provided
if (!isset($_GET['event_id']) || empty($_GET['event_id']) || !isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('danger', 'Both Event ID and User ID are required');
    redirect('event_users.php');
}

$event_id = (int)$_GET['event_id'];
$user_id = (int)$_GET['id'];

// Get event details and check ownership
$event_details = get_event_details($event_id, $_SESSION['user_id']);

if (!$event_details['success']) {
    set_flash_message('danger', $event_details['message']);
    redirect('events.php');
}

$event = $event_details['event'];

// Get event user details
$conn = db_connect();
$sql = "SELECT eu.*, r.role_name 
        FROM event_users eu 
        JOIN event_user_roles r ON eu.role_id = r.id 
        WHERE eu.id = $user_id AND eu.event_id = $event_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $conn->close();
    set_flash_message('danger', 'Staff member not found or does not belong to this event');
    redirect('event_users.php?event_id=' . $event_id);
}

$event_user = $result->fetch_assoc();

// Get gates for this event
$gates = $event['gates'];

// Get available roles
$roles_query = "SELECT * FROM event_user_roles ORDER BY id ASC";
$roles_result = $conn->query($roles_query);
$roles = [];

if ($roles_result->num_rows > 0) {
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row;
    }
}

$conn->close();

// Process update form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $mobile = sanitize_input($_POST['mobile']);
    $role_id = (int)$_POST['role_id'];
    $gate_id = isset($_POST['gate_id']) ? (int)$_POST['gate_id'] : null;
    
    if (empty($name) || empty($email) || empty($mobile) || $role_id < 1) {
        set_flash_message('danger', 'Please fill in all required fields');
    } else {
        // Check if email exists for another user in this event
        $conn = db_connect();
        $check_sql = "SELECT id FROM event_users WHERE email = '$email' AND event_id = $event_id AND id != $user_id";
        $check_result = $conn->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            $conn->close();
            set_flash_message('danger', 'Email is already in use by another staff member in this event');
        } else {
            // Update user data
            $update_data = [
                'name' => $name,
                'email' => $email,
                'mobile' => $mobile,
                'role_id' => $role_id
            ];
            
            // Only update gate assignment if a gatekeeper and gate is selected
            $role = get_record('event_user_roles', "id = $role_id");
            if ($role && $role['role_name'] == 'GateKeeper' && $gate_id) {
                $update_data['last_active_gate'] = $gate_id;
            }
            
            $updated = update_record('event_users', $update_data, "id = $user_id AND event_id = $event_id");
            
            $conn->close();
            
            if ($updated) {
                set_flash_message('success', 'Staff member updated successfully');
                redirect('event_users.php?event_id=' . $event_id);
            } else {
                set_flash_message('danger', 'Failed to update staff member');
            }
        }
    }
}

// Page title
$page_title = 'Edit Staff Member - ' . $event['event_name'];

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
                <h1 class="h2">Edit Staff Member</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="event_users.php?event_id=<?php echo $event_id; ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Staff List
                    </a>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Event Info -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Event:</strong> <?php echo htmlspecialchars($event['event_name']); ?></p>
                            <p><strong>Organizer:</strong> <?php echo htmlspecialchars($event['event_organiser']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Staff Member:</strong> <?php echo htmlspecialchars($event_user['name']); ?></p>
                            <p><strong>Current Role:</strong> <?php echo htmlspecialchars($event_user['role_name']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Edit User Form -->
                <div class="col-md-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Edit Staff Member</h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($event_user['name']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($event_user['email']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="mobile" name="mobile" value="<?php echo htmlspecialchars($event_user['mobile']); ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="role_id" class="form-label">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role_id" name="role_id" required onchange="toggleGateAssignment()">
                                        <option value="">-- Select Role --</option>
                                        <?php foreach ($roles as $role): ?>
                                            <?php 
                                            // Disable GateKeeper option if no gates exist
                                            $disabled = ($role['role_name'] == 'GateKeeper' && empty($gates)) ? 'disabled' : '';
                                            ?>
                                            <option value="<?php echo $role['id']; ?>" <?php echo $event_user['role_id'] == $role['id'] ? 'selected' : ''; ?> <?php echo $disabled; ?>>
                                                <?php echo htmlspecialchars($role['role_name']); ?> - <?php echo htmlspecialchars($role['description']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($gates)): ?>
                                        <div class="form-text text-danger">GateKeeper role requires at least one gate to be created.</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3" id="gateAssignment" style="display: <?php echo $event_user['role_name'] == 'GateKeeper' ? 'block' : 'none'; ?>">
                                    <label for="gate_id" class="form-label">Assigned Gate</label>
                                    <select class="form-select" id="gate_id" name="gate_id">
                                        <option value="">-- Select Gate --</option>
                                        <?php foreach ($gates as $gate): ?>
                                            <option value="<?php echo $gate['id']; ?>" <?php echo $event_user['last_active_gate'] == $gate['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($gate['gate_name']); ?> 
                                                (<?php echo date('h:i A', strtotime($gate['access_time_start'])); ?> - <?php echo date('h:i A', strtotime($gate['access_time_end'])); ?>)
                                                <?php echo $gate['gate_status'] == 'Open' ? '- OPEN' : '- CLOSED'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">This is the gate that the gatekeeper will manage.</div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="submit" class="btn btn-primary">Update Staff Member</button>
                                    <a href="reset_password.php?id=<?php echo $user_id; ?>&event_id=<?php echo $event_id; ?>" class="btn btn-warning">Reset Password</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function toggleGateAssignment() {
        const roleSelect = document.getElementById('role_id');
        const gateAssignment = document.getElementById('gateAssignment');
        
        // Check if selected role is GateKeeper
        const selectedRole = roleSelect.options[roleSelect.selectedIndex].text;
        if (selectedRole.includes('GateKeeper')) {
            gateAssignment.style.display = 'block';
        } else {
            gateAssignment.style.display = 'none';
        }
    }
</script>

<?php
// Include footer
include '../includes/event_admin_footer.php';
?>