<?php
/**
 * Login Page
 */

require_once __DIR__ . '/utils/authMiddleware.php';
require_once __DIR__ . '/controllers/authC.php';

// Check if already logged in
checkGuest();

// Handle login
$errors = handleLogin($userModel);

// Include login view
include __DIR__ . '/views/auth/login.php';
?>