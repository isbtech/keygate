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

// Process event status update if requested
if (isset($_POST['update_event_status'], $_POST['event_id'], $_POST['status'])) {
    $event_id = (int)$_POST['event_id'];
    $status = sanitize_input($_POST['status']);
    
    if ($status == 'Approve') {
        $result = approve_event($event_id, $_SESSION['user_id']);
    } elseif ($status == 'Reject') {
        $reason = isset($_POST['reason']) ? sanitize_input($_POST['reason']) : '';
        $result = reject_event($event_id, $reason);
    } else {
        $result = [
            'success' => false,
            'message' => 'Invalid action'
        ];
    }
    
    if ($result['success']) {
        set_flash_message('success', $result['message']);
    } else {
        set_flash_message('danger', $result['message']);
    }
    
    // Redirect to refresh the page
    redirect('events.php');
}

// Get all events with creators
$conn = db_connect();
$sql = "SELECT e.*, u.name as creator_name 
        FROM events e 
        JOIN users u ON e.created_by = u.id 
        ORDER BY e.created_at DESC";
$result = $conn->query($sql);
$events = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Get event dates
        $dates_sql = "SELECT event_date FROM event_dates WHERE event_id = {$row['id']} ORDER BY event_date ASC";
        $dates_result = $conn->query($dates_sql);
        $dates = [];
        
        if ($dates_result->num_rows > 0) {
            while ($date_row = $dates_result->fetch_assoc()) {
                $dates[] = $date_row['event_date'];
            }
        }
        
        $row['dates'] = $dates;
        
        // Get gates count
        $gates_sql = "SELECT COUNT(*) as count FROM access_gates WHERE event_id = {$row['id']}";
        $gates_result = $conn->query($gates_sql);
        $row['gates_count'] = $gates_result->fetch_assoc()['count'];
        
        // Get delegates count
        $delegates_sql = "SELECT COUNT(*) as count FROM event_delegates WHERE event_id = {$row['id']}";
        $delegates_result = $conn->query($delegates_sql);
        $row['delegates_count'] = $delegates_result->fetch_assoc()['count'];
        
        $events[] = $row;
    }
}
$conn->close();

// Page title
$page_title = 'Events Management';

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
                        <a class="nav-link active" href="events.php">
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
                <h1 class="h2">Events Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="pending_events.php" class="btn btn-sm btn-outline-primary me-2">
                        <i class="bi bi-hourglass-split"></i> Pending Events
                    </a>
                </div>
            </div>

            <?php display_flash_message(); ?>

            <!-- Events Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Event Name</th>
                                    <th>Organizer</th>
                                    <th>Created By</th>
                                    <th>Dates</th>
                                    <th>Gates</th>
                                    <th>Delegates</th>
                                    <th>Status</th>
                                    <th>Created On</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($events)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No events found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($events as $event): ?>
                                        <tr>
                                            <td><?php echo $event['id']; ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($event['event_name']); ?>
                                                <?php if ($event['status'] == 'Pending Approval'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($event['event_organiser']); ?></td>
                                            <td><?php echo htmlspecialchars($event['creator_name']); ?></td>
                                            <td>
                                                <?php if (!empty($event['dates'])): ?>
                                                    <?php foreach ($event['dates'] as $index => $date): ?>
                                                        <?php if ($index < 2): ?>
                                                            <?php echo date('M d, Y', strtotime($date)); ?><br>
                                                        <?php elseif ($index == 2): ?>
                                                            <span class="text-muted">+<?php echo (count($event['dates']) - 2); ?> more</span>
                                                            <?php break; ?>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No dates</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $event['gates_count']; ?> / <?php echo $event['access_gates_required']; ?></td>
                                            <td><?php echo $event['delegates_count']; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    if ($event['status'] == 'Active') echo 'success';
                                                    elseif ($event['status'] == 'Pending Approval') echo 'warning';
                                                    elseif ($event['status'] == 'Cancelled') echo 'danger';
                                                    elseif ($event['status'] == 'Completed') echo 'info';
                                                    else echo 'secondary';
                                                ?>">
                                                    <?php echo $event['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($event['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <?php if ($event['status'] == 'Pending Approval'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#approveModal<?php echo $event['id']; ?>">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $event['id']; ?>">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    <?php elseif ($event['status'] == 'Active'): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#completeModal<?php echo $event['id']; ?>">
                                                            <i class="bi bi-check-square"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Approve Modal -->
                                                <div class="modal fade" id="approveModal<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="approveModalLabel<?php echo $event['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="approveModalLabel<?php echo $event['id']; ?>">Approve Event</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to approve the event: <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>?</p>
                                                                <p>This will make the event available for setup and delegate registration.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <form method="post">
                                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                                    <input type="hidden" name="status" value="Approve">
                                                                    <button type="submit" name="update_event_status" class="btn btn-success">Approve Event</button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Reject Modal -->
                                                <div class="modal fade" id="rejectModal<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="rejectModalLabel<?php echo $event['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="rejectModalLabel<?php echo $event['id']; ?>">Reject Event</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="post">
                                                                <div class="modal-body">
                                                                    <p>Are you sure you want to reject the event: <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>?</p>
                                                                    <p>This action cannot be undone.</p>
                                                                    <div class="mb-3">
                                                                        <label for="reason<?php echo $event['id']; ?>" class="form-label">Reason for Rejection (Optional):</label>
                                                                        <textarea class="form-control" id="reason<?php echo $event['id']; ?>" name="reason" rows="3"></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                                    <input type="hidden" name="status" value="Reject">
                                                                    <button type="submit" name="update_event_status" class="btn btn-danger">Reject Event</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Complete Modal -->
                                                <div class="modal fade" id="completeModal<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="completeModalLabel<?php echo $event['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="completeModalLabel<?php echo $event['id']; ?>">Mark Event as Completed</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to mark the event: <strong><?php echo htmlspecialchars($event['event_name']); ?></strong> as completed?</p>
                                                                <p>This will move the event to the completed state.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <form method="post">
                                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                                    <input type="hidden" name="status" value="Complete">
                                                                    <button type="submit" name="update_event_status" class="btn btn-info">Mark as Completed</button>
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
        </main>
    </div>
</div>

<?php
// Include footer
include '../includes/admin_footer.php';
?>