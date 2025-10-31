<?php
// Update accounting entry controller

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

/**
 * Handle accounting entry update
 */
function handleUpdateAccounting($accountingModel) {
    $errors = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_accounting') {
        $id = intval($_POST['id'] ?? 0);
        $entryDate = sanitize($_POST['entry_date'] ?? '');
        $quantityIn = sanitize($_POST['quantity_in'] ?? '0');
        $quantityOut = sanitize($_POST['quantity_out'] ?? '0');
        $remarks = sanitize($_POST['remarks'] ?? '');
        
        // Validation
        if ($id <= 0) {
            $errors[] = 'Invalid entry ID';
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
        
        // Get existing entry to calculate balance
        if (empty($errors)) {
            $existingEntry = $accountingModel->getById($id, getCurrentUserId());
            if (!$existingEntry) {
                $errors[] = 'Entry not found';
            }
        }
        
        // Calculate new balance (simplified - just recalculate based on current in/out)
        if (empty($errors)) {
            // Get all entries for the record except this one
            $recordId = $existingEntry['record_id'];
            $entries = $accountingModel->getByRecordId($recordId, getCurrentUserId());
            
            $runningBalance = 0;
            foreach ($entries as $entry) {
                if ($entry['id'] != $id) {
                    $runningBalance += floatval($entry['quantity_in']) - floatval($entry['quantity_out']);
                }
            }
            
            // Add the new values
            $newBalance = $runningBalance + floatval($quantityIn) - floatval($quantityOut);
            
            if ($newBalance < 0) {
                $errors[] = 'Cannot remove more quantity than available';
            }
        }
        
        // Update if no errors
        if (empty($errors)) {
            $data = [
                'entry_date' => $entryDate,
                'quantity_in' => $quantityIn,
                'quantity_out' => $quantityOut,
                'balance' => $newBalance,
                'remarks' => $remarks
            ];
            
            $result = $accountingModel->update($id, getCurrentUserId(), $data);
            
            if ($result['success']) {
                setFlash('Accounting entry updated successfully', FLASH_SUCCESS);
                redirect('index.php?page=accounting&record_id=' . $existingEntry['record_id']);
            } else {
                $errors[] = $result['message'];
            }
        }
    }
    
    return $errors;
}

/**
 * Get accounting entry for editing
 */
function getAccountingForEdit($accountingModel, $id) {
    if ($id) {
        return $accountingModel->getById($id, getCurrentUserId());
    }
    return null;
}
?>