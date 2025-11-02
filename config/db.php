<?php
// Database configuration for MySQL
// Replace these values with your MySQL connection details

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventory_management');

// Establish connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset to utf8mb4 for proper encoding
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>