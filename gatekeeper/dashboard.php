<?php
// Include configuration
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/delegate_checkin.php';

// Check if user is logged in and is a gatekeeper
if (!is_event_user('GateKeeper')) {
    set_flash_message('danger', 'Unauthorized access');
    redirect('../event_login.php');
}

// Get gatekeeper info
$gatekeeper_info = get_gatekeeper_info($_SESSION['event_user_id']);

if (!$gatekeeper_info['success']) {
    set_flash_message('danger', $gatekeeper_info['message']);
    redirect('../event_login.php');
}

$event = $gatekeeper_info['event'];
$gate = $gatekeeper_info['gate'];
$gatekeeper = $gatekeeper_info['gatekeeper'];

// Process barcode scan
$scan_result = null;
if (isset($_POST['barcode']) && !empty($_POST['barcode'])) {
    $barcode = sanitize_input($_POST['barcode']);
    $scan_result = process_delegate_scan($barcode, $gate['id'], $event['id'], $_SESSION['event_user_id']);
}

// Get recent check-ins
$recent_checkins = get_recent_checkins($gate['id'], $event['id'], 10);

// Get gate summary
$gate_summary = get_gate_checkin_summary($gate['id'], $event['id']);

// Check if gate is open
$is_gate_open = is_gate_open($gate['id']);

// Search delegates
$search_results = [];
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = sanitize_input($_GET['search']);
    $search_results = search_delegates($search_term, $event['id'], $gate['id']);
}

// Page title
$page_title = 'Gatekeeper Dashboard';

// Include header
include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header Bar -->
    <div class="row bg-dark text-white p-3 mb-4">
        <div class="col-md-6">
            <h3>Event: <?php echo htmlspecialchars($event['event_name']); ?></h3>
            <h5>Gate: <?php echo htmlspecialchars($gate['gate_name']); ?> 
                <span class="badge <?php echo $is_gate_open ? 'bg-success' : 'bg-danger'; ?>">
                    <?php echo $is_gate_open ? 'OPEN' : 'CLOSED'; ?>
                </span>
            </h5>
            <p>Access Time: <?php echo $gate['access_time_start']; ?> - <?php echo $gate['access_time_end']; ?></p>
        </div>
        <div class="col-md-6 text-end">
            <h5>Gatekeeper: <?php echo htmlspecialchars($gatekeeper['name']); ?></h5>
            <p>Current Time: <span id="current-time"></span></p>
            <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
    </div>
    
    <div class="row">
        <!-- Main Content -->
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Scan Delegate Badge</h4>
                </div>
                <div class="card-body">
                    <?php display_flash_message(); ?>
                    
                    <?php if (!$is_gate_open): ?>
                        <div class="alert alert-warning">
                            <strong>Gate is currently closed!</strong> Check-ins are not allowed at this time.
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" id="scan-form">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control form-control-lg" name="barcode" id="barcode" 
                                   placeholder="Scan barcode or enter manually" autofocus 
                                   <?php echo !$is_gate_open ? 'disabled' : ''; ?>>
                            <button class="btn btn-primary" type="submit" <?php echo !$is_gate_open ? 'disabled' : ''; ?>>Check In</button>
                        </div>
                    </form>
                    
                    <?php if ($scan_result): ?>
                        <div class="alert alert-<?php echo $scan_result['access_granted'] ? 'success' : 'danger'; ?> mt-3">
                            <h5><?php echo $scan_result['message']; ?></h5>
                            <?php if (isset($scan_result['delegate'])): ?>
                                <p>
                                    <strong>Name:</strong> <?php echo htmlspecialchars($scan_result['delegate']['name']); ?><br>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($scan_result['delegate']['email']); ?><br>
                                    <strong>Designation:</strong> <?php echo htmlspecialchars($scan_result['delegate']['designation']); ?>
                                </p>
                                <?php if (isset($scan_result['already_checked_in']) && $scan_result['already_checked_in']): ?>
                                    <p><strong>Previous Check-in Time:</strong> <?php echo $scan_result['check_in_time']; ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Search Delegates</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="mb-3">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search by name, email or mobile" 
                                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <button class="btn btn-outline-secondary" type="submit">Search</button>
                        </div>
                    </form>
                    
                    <?php if (!empty($search_results)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Designation</th>
                                        <th>Access</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($search_results as $delegate): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($delegate['name']); ?></td>
                                            <td><?php echo htmlspecialchars($delegate['email']); ?></td>
                                            <td><?php echo htmlspecialchars($delegate['designation']); ?></td>
                                            <td>
                                                <?php if ($delegate['has_access']): ?>
                                                    <span class="badge bg-success">Allowed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">No Access</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($delegate['checked_in']): ?>
                                                    <span class="badge bg-info">Checked In</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Not Checked In</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($is_gate_open && $delegate['has_access'] && !$delegate['checked_in']): ?>
                                                    <form method="post">
                                                        <input type="hidden" name="barcode" value="<?php echo $delegate['barcode']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary">Check In</button>
                                                    </form>
                                                <?php elseif ($delegate['checked_in']): ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>Already Checked In</button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-danger" disabled>No Access</button>
                                                   
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif (isset($_GET['search'])): ?>
                        <div class="alert alert-info">No delegates found matching your search criteria.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Gate Summary -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Gate Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3><?php echo $gate_summary['total_with_access']; ?></h3>
                                    <p class="mb-0">Total Delegates</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h3><?php echo $gate_summary['total_checked_in']; ?></h3>
                                    <p class="mb-0">Checked In</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="progress mt-3" style="height: 30px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $gate_summary['percentage']; ?>%;" 
                             aria-valuenow="<?php echo $gate_summary['percentage']; ?>" aria-valuemin="0" aria-valuemax="100">
                            <?php echo $gate_summary['percentage']; ?>%
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <p><strong>Last Hour Check-ins:</strong> <?php echo $gate_summary['recent_checkins']; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Recent Check-ins -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Recent Check-ins</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_checkins)): ?>
                        <div class="p-3 text-center">No check-ins yet.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_checkins as $checkin): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($checkin['name']); ?></h6>
                                        <small><?php echo date('H:i:s', strtotime($checkin['check_in_time'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($checkin['designation']); ?></p>
                                    <small><?php echo htmlspecialchars($checkin['email']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update current time
function updateTime() {
    const now = new Date();
    document.getElementById('current-time').textContent = now.toLocaleTimeString();
}

// Update time every second
setInterval(updateTime, 1000);
updateTime(); // Initial call

// Auto focus on barcode input after scan
document.addEventListener('DOMContentLoaded', function() {
    const scanForm = document.getElementById('scan-form');
    const barcodeInput = document.getElementById('barcode');
    
    if (scanForm && barcodeInput) {
        scanForm.addEventListener('submit', function() {
            setTimeout(function() {
                barcodeInput.focus();
            }, 100);
        });
    }
});
</script>

<?php
// Include footer
include '../includes/footer.php';
?>