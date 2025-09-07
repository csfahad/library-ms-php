<?php
/**
 * Logout Script
 * Library Management System
 */

require_once '../config/database.php';
require_once 'auth.php';

// Logout the user
logout();

// Redirect to login page with success message
header('Location: ../index.php?message=logout_success');
exit();
?>
