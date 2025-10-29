<?php
/**
 * Reusable table row component for stock book
 * Usage: renderTableItem($record, $index);
 */

function renderTableItem($record, $index) {
    $id = htmlspecialchars($record['id']);
    $code = htmlspecialchars($record['code']);
    $color = htmlspecialchars($record['color']);
    $netWeight = formatNumber($record['net_weight']);
    $gauge = htmlspecialchars($record['gauge']);
    $salesStatus = htmlspecialchars($record['sales_status']);
    $noOfMeters = formatNumber($record['no_of_meters']);
    
    // Status badge color
    $statusColors = [
        'Available' => 'success',
        'Sold' => 'secondary',
        'Reserved' => 'warning',
        'Pending' => 'info',
        'Out of Stock' => 'danger'
    ];
    $statusColor = $statusColors[$record['sales_status']] ?? 'secondary';
    
    echo "<tr>";
    echo "<td>{$index}</td>";
    echo "<td><strong>{$code}</strong></td>";
    echo "<td>{$color}</td>";
    echo "<td>{$netWeight}</td>";
    echo "<td>{$gauge}</td>";
    echo "<td><span class='badge bg-{$statusColor}'>{$salesStatus}</span></td>";
    echo "<td>{$noOfMeters}</td>";
    echo "<td>";
    include __DIR__ . '/quick-action-buttons.php';
    renderActionButtons($id, $code);
    echo "</td>";
    echo "</tr>";
}
?>