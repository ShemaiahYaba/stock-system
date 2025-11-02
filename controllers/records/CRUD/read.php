<?php
// controllers/records/CRUD/read.php
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
$stockTypeId = isset($_GET['type']) ? intval($_GET['type']) : null;

/**
 * Get all records for current user, optionally filtered by stock type
 */
function getAllRecords($recordModel, $stockTypeId = null) {
    return $recordModel->getAll(getCurrentUserId(), $stockTypeId);
}

/**
 * Get single record by ID
 */
function getRecordById($recordModel, $id) {
    $userId = getCurrentUserId();
    error_log("Getting record ID: $id for user ID: $userId");
    $record = $recordModel->getById($id, $userId);
    if (!$record) {
        error_log("Record not found or access denied. User ID: $userId, Record ID: $id");
    }
    return $record;
}

/**
 * Get total records count, optionally filtered by stock type
 */
function getTotalRecords($recordModel, $stockTypeId = null) {
    return $recordModel->count(getCurrentUserId(), $stockTypeId);
}