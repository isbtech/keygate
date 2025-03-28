<?php
// Include configuration
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (is_admin()) {
        redirect('admin/dashboard.php');
    } elseif (is_event_admin()) {
        redirect('event_admin/dashboard.php');
    } else {
        redirect('index.php');
    }
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $mobile = sanitize_input($_POST['mobile']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Basic validation
    if (empty($name) || empty($email) || empty($mobile) || empty($password)) {
        set_flash_message('danger', 'Please fill in all fields');
    } elseif ($password != $confirm_password) {
        set_flash_message('danger', 'Passwords do not match');
    } elseif (strlen($password) < 6) {
        set_flash_message('danger', 'Password must be at least 6 characters long');
    } else {
        // Register user
        $result = register_user($name, $email, $mobile, $password);
        
        if ($result['success']) {
            set_flash_message('success', $result['message']);
            redirect('login.php');
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
}

// Page title
$page_title = 'Register';

// Include header
include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Register</h4>
                </div>
                <div class="card-body">
                    <?php display_flash_message(); ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="mobile" class="form-label">Mobile Number</label>
                            <input type="text" class="form-control" id="mobile" name="mobile" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Already have an account? <a href="login.php">Login</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>