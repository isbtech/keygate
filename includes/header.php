<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - Keygate' : 'Keygate - Event Access Management'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    
    <!-- Custom CSS -->
    <style>
        .navbar-brand {
            font-weight: bold;
            letter-spacing: 1px;
        }
        .nav-link {
            font-weight: 500;
        }
        .alert {
            margin-top: 20px;
        }
        footer {
            margin-top: 50px;
            padding: 20px 0;
            background-color: #f8f9fa;
        }
    </style>
    
    <!-- Additional CSS if any -->
    <?php if (isset($additional_css)): ?>
        <?php echo $additional_css; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-key-fill"></i> Keygate
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <?php if (is_admin()): ?>
                                    <li><a class="dropdown-item" href="admin/dashboard.php">Admin Dashboard</a></li>
                                <?php elseif (is_event_admin()): ?>
                                    <li><a class="dropdown-item" href="event_admin/dashboard.php">Event Admin Dashboard</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php elseif (is_event_user()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-badge"></i> <?php echo $_SESSION['event_user_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <?php if (is_event_user('GateKeeper')): ?>
                                    <li><a class="dropdown-item" href="gatekeeper/dashboard.php">Gatekeeper Dashboard</a></li>
                                <?php elseif (is_event_user('Event Manager')): ?>
                                    <li><a class="dropdown-item" href="event_manager/dashboard.php">Event Manager Dashboard</a></li>
                                <?php elseif (is_event_user('Volunteer')): ?>
                                    <li><a class="dropdown-item" href="volunteer/dashboard.php">Volunteer Dashboard</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>