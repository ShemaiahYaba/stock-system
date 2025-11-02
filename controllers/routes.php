<?php
/**
 * Application Routes
 * 
 * This file handles all routing for the application.
 * It determines which view to load based on the 'page' parameter.
 */

// Load required files
require_once __DIR__ . '/../utils/authMiddleware.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is authenticated
checkAuth();

// Get the requested page
$page = $_GET['page'] ?? 'dashboard';

// Define allowed pages
$allowedPages = [
    'dashboard',
    'stockbook',
    'accounting'
];

// Validate page request
if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

// Route to the appropriate view
switch ($page) {
    case 'stockbook':
        require_once __DIR__ . '/../views/stockbook.php';
        break;
        
    case 'accounting':
        require_once __DIR__ . '/../views/accounting/list.php';
        break;
        
    case 'dashboard':
    default:
        require_once __DIR__ . '/../views/dashboard.php';
        break;
}