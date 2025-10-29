<?php
// Read records controller

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
 * Get all records for current user
 */
function getAllRecords($recordModel) {
    return $recordModel->getAll(getCurrentUserId());
}

/**
 * Get single record by ID
 */
function getRecordById($recordModel, $id) {
    return $recordModel->getById($id, getCurrentUserId());
}

/**
 * Get total records count
 */
function getTotalRecords($recordModel) {
    return $recordModel->count(getCurrentUserId());
}
?>