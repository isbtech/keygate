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

// Process user status update if requested
if (isset($_POST['toggle_status'], $_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
    $user = get_record('users', "id = $user_id");
    
    if ($user) {
        $new_status = $user['activated'] == 'yes' ? 'no' : 'yes';
        $result = update_user_status($user_id, $new_status);
        
        if ($result['success']) {
            set_flash_message('success', $result['message']);
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
    
    // Redirect to refresh the page
    redirect('users.php');
}

// Process plan assignment if requested
if (isset($_POST['assign_plan'], $_POST['user_id'], $_POST['plan_id'])) {
    $user_id = (int)$_POST['user_id'];
    $plan_id = (int)$_POST['plan_id'];
    
    $result = assign_plan_to_user($user_id, $plan_id);
    
    if ($result['success']) {
        set_flash_message('success', $result['message']);
    } else {
        set_flash_message('danger', $result['message']);
    }
    
    // Redirect to refresh the page
    redirect('users.php');
}

// Get all users
$users = get_all_users();

// Get all plans for dropdown
$plans = get_all_plans();

// Page title
$page_title = 'Users Management';

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
                <h1 class="h2">Users Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="create_user.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-person-plus"></i> Add New User
                    </a>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Role</th>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No users found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['mobile']); ?></td>
                                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                                            <td>
                                                <form method="post" class="d-flex align-items-center">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <select name="plan_id" class="form-select form-select-sm me-2" style="width: 120px;">
                                                        <option value="">No Plan</option>
                                                        <?php foreach ($plans as $plan): ?>
                                                            <option value="<?php echo $plan['id']; ?>" <?php echo $user['plan_id'] == $plan['id'] ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($plan['plan_name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" name="assign_plan" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-check"></i>
                                                    </button>
                                                </form>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['activated'] == 'yes' ? 'success' : 'danger'; ?>">
                                                    <?php echo $user['activated'] == 'yes' ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-<?php echo $user['activated'] == 'yes' ? 'danger' : 'success'; ?>">
                                                            <i class="bi bi-<?php echo $user['activated'] == 'yes' ? 'lock' : 'unlock'; ?>"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include footer
include '../includes/admin_footer.php';
?>