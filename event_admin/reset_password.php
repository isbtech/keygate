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
$conn->close();

// Process reset password form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = sanitize_input($_POST['password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    
    if (empty($password)) {
        set_flash_message('danger', 'Password cannot be empty');
    } else if ($password != $confirm_password) {
        set_flash_message('danger', 'Passwords do not match');
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update password
        $updated = update_record('event_users', 
            ['password' => $hashed_password], 
            "id = $user_id AND event_id = $event_id"
        );
        
        if ($updated) {
            // Send email to user with new password
            $message = "
                <html>
                <head>
                    <title>Password Reset</title>
                </head>
                <body>
                    <h2>Password Reset</h2>
                    <p>Hello {$event_user['name']},</p>
                    <p>Your password for the event '{$event['event_name']}' has been reset.</p>
                    <p><strong>Your New Login Details:</strong></p>
                    <p>Email: {$event_user['email']}<br>Password: $password</p>
                    <p>You can login at: " . SITE_URL . "/event_login.php</p>
                    <p>Regards,<br>{$event['event_organiser']}</p>
                </body>
                </html>
            ";
            
            send_email($event_user['email'], "Password Reset - {$event['event_name']}", $message);
            
            set_flash_message('success', 'Password reset successfully. An email with the new password has been sent to the staff member.');
            redirect('event_users.php?event_id=' . $event_id);
        } else {
            set_flash_message('danger', 'Failed to reset password');
        }
    }
}

// Check if there's a default password set for the organization
$default_password = '';
$event_admin = get_record('users', "id = {$_SESSION['user_id']}");
if ($event_admin && isset($event_admin['default_staff_password']) && !empty($event_admin['default_staff_password'])) {
    $default_password = $event_admin['default_staff_password'];
}

// Generate random password if no default is set
if (empty($default_password)) {
    $default_password = generate_password(10);
}

// Page title
$page_title = 'Reset Staff Password - ' . $event['event_name'];

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
                <h1 class="h2">Reset Staff Password</h1>
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
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($event_user['email']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Reset Password Form -->
                <div class="col-md-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Reset Password for <?php echo htmlspecialchars($event_user['name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="post">
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="password" name="password" value="<?php echo htmlspecialchars($default_password); ?>" required>
                                        <button class="btn btn-outline-secondary" type="button" id="generate_password">Generate</button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="confirm_password" name="confirm_password" value="<?php echo htmlspecialchars($default_password); ?>" required>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="save_default" name="save_default">
                                    <label class="form-check-label" for="save_default">
                                        Set as default password for all new staff members
                                    </label>
                                </div>
                                
                                <div class="alert alert-info">
                                    <p><i class="bi bi-info-circle"></i> The staff member will receive an email with the new password.</p>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-warning">Reset Password</button>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Generate random password
        document.getElementById('generate_password').addEventListener('click', function() {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+";
            let password = '';
            for (let i = 0; i < 10; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('password').value = password;
            document.getElementById('confirm_password').value = password;
        });
        
        // Save default password checkbox
        document.getElementById('save_default').addEventListener('change', function() {
            if (this.checked) {
                // Save password as default for future staff members
                const password = document.getElementById('password').value;
                
                fetch('save_default_password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'password=' + encodeURIComponent(password)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Default password saved successfully for future staff members.');
                    } else {
                        alert('Failed to save default password: ' + data.message);
                        this.checked = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while saving the default password.');
                    this.checked = false;
                });
            }
        });
    });
</script>

<?php
// Include footer
include '../includes/event_admin_footer.php';
?>