<?php
// Include configuration
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/admin_functions.php';

// Check if user is logged in and is admin
if (!is_admin()) {
    set_flash_message('danger', 'Unauthorized access');
    redirect('../login.php');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $mobile = sanitize_input($_POST['mobile']);
    $role = sanitize_input($_POST['role']);
    $plan_id = !empty($_POST['plan_id']) ? (int)$_POST['plan_id'] : null;
    $password = $_POST['password'];
    $activated = isset($_POST['activated']) ? 'yes' : 'no';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($mobile) || empty($password)) {
        set_flash_message('danger', 'Please fill in all required fields');
    } elseif (strlen($password) < 6) {
        set_flash_message('danger', 'Password must be at least 6 characters long');
    } else {
        // Create user
        $result = admin_create_user($name, $email, $mobile, $password, $role, $plan_id, $activated);
        
        if ($result['success']) {
            set_flash_message('success', $result['message']);
            redirect('users.php');
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
}

// Get all plans for dropdown
$plans = get_all_plans();

// Page title
$page_title = 'Create New User';

// Include header
include '../includes/admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebar" class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="users.php">
                            <i class="bi bi-people me-2"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="bi bi-calendar-event me-2"></i> Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="plans.php">
                            <i class="bi bi-grid-3x3-gap me-2"></i> Plans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="bi bi-file-earmark-bar-graph me-2"></i> Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Create New User</h1>
                <a href="users.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Users
                </a>
            </div>

            <?php display_flash_message(); ?>

            <!-- Create User Form -->
            <div class="card">
                <div class="card-body">
                    <form method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="mobile" class="form-label">Mobile Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="mobile" name="mobile" required>
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="Event_admin">Event Admin</option>
                                    <option value="keygate_staff">Keygate Staff</option>
                                    <option value="Admin">Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="plan_id" class="form-label">Plan</label>
                                <select class="form-select" id="plan_id" name="plan_id">
                                    <option value="">No Plan</option>
                                    <?php foreach ($plans as $plan): ?>
                                        <option value="<?php echo $plan['id']; ?>">
                                            <?php echo htmlspecialchars($plan['plan_name']); ?> 
                                            (Events: <?php echo $plan['events_allowed']; ?>, 
                                            Delegates: <?php echo $plan['delegates_per_event']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="generate_password">Generate</button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="activated" name="activated" checked>
                            <label class="form-check-label" for="activated">Activate user immediately</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Create User</button>
                        </div>
                    </form>
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
    });
});
</script>

<?php
// Include footer
include '../includes/admin_footer.php';
?>