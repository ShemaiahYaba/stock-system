<?php
// Read accounting entries controller

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../models/accounting.php';
require_once __DIR__ . '/../../../models/record.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../utils/authMiddleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkAuth();

$accountingModel = new Accounting($conn);
$recordModel = new Record($conn);

/**
 * Get all accounting entries for a record with pagination
 */
function getAccountingByRecord($accountingModel, $recordId, $userId, $page = 1, $perPage = 20) {
    // Verify record belongs to user
    $record = $recordModel->getById($recordId, $userId);
    if (!$record) {
        return [
            'data' => [],
            'pagination' => [
                'total' => 0,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => 0
            ]
        ];
    }
    
    return $accountingModel->getMovementHistory($recordId, $userId, $page, $perPage);
}

/**
 * Get accounting entry for editing
 */
function getAccountingForEdit($accountingModel, $id) {
    $userId = $_SESSION['user_id'];
    $entry = $accountingModel->getById($id, $userId);
    
    if ($entry) {
        // Set quantity based on transaction type
        $entry['quantity'] = $entry['transaction_type'] === 'inflow' 
            ? $entry['quantity_in'] 
            : $entry['quantity_out'];
    }
    
    return $entry;
}

/**
 * Get accounting summary for a record
 */
function getAccountingSummary($accountingModel, $recordId) {
    $userId = $_SESSION['user_id'];
    
    // Get the latest balance
    $currentBalance = $accountingModel->getLatestBalance($recordId, $userId);
    
    // Get summary from model
    $summary = $accountingModel->getStockSummary($userId);
    
    // Find the specific record's summary
    $recordSummary = [
        'total_in' => 0,
        'total_out' => 0,
        'current_balance' => $currentBalance,
        'entry_count' => 0
    ];
    
    // Get the entry count
    $entries = $accountingModel->getByRecordId($recordId, $userId);
    $recordSummary['entry_count'] = count($entries);
    
    // Calculate totals
    foreach ($entries as $entry) {
        $recordSummary['total_in'] += $entry['quantity_in'];
        $recordSummary['total_out'] += $entry['quantity_out'];
    }
    
    return $recordSummary;
}

?>