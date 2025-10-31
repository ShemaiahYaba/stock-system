<?php
// Read accounting entries controller

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
 * Get all accounting entries for a specific record
 */
function getAccountingByRecord($accountingModel, $recordId) {
    return $accountingModel->getByRecordId($recordId, getCurrentUserId());
}

/**
 * Get all accounting entries for current user
 */
function getAllAccounting($accountingModel) {
    return $accountingModel->getAllByUserId(getCurrentUserId());
}

/**
 * Get single accounting entry by ID
 */
function getAccountingById($accountingModel, $id) {
    return $accountingModel->getById($id, getCurrentUserId());
}

/**
 * Get summary for a record
 */
function getAccountingSummary($accountingModel, $recordId) {
    return $accountingModel->getSummary($recordId, getCurrentUserId());
}

/**
 * Count entries for a record
 */
function countAccountingEntries($accountingModel, $recordId) {
    return $accountingModel->countByRecord($recordId, getCurrentUserId());
}
?>