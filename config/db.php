<?php
// Database configuration using Supabase
// Replace these values with your Supabase connection details

define('DB_HOST', 'aws-1-eu-west-1.pooler.supabase.com');
define('DB_PORT', '5432');
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres.tjdimldgwxfnvoscexhw');
define('DB_PASS', 'test-123');

// Create connection string
$connection_string = sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s",
    DB_HOST,
    DB_PORT,
    DB_NAME,
    DB_USER,
    DB_PASS
);

// Establish connection
try {
    $conn = pg_connect($connection_string);
    
    if (!$conn) {
        throw new Exception("Failed to connect to database");
    }
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>