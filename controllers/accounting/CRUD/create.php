<?php
// Create accounting entry controller

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../models/accounting.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../utils/authMiddleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkAuth();

$accountingModel = new Accounting($conn);

// Ensure accounting table exists
$accountingModel->createTable();

/**
 * Handle accounting entry creation
 */
function handleCreateAccounting($accountingModel) {
    $errors = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_accounting') {
        $recordId = intval($_POST['record_id'] ?? 0);
        $entryDate = sanitize($_POST['entry_date'] ?? '');
        $quantityIn = sanitize($_POST['quantity_in'] ?? '0');
        $quantityOut = sanitize($_POST['quantity_out'] ?? '0');
        $remarks = sanitize($_POST['remarks'] ?? '');
        
        // Validation
        if ($recordId <= 0) {
            $errors[] = 'Invalid record ID';
        }
        
        if (empty($entryDate)) {
            $errors[] = 'Entry date is required';
        }
        
        if (!is_numeric($quantityIn) || floatval($quantityIn) < 0) {
            $errors[] = 'Valid quantity in is required (must be 0 or positive)';
        }
        
        if (!is_numeric($quantityOut) || floatval($quantityOut) < 0) {
            $errors[] = 'Valid quantity out is required (must be 0 or positive)';
        }
        
        if (floatval($quantityIn) == 0 && floatval($quantityOut) == 0) {
            $errors[] = 'Either quantity in or quantity out must be greater than 0';
        }
        
        // Calculate new balance
        if (empty($errors)) {
            $previousBalance = $accountingModel->getLatestBalance($recordId, getCurrentUserId());
            $newBalance = $previousBalance + floatval($quantityIn) - floatval($quantityOut);
            
            if ($newBalance < 0) {
                $errors[] = 'Cannot remove more quantity than available (Current balance: ' . formatNumber($previousBalance) . ')';
            }
        }
        
        // Create if no errors
        if (empty($errors)) {
            $data = [
                'record_id' => $recordId,
                'entry_date' => $entryDate,
                'quantity_in' => $quantityIn,
                'quantity_out' => $quantityOut,
                'balance' => $newBalance,
                'remarks' => $remarks
            ];
            
            $result = $accountingModel->create($data);
            
            if ($result['success']) {
                setFlash('Accounting entry created successfully', FLASH_SUCCESS);
                redirect('index.php?page=accounting&record_id=' . $recordId);
            } else {
                $errors[] = $result['message'];
            }
        }
    }
    
    return $errors;
}
?>