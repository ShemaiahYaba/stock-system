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
    'Teal',
    'Tri-Color',
    'Fire-Color',
    'Silver-Color',
    'Blue-Fean',
    'Faint-Beige',
]);

// Sales status options
define('SALE_STATUSES', [
    'factory use',
    'in production',
    'sold out'
]);

// Gauge options (thickness measurements)
define('GAUGES', [
    '0.55',
    '0.45',
    '0.35',
    '0.53',
    '0.51',
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