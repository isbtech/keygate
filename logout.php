<?php
// Include configuration
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Logout user
logout_user();

// Redirect to login page
set_flash_message('success', 'You have been logged out successfully');
redirect('login.php');
?>