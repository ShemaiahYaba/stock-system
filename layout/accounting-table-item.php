<?php
/**
 * Reusable accounting table row component
 * Usage: renderAccountingTableItem($entry);
 */

function renderAccountingTableItem($entry) {
    echo '<tr>';
    
    // Date
    echo '<td>' . date('M d, Y', strtotime($entry['entry_date'])) . '</td>';
    
    // Entry Type with badge
    $typeColors = [
        'Inflow' => 'success',
        'Outflow' => 'danger',
        'Adjustment' => 'warning',
        'Transfer' => 'info',
        'Return' => 'primary'
    ];
    $typeColor = $typeColors[$entry['entry_type']] ?? 'secondary';
    echo '<td><span class="badge bg-' . $typeColor . '">' . htmlspecialchars($entry['entry_type']) . '</span></td>';
    
    // Quantity In
    $quantityIn = floatval($entry['quantity_in']);
    if ($quantityIn > 0) {
        echo '<td class="text-success fw-bold">+' . formatNumber($quantityIn) . '</td>';
    } else {
        echo '<td class="text-muted">-</td>';
    }
    
    // Quantity Out
    $quantityOut = floatval($entry['quantity_out']);
    if ($quantityOut > 0) {
        echo '<td class="text-danger fw-bold">-' . formatNumber($quantityOut) . '</td>';
    } else {
        echo '<td class="text-muted">-</td>';
    }
    
    // Balance
    echo '<td class="fw-bold">' . formatNumber($entry['balance']) . '</td>';
    
    // Remarks
    $remarks = htmlspecialchars($entry['remarks']);
    $shortRemarks = strlen($remarks) > 30 ? substr($remarks, 0, 30) . '...' : $remarks;
    echo '<td><small class="text-muted">' . ($remarks ? $shortRemarks : '-') . '</small></td>';
    
    // Actions
    echo '<td>';
    renderAccountingActionButtons($entry['id'], $entry['record_id']);
    echo '</td>';
    
    echo '</tr>';
}

/**
 * Render action buttons for accounting entries
 */
function renderAccountingActionButtons($id, $recordId) {
    echo '<div class="btn-group btn-group-sm" role="group">';
    
    // Edit button
    echo '<a href="index.php?page=accounting&record_id=' . $recordId . '&action=edit&id=' . $id . '" class="btn btn-warning btn-action" title="Edit">';
    echo '<i class="bi bi-pencil"></i>';
    echo '</a>';
    
    // Delete button
    echo '<button type="button" class="btn btn-danger btn-action" onclick="confirmDeleteAccounting(' . $id . ', ' . $recordId . ')" title="Delete">';
    echo '<i class="bi bi-trash"></i>';
    echo '</button>';
    
    echo '</div>';
}
?>

<script>
function confirmDeleteAccounting(id, recordId) {
    if (confirm('Are you sure you want to delete this accounting entry? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'index.php?page=accounting&record_id=' + recordId;
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_accounting';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'id';
        idInput.value = id;
        
        form.appendChild(actionInput);
        form.appendChild(idInput);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>