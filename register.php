<?php
/**
 * Registration Page
 */

require_once __DIR__ . '/utils/authMiddleware.php';
require_once __DIR__ . '/controllers/authC.php';

// Check if already logged in
checkGuest();

// Handle registration
$errors = handleRegister($userModel);

// Include registration view
include __DIR__ . '/views/auth/register.php';
?>