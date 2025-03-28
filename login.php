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

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        set_flash_message('danger', 'Please enter both email and password');
    } else {
        $result = login_user($email, $password);
        
        if ($result['success']) {
            // Redirect based on role
            if (is_admin()) {
                redirect('admin/dashboard.php');
            } elseif (is_event_admin()) {
                redirect('event_admin/dashboard.php');
            } else {
                redirect('index.php');
            }
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
}

// Page title
$page_title = 'Login';

// Include header
include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Login</h4>
                </div>
                <div class="card-body">
                    <?php display_flash_message(); ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-primary">Login</button>
                            <a href="forgot_password.php">Forgot Password?</a>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p class="mb-0">Don't have an account? <a href="register.php">Register</a></p>
                    <p class="mt-2 mb-0">Event staff? <a href="event_login.php">Login here</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include 'includes/footer.php';
?>