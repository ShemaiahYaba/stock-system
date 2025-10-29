<!-- /layout/quick-action-buttons.php -->
<?php
/**
 * Reusable quick action buttons component
 * Usage: renderActionButtons($id, $code);
 */

function renderActionButtons($id, $code) {
    echo '<div class="btn-group btn-group-sm" role="group">';
    
    // View button
    echo '<button type="button" class="btn btn-info btn-action" data-bs-toggle="modal" data-bs-target="#viewModal' . $id . '" title="View">';
    echo '<i class="bi bi-eye"></i>';
    echo '</button>';
    
    // Edit button
    echo '<a href="index.php?page=stockbook&action=edit&id=' . $id . '" class="btn btn-warning btn-action" title="Edit">';
    echo '<i class="bi bi-pencil"></i>';
    echo '</a>';
    
    // Delete button
    echo '<button type="button" class="btn btn-danger btn-action" onclick="confirmDelete(' . $id . ', \'' . addslashes($code) . '\')" title="Delete">';
    echo '<i class="bi bi-trash"></i>';
    echo '</button>';
    
    echo '</div>';
}
?>