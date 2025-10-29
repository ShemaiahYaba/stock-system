<?php
// Authentication middleware

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/helpers.php';

/**
 * Check if user is authenticated
 * Redirect to login if not
 */
function checkAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isLoggedIn()) {
        setFlash('Please login to access this page', FLASH_WARNING);
        redirect('login.php');
    }
}

/**
 * Check if user is guest (not logged in)
 * Redirect to dashboard if already logged in
 */
function checkGuest() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isLoggedIn()) {
        redirect('index.php');
    }
}
?>