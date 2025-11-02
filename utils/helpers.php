<?php
// Utility functions and formatters

/**
 * Sanitize input data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Format number with 2 decimal places
 */
function formatNumber($number) {
    return number_format((float)$number, 2, '.', ',');
}

/**
 * Redirect to a page
 */
function redirect($page) {
    header("Location: $page");
    exit();
}

/**
 * Set flash message
 */
function setFlash($message, $type = FLASH_SUCCESS) {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION[SESSION_USER_ID]);
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION[SESSION_USER_ID] ?? null;
}

/**
 * Get current user email
 */
function getCurrentUserEmail() {
    return $_SESSION[SESSION_USER_EMAIL] ?? null;
}

/**
 * Generate random code
 */
function generateCode($prefix = '', $length = 6) {
    // return $prefix . '-' . strtoupper(bin2hex(random_bytes($length / 2)));
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Format date for display
 */
function formatDate($dateString, $format = 'd M Y H:i') {
    if (empty($dateString)) {
        return '-';
    }
    $date = new DateTime($dateString);
    return $date->format($format);
}

/**
 * Display error messages
 */
function displayErrors($errors) {
    if (!empty($errors)) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo '<ul class="mb-0">';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}
?>