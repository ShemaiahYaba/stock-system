<?php
// Create accounting entry controller

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

// Ensure accounting table exists
$accountingModel->createTable();

/**
 * Handle accounting entry creation
 */
function handleCreateAccounting($accountingModel, $recordModel) {
    $errors = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_accounting') {
        $userId = $_SESSION['user_id'];
        $recordId = intval($_POST['record_id'] ?? 0);
        $entryDate = sanitize($_POST['entry_date'] ?? date('Y-m-d'));
        $transactionType = sanitize($_POST['transaction_type'] ?? '');
        $quantity = floatval($_POST['quantity'] ?? 0);
        $remarks = sanitize($_POST['remarks'] ?? '');
        
        // Validation
        if ($recordId <= 0) {
            $errors[] = 'Invalid record ID';
        }
        
        if (empty($entryDate)) {
            $errors[] = 'Entry date is required';
        }
        
        if (!in_array($transactionType, ['inflow', 'outflow'])) {
            $errors[] = 'Invalid transaction type';
        }
        
        if (!is_numeric($quantity) || $quantity <= 0) {
            $errors[] = 'Valid quantity is required (must be greater than 0)';
        }
        
        // Check if record exists and belongs to user
        $record = $recordModel->getById($recordId, $userId);
        if (!$record) {
            $errors[] = 'Record not found or access denied';
        }
        
        // Calculate new balance
        if (empty($errors)) {
            $previousBalance = $accountingModel->getLatestBalance($recordId, $userId);
            if ($transactionType === 'inflow') {
                $newBalance = $previousBalance + $quantity;
            } else {
                $newBalance = $previousBalance - $quantity;
            }
            
            if ($newBalance < 0) {
                $errors[] = 'Cannot remove more quantity than available (Current balance: ' . formatNumber($previousBalance) . ')';
            }
        }
        
        if (empty($errors)) {
            // Create entry
            $result = $accountingModel->create([
                'user_id' => $userId,
                'record_id' => $recordId,
                'entry_date' => $entryDate,
                'transaction_type' => $transactionType,
                'quantity' => $quantity,
                'balance' => $newBalance,
                'remarks' => $remarks
            ]);
            
            if ($result['success']) {
                setFlash('Accounting entry created successfully', FLASH_SUCCESS);
                redirect('index.php?page=accounting&record_id=' . $recordId);
            } else {
                $errors[] = $result['message'] ?? 'Failed to create accounting entry';
            }
        }
    }
    
    return $errors;
}
?>