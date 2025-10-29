<?php
// Create record controller

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

// Ensure records table exists
$recordModel->createTable();

/**
 * Handle record creation
 */
function handleCreateRecord($recordModel) {
    $errors = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
        $code = sanitize($_POST['code'] ?? '');
        $color = sanitize($_POST['color'] ?? '');
        $netWeight = sanitize($_POST['net_weight'] ?? '');
        $gauge = sanitize($_POST['gauge'] ?? '');
        $salesStatus = sanitize($_POST['sales_status'] ?? '');
        $noOfMeters = sanitize($_POST['no_of_meters'] ?? '');
        
        // Validation
        if (empty($code)) {
            $errors[] = 'Code is required';
        } elseif ($recordModel->codeExists($code, getCurrentUserId())) {
            $errors[] = 'Code already exists';
        }
        
        if (empty($color)) {
            $errors[] = 'Color is required';
        }
        
        if (empty($netWeight) || !is_numeric($netWeight)) {
            $errors[] = 'Valid net weight is required';
        }
        
        if (empty($gauge)) {
            $errors[] = 'Gauge is required';
        }
        
        if (empty($salesStatus)) {
            $errors[] = 'Sales status is required';
        }
        
        if (empty($noOfMeters) || !is_numeric($noOfMeters)) {
            $errors[] = 'Valid number of meters is required';
        }
        
        // Create if no errors
        if (empty($errors)) {
            $data = [
                'user_id' => getCurrentUserId(),
                'code' => $code,
                'color' => $color,
                'net_weight' => $netWeight,
                'gauge' => $gauge,
                'sales_status' => $salesStatus,
                'no_of_meters' => $noOfMeters
            ];
            
            $result = $recordModel->create($data);
            
            if ($result['success']) {
                setFlash('Record created successfully', FLASH_SUCCESS);
                redirect('index.php?page=stockbook');
            } else {
                $errors[] = $result['message'];
            }
        }
    }
    
    return $errors;
}
?>