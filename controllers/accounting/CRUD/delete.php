<?php
// Delete accounting entry controller

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
 * Handle accounting entry deletion
 */
function handleDeleteAccounting($accountingModel) {
    global $recordModel;
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete_accounting') {
        $id = intval($_GET['id'] ?? 0);
        $recordId = intval($_GET['record_id'] ?? 0);
        $userId = $_SESSION['user_id'];
        
        if ($id <= 0 || $recordId <= 0) {
            setFlash('Invalid parameters', FLASH_ERROR);
            return;
        }
        
        // Verify the record belongs to the user
        $record = $recordModel->getById($recordId, $userId);
        if (!$record) {
            setFlash('Record not found or access denied', FLASH_ERROR);
            redirect('index.php?page=stockbook');
            return;
        }
        
        // Verify the entry belongs to the record and user
        $entry = $accountingModel->getById($id, $userId);
        if (!$entry || $entry['record_id'] != $recordId) {
            setFlash('Entry not found or access denied', FLASH_ERROR);
            redirect('index.php?page=accounting&record_id=' . $recordId);
            return;
        }
        
        // Check if this is the only entry (can't delete the opening balance)
        $entries = $accountingModel->getByRecordId($recordId, $userId);
        if (count($entries) === 1) {
            setFlash('Cannot delete the only accounting entry for a record', FLASH_ERROR);
            redirect('index.php?page=accounting&record_id=' . $recordId);
            return;
        }
        
        // Proceed with deletion
        $result = $accountingModel->delete($id, $userId);
        
        if ($result['success']) {
            setFlash('Accounting entry deleted successfully', FLASH_SUCCESS);
        } else {
            setFlash($result['message'] ?? 'Failed to delete accounting entry', FLASH_ERROR);
        }
        
        redirect('index.php?page=accounting&record_id=' . $recordId);
    }
}
?>