<!-- /config/constants.php -->
<?php
// Global constants and enums

// Color options for stock items
define('COLORS', [
    'Red',
    'Blue',
    'Green',
    'Yellow',
    'Black',
    'White',
    'Orange',
    'Purple',
    'Pink',
    'Brown',
    'Gray',
    'Beige',
    'Navy',
    'Maroon',
    'Teal'
]);

// Sales status options
define('SALE_STATUSES', [
    'Available',
    'Sold',
    'Reserved',
    'Pending',
    'Out of Stock'
]);

// Gauge options (thickness measurements)
define('GAUGES', [
    '0.55',
    '0.45',
    '0.35',
]);

// ✅ NEW: Accounting entry types
define('ACCOUNTING_ENTRY_TYPES', [
    'Inflow',
    'Outflow',
    'Adjustment',
    'Transfer',
    'Return'
]);

// Application settings
define('APP_NAME', 'Stock System');
define('APP_VERSION', '1.0.0');
define('ITEMS_PER_PAGE', 20);

// Session keys
define('SESSION_USER_ID', 'user_id');
define('SESSION_USER_EMAIL', 'user_email');
define('SESSION_USER_NAME', 'user_name');

// Flash message types
define('FLASH_SUCCESS', 'success');
define('FLASH_ERROR', 'danger');
define('FLASH_WARNING', 'warning');
define('FLASH_INFO', 'info');
?>