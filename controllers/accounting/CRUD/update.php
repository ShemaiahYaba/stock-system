<?php
// Update accounting entry controller

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
 * Handle accounting entry update
 */
function handleUpdateAccounting($accountingModel, $recordModel) {
    $errors = [];
    $userId = $_SESSION['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_accounting') {
        $id = intval($_POST['id'] ?? 0);
        $recordId = intval($_POST['record_id'] ?? 0);
        $entryDate = sanitize($_POST['entry_date'] ?? date('Y-m-d'));
        $transactionType = sanitize($_POST['transaction_type'] ?? '');
        $quantity = floatval($_POST['quantity'] ?? 0);
        $remarks = sanitize($_POST['remarks'] ?? '');
        
        // Validation
        if ($id <= 0) {
            $errors[] = 'Invalid entry ID';
        }
        
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
        
        if (empty($errors)) {
            // Get current entry to verify ownership
            $currentEntry = $accountingModel->getById($id, $userId);
            if (!$currentEntry) {
                $errors[] = 'Entry not found or access denied';
            } else {
                // Calculate new balance
                $previousBalance = $accountingModel->getBalanceBefore($recordId, $id);
                $newBalance = $previousBalance;
                
                if ($transactionType === 'inflow') {
                    $newBalance += $quantity;
                } else {
                    $newBalance -= $quantity;
                    
                    // Check if we have enough stock
                    if ($newBalance < 0) {
                        $errors[] = 'Cannot remove more quantity than available';
                    }
                }
                
                if (empty($errors)) {
                    // Update entry
                    $result = $accountingModel->update($id, $userId, [
                        'entry_date' => $entryDate,
                        'transaction_type' => $transactionType,
                        'quantity' => $quantity,
                        'balance' => $newBalance,
                        'remarks' => $remarks
                    ]);
                    
                    if ($result['success']) {
                        setFlash('Accounting entry updated successfully', FLASH_SUCCESS);
                        redirect('index.php?page=accounting&record_id=' . $recordId);
                    } else {
                        $errors[] = $result['message'] ?? 'Failed to update accounting entry';
                    }
                }
            }
        }
        
        return $errors;
    }
    
    return [];
}

?>