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

// Process plan creation
if (isset($_POST['create_plan'])) {
    $plan_name = sanitize_input($_POST['plan_name']);
    $events_allowed = (int)$_POST['events_allowed'];
    $delegates_per_event = (int)$_POST['delegates_per_event'];
    $event_auto_approval = isset($_POST['event_auto_approval']) ? 'yes' : 'no';
    
    if (empty($plan_name) || $events_allowed < 1 || $delegates_per_event < 1) {
        set_flash_message('danger', 'Please fill in all fields with valid values');
    } else {
        $result = create_plan($plan_name, $events_allowed, $delegates_per_event, $event_auto_approval);
        
        if ($result['success']) {
            set_flash_message('success', $result['message']);
            redirect('plans.php');
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
}

// Process plan update
if (isset($_POST['update_plan'])) {
    $plan_id = (int)$_POST['plan_id'];
    $plan_name = sanitize_input($_POST['plan_name']);
    $events_allowed = (int)$_POST['events_allowed'];
    $delegates_per_event = (int)$_POST['delegates_per_event'];
    $event_auto_approval = isset($_POST['event_auto_approval']) ? 'yes' : 'no';
    
    if (empty($plan_name) || $events_allowed < 1 || $delegates_per_event < 1) {
        set_flash_message('danger', 'Please fill in all fields with valid values');
    } else {
        $result = update_plan($plan_id, $plan_name, $events_allowed, $delegates_per_event, $event_auto_approval);
        
        if ($result['success']) {
            set_flash_message('success', $result['message']);
            redirect('plans.php');
        } else {
            set_flash_message('danger', $result['message']);
        }
    }
}

// Process plan deletion
if (isset($_POST['delete_plan'])) {
    $plan_id = (int)$_POST['plan_id'];
    
    $result = delete_plan($plan_id);
    
    if ($result['success']) {
        set_flash_message('success', $result['message']);
    } else {
        set_flash_message('danger', $result['message']);
    }
    
    redirect('plans.php');
}

// Get all plans
$plans = get_all_plans();

// Page title
$page_title = 'Subscription Plans';

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
                        <a class="nav-link" href="users.php">
                            <i class="bi bi-people me-2"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="bi bi-calendar-event me-2"></i> Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="plans.php">
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
                <h1 class="h2">Subscription Plans</h1>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                    <i class="bi bi-plus-circle"></i> Create New Plan
                </button>
            </div>

            <?php display_flash_message(); ?>

            <!-- Plans Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Plan Name</th>
                                    <th>Events Allowed</th>
                                    <th>Delegates Per Event</th>
                                    <th>Auto Approval</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                               <?php if (empty($plans)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No plans found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($plans as $plan): ?>
                                        <tr>
                                            <td><?php echo $plan['id']; ?></td>
                                            <td><?php echo htmlspecialchars($plan['plan_name']); ?></td>
                                            <td><?php echo $plan['events_allowed']; ?></td>
                                            <td><?php echo $plan['delegates_per_event']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $plan['event_auto_approval'] == 'yes' ? 'success' : 'warning'; ?>">
                                                    <?php echo $plan['event_auto_approval'] == 'yes' ? 'Yes' : 'No'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($plan['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editPlanModal<?php echo $plan['id']; ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePlanModal<?php echo $plan['id']; ?>">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                                
                                                <!-- Edit Plan Modal -->
                                                <div class="modal fade" id="editPlanModal<?php echo $plan['id']; ?>" tabindex="-1" aria-labelledby="editPlanModalLabel<?php echo $plan['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="editPlanModalLabel<?php echo $plan['id']; ?>">Edit Plan</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="post">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="plan_name<?php echo $plan['id']; ?>" class="form-label">Plan Name</label>
                                                                        <input type="text" class="form-control" id="plan_name<?php echo $plan['id']; ?>" name="plan_name" value="<?php echo htmlspecialchars($plan['plan_name']); ?>" required>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="events_allowed<?php echo $plan['id']; ?>" class="form-label">Events Allowed</label>
                                                                        <input type="number" class="form-control" id="events_allowed<?php echo $plan['id']; ?>" name="events_allowed" value="<?php echo $plan['events_allowed']; ?>" min="1" required>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="delegates_per_event<?php echo $plan['id']; ?>" class="form-label">Delegates Per Event</label>
                                                                        <input type="number" class="form-control" id="delegates_per_event<?php echo $plan['id']; ?>" name="delegates_per_event" value="<?php echo $plan['delegates_per_event']; ?>" min="1" required>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3 form-check">
                                                                        <input type="checkbox" class="form-check-input" id="event_auto_approval<?php echo $plan['id']; ?>" name="event_auto_approval" <?php echo $plan['event_auto_approval'] == 'yes' ? 'checked' : ''; ?>>
                                                                        <label class="form-check-label" for="event_auto_approval<?php echo $plan['id']; ?>">Enable Auto Approval for Events</label>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_plan" class="btn btn-primary">Update Plan</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Delete Plan Modal -->
                                                <div class="modal fade" id="deletePlanModal<?php echo $plan['id']; ?>" tabindex="-1" aria-labelledby="deletePlanModalLabel<?php echo $plan['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deletePlanModalLabel<?php echo $plan['id']; ?>">Delete Plan</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to delete the plan: <strong><?php echo htmlspecialchars($plan['plan_name']); ?></strong>?</p>
                                                                <p class="text-danger">This action cannot be undone. If there are users assigned to this plan, deletion will fail.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <form method="post">
                                                                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                                                    <button type="submit" name="delete_plan" class="btn btn-danger">Delete Plan</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
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
            
            <!-- Create Plan Modal -->
            <div class="modal fade" id="createPlanModal" tabindex="-1" aria-labelledby="createPlanModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="createPlanModalLabel">Create New Plan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form method="post">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="plan_name" class="form-label">Plan Name</label>
                                    <input type="text" class="form-control" id="plan_name" name="plan_name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="events_allowed" class="form-label">Events Allowed</label>
                                    <input type="number" class="form-control" id="events_allowed" name="events_allowed" min="1" value="1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="delegates_per_event" class="form-label">Delegates Per Event</label>
                                    <input type="number" class="form-control" id="delegates_per_event" name="delegates_per_event" min="1" value="100" required>
                                </div>
                                
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="event_auto_approval" name="event_auto_approval">
                                    <label class="form-check-label" for="event_auto_approval">Enable Auto Approval for Events</label>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" name="create_plan" class="btn btn-primary">Create Plan</button>
                            </div>
                        </form>
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