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
        'factory use' => 'success',     // Green
        'in production' => 'warning',   // Yellow
        'sold out' => 'secondary'       // Grey
    ];
    
    // Get the status from record and ensure it matches the exact case
    $status = strtolower(trim($record['sales_status']));
    $statusColor = 'secondary';  // Default color if no match
    
    // Find matching status with case-insensitive comparison
    foreach ($statusColors as $key => $statusColorValue) {
        if (strtolower($key) === $status) {
            $statusColor = $statusColorValue;
            break;
        }
    }
    
    echo "<tr>";
    echo "<td>{$index}</td>";
    echo "<td><strong>{$code}</strong></td>";
    echo "<td>{$color}</td>";
    echo "<td>{$netWeight}</td>";
    echo "<td>{$gauge}</td>";
    echo "<td><span class='badge bg-{$statusColor}'>{$salesStatus}</span></td>";
    echo "<td>{$noOfMeters}</td>";
    echo "<td>";
    include_once __DIR__ . '/quick-action-buttons.php';
    renderActionButtons($id, $code, $record['sales_status']);
    echo "</td>";
    echo "</tr>";
}
?>