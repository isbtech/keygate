<?php
// Include configuration
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Page title
$page_title = 'Home';

// Include header
include 'includes/header.php';
?>

<div class="container">
    <!-- Hero Section -->
    <div class="row py-5">
        <div class="col-md-6">
            <h1 class="display-4">Keygate</h1>
            <p class="lead">Streamlined Event Access Management System</p>
            <p class="mb-4">Simplify event entry with our cutting-edge barcode scanning technology. Create events, manage delegates, and track attendance in real-time.</p>
            
            <?php if (!is_logged_in() && !is_event_user()): ?>
                <div class="d-grid gap-2 d-md-flex">
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                    <a href="login.php" class="btn btn-outline-secondary">Login</a>
                </div>
            <?php elseif (is_admin()): ?>
                <a href="admin/dashboard.php" class="btn btn-primary">Go to Admin Dashboard</a>
            <?php elseif (is_event_admin()): ?>
                <a href="event_admin/dashboard.php" class="btn btn-primary">Go to Event Admin Dashboard</a>
            <?php elseif (is_event_user('GateKeeper')): ?>
                <a href="gatekeeper/dashboard.php" class="btn btn-primary">Go to Gatekeeper Dashboard</a>
            <?php elseif (is_event_user('Event Manager')): ?>
                <a href="event_manager/dashboard.php" class="btn btn-primary">Go to Event Manager Dashboard</a>
            <?php elseif (is_event_user('Volunteer')): ?>
                <a href="volunteer/dashboard.php" class="btn btn-primary">Go to Volunteer Dashboard</a>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <img src="assets/img/hero-image.svg" alt="Event Access Management" class="img-fluid">
        </div>
    </div>
    
    <!-- Features Section -->
    <div class="row py-5">
        <div class="col-12 text-center mb-4">
            <h2>Key Features</h2>
            <p class="lead">Everything you need to make your event a success</p>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-qr-code-scan text-primary" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Barcode Scanning</h5>
                    <p class="card-text">Quick and secure check-in process using unique barcodes for each delegate.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-door-open text-primary" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Multi-Gate Support</h5>
                    <p class="card-text">Configure multiple access points with specific entry permissions for different delegate types.</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-graph-up text-primary" style="font-size: 3rem;"></i>
                    <h5 class="card-title mt-3">Real-time Analytics</h5>
                    <p class="card-text">Monitor attendance, check-in rates, and gate traffic with live dashboards.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- How It Works Section -->
    <div class="row py-5 bg-light rounded">
        <div class="col-12 text-center mb-4">
            <h2>How It Works</h2>
            <p class="lead">A simple three-step process</p>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <div class="circle-icon">1</div>
                <h5 class="mt-3">Create Your Event</h5>
                <p>Set up your event details, define access gates, and configure check-in windows.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <div class="circle-icon">2</div>
                <h5 class="mt-3">Register Delegates</h5>
                <p>Add delegates to your event and assign specific access permissions to each.</p>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="text-center">
                <div class="circle-icon">3</div>
                <h5 class="mt-3">Manage Check-ins</h5>
                <p>Scan delegate barcodes at gates and track attendance in real-time.</p>
            </div>
        </div>
    </div>
    
    <!-- Testimonials Section -->
    <div class="row py-5">
        <div class="col-12 text-center mb-4">
            <h2>What Our Customers Say</h2>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="card-text fst-italic">"Keygate transformed our event entry process. No more long queues or manual check-ins. It's been a game-changer for our conferences."</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="ms-3">
                            <h6 class="mb-0">John Smith</h6>
                            <small class="text-muted">Event Director, TechConf</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="card-text fst-italic">"The ability to monitor gate activity in real-time helped us optimize our staff positioning. The reports are incredibly detailed and useful."</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="ms-3">
                            <h6 class="mb-0">Sarah Johnson</h6>
                            <small class="text-muted">Operations Manager, Global Summit</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <p class="card-text fst-italic">"Setting up multiple gates with specific access permissions was simple. Our VIP guests had a seamless experience while maintaining proper security."</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="ms-3">
                            <h6 class="mb-0">David Chen</h6>
                            <small class="text-muted">Security Director, Annual Gala</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- CTA Section -->
    <div class="row py-5 text-center">
        <div class="col-md-8 mx-auto">
            <h2>Ready to simplify your event access management?</h2>
            <p class="lead mb-4">Join hundreds of event organizers who trust Keygate for their access control needs.</p>
            <a href="register.php" class="btn btn-lg btn-primary">Get Started Now</a>
        </div>
    </div>
</div>

<style>
    .circle-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: #0d6efd;
        color: white;
        font-size: 24px;
        font-weight: bold;
    }
</style>

<?php
// Include footer
include 'includes/footer.php';
?>