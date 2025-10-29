<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/controllers/authC.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle logout
handleLogout();
?>