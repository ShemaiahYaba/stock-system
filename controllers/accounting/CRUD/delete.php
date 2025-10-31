<?php
// Delete accounting entry controller

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
 * Handle accounting entry deletion
 */
function handleDeleteAccounting($accountingModel) {
    if (isset($_GET['action']) && $_GET['action'] === 'delete_accounting' && isset($_GET['id']) && isset($_GET['record_id'])) {
        $id = intval($_GET['id']);
        $recordId = intval($_GET['record_id']);
        
        if ($id > 0) {
            $result = $accountingModel->delete($id, getCurrentUserId());
            
            if ($result['success']) {
                setFlash('Accounting entry deleted successfully', FLASH_SUCCESS);
            } else {
                setFlash($result['message'], FLASH_ERROR);
            }
        } else {
            setFlash('Invalid entry ID', FLASH_ERROR);
        }
        
        redirect('index.php?page=accounting&record_id=' . $recordId);
    }
}
?>