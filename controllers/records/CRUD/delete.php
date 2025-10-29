<?php
// Delete record controller

require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../models/record.php';
require_once __DIR__ . '/../../../utils/helpers.php';
require_once __DIR__ . '/../../../utils/authMiddleware.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

checkAuth();

$recordModel = new Record($conn);

/**
 * Handle record deletion
 */
function handleDeleteRecord($recordModel) {
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        if ($id > 0) {
            $result = $recordModel->delete($id, getCurrentUserId());
            
            if ($result['success']) {
                setFlash('Record deleted successfully', FLASH_SUCCESS);
            } else {
                setFlash($result['message'], FLASH_ERROR);
            }
        } else {
            setFlash('Invalid record ID', FLASH_ERROR);
        }
        
        redirect('index.php?page=stockbook');
    }
}
?>