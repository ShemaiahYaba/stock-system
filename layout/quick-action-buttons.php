<!-- /layout/quick-action-buttons.php -->
<?php
/**
 * Reusable quick action buttons component
 * Usage: renderActionButtons($id, $code, $status = '');
 * 
 * @param int $id Record ID
 * @param string $code Item code
 * @param string $status Current status of the record
 */

function renderActionButtons($id, $code, $status = '') {
    echo '<div class="btn-group btn-group-sm" role="group">';
    
    // View button
    echo '<button type="button" class="btn btn-info btn-action" data-bs-toggle="modal" data-bs-target="#viewModal' . $id . '" title="View">';
    echo '<i class="bi bi-eye"></i>';
    echo '</button>';
    
    // Edit button
    echo '<a href="index.php?page=stockbook&action=edit&id=' . $id . '" class="btn btn-warning btn-action" title="Edit">';
    echo '<i class="bi bi-pencil"></i>';
    echo '</a>';
    
    // Accounting button - Only show for 'factory use' status
    if (strtolower(trim($status)) === 'factory use') {
        echo '<a href="index.php?page=accounting&record_id=' . $id . '" class="btn btn-primary btn-action" title="Accounting">';
        echo '<i class="bi bi-calculator"></i>';
        echo '</a>';
    }
    
    // Delete button
    echo '<button type="button" class="btn btn-danger btn-action" onclick="confirmDelete(' . $id . ', \'' . addslashes($code) . '\')" title="Delete">';
    echo '<i class="bi bi-trash"></i>';
    echo '</button>';
    
    echo '</div>';
}
?>