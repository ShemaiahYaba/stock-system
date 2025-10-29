<?php
// Authentication controller

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../models/user.php';
require_once __DIR__ . '/../utils/helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userModel = new User($conn);

// Ensure users table exists
$userModel->createTable();

/**
 * Handle user registration
 */
function handleRegister($userModel) {
    $errors = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($name)) {
            $errors[] = 'Name is required';
        }
        
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!isValidEmail($email)) {
            $errors[] = 'Invalid email format';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // Register if no errors
        if (empty($errors)) {
            $result = $userModel->register($name, $email, $password);
            
            if ($result['success']) {
                setFlash('Registration successful! Please login.', FLASH_SUCCESS);
                redirect('login.php');
            } else {
                $errors[] = $result['message'];
            }
        }
    }
    
    return $errors;
}

/**
 * Handle user login
 */
function handleLogin($userModel) {
    $errors = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validation
        if (empty($email)) {
            $errors[] = 'Email is required';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required';
        }
        
        // Login if no errors
        if (empty($errors)) {
            $result = $userModel->login($email, $password);
            
            if ($result['success']) {
                // Set session
                $_SESSION[SESSION_USER_ID] = $result['user']['id'];
                $_SESSION[SESSION_USER_EMAIL] = $result['user']['email'];
                $_SESSION[SESSION_USER_NAME] = $result['user']['name'];
                
                setFlash('Welcome back, ' . $result['user']['name'] . '!', FLASH_SUCCESS);
                redirect('index.php');
            } else {
                $errors[] = $result['message'];
            }
        }
    }
    
    return $errors;
}

/**
 * Handle user logout
 */
function handleLogout() {
    // Destroy session
    session_destroy();
    
    // Start new session for flash message
    session_start();
    setFlash('You have been logged out successfully', FLASH_INFO);
    
    redirect('login.php');
}
?>