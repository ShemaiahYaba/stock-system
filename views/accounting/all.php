<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/accounting/index.php';
require_once __DIR__ . '/../../controllers/records/index.php';

$pageTitle = 'Stock Accounting - All Records';

// Get all records for user
$records = getAllRecords($recordModel);

// Get selected record from query or use first one
$selectedRecordId = isset($_GET['record_id']) ? intval($_GET['record_id']) : null;

if (!$selectedRecordId && !empty($records)) {
    $selectedRecordId = $records[0]['id'];
}

$selectedRecord = null;
$entries = [];
$summary = ['total_in' => 0, 'total_out' => 0, 'current_balance' => 0];

if ($selectedRecordId) {
    $selectedRecord = getRecordById($recordModel, $selectedRecordId);
    if ($selectedRecord) {
        $entries = getAccountingByRecord($accountingModel, $selectedRecordId);
        $summary = getAccountingSummary($accountingModel, $selectedRecordId);
    }
}

$flash = getFlash();
?>

<?php include __DIR__ . '/../../layout/header.php'; ?>

<?php include __DIR__ . '/../../layout/sidebar.php'; ?>

<?php include __DIR__ . '/../../layout/navbar.php'; ?>

<div class="main-content">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-calculator"></i> Stock Accounting</h2>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($records)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <h4 class="mt-3">No Stock Records Found</h4>
                    <p class="text-muted">Create stock records first before managing accounting entries</p>
                    <a href="index.php?page=stockbook" class="btn btn-primary">
                        <i class="bi bi-journal-text"></i> Go to Stock Book
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Record Selector -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Stock Record:</label>
                            <select class="form-select" id="recordSelector" onchange="changeRecord()">
                                <?php foreach ($records as $record): ?>
                                    <option value="<?php echo $record['id']; ?>" 
                                            <?php echo $record['id'] == $selectedRecordId ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($record['code']); ?> - 
                                        <?php echo htmlspecialchars($record['color']); ?> 
                                        (<?php echo htmlspecialchars($record['gauge']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($selectedRecord): ?>
                        <div class="col-md-6 text-end mt-3 mt-md-0">
                            <a href="index.php?page=accounting&record_id=<?php echo $selectedRecordId; ?>" 
                               class="btn btn-primary">
                                <i class="bi bi-eye"></i> View Full Accounting
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($selectedRecord): ?>
                <!-- Record Info -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Record Details</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <strong>Code:</strong><br>
                                <?php echo htmlspecialchars($selectedRecord['code']); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Color:</strong><br>
                                <?php echo htmlspecialchars($selectedRecord['color']); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Gauge:</strong><br>
                                <?php echo htmlspecialchars($selectedRecord['gauge']); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Net Weight:</strong><br>
                                <?php echo formatNumber($selectedRecord['net_weight']); ?> KG
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h6 class="card-title text-uppercase">Total Inflow</h6>
                                <h3><?php echo formatNumber($summary['total_in']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h6 class="card-title text-uppercase">Total Outflow</h6>
                                <h3><?php echo formatNumber($summary['total_out']); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h6 class="card-title text-uppercase">Current Balance</h6>
                                <h3><?php echo formatNumber($summary['current_balance']); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Entries -->
                <?php if (empty($entries)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <h5 class="mt-3">No Accounting Entries</h5>
                            <p class="text-muted">No accounting entries found for this record</p>
                            <a href="index.php?page=accounting&record_id=<?php echo $selectedRecordId; ?>" 
                               class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Add First Entry
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">Recent Accounting Entries</h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Quantity In</th>
                                            <th>Quantity Out</th>
                                            <th>Balance</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $recentEntries = array_slice($entries, 0, 10);
                                        foreach ($recentEntries as $entry): 
                                        ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($entry['entry_date'])); ?></td>
                                            <td class="text-success">
                                                <?php if (floatval($entry['quantity_in']) > 0): ?>
                                                    <strong>+<?php echo formatNumber($entry['quantity_in']); ?></strong>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-danger">
                                                <?php if (floatval($entry['quantity_out']) > 0): ?>
                                                    <strong>-<?php echo formatNumber($entry['quantity_out']); ?></strong>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo formatNumber($entry['balance']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($entry['remarks'] ?: '-'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3 d-flex justify-content-between align-items-center">
                                <p class="text-muted mb-0">
                                    Showing <?php echo count($recentEntries); ?> of <?php echo count($entries); ?> entries
                                </p>
                                <a href="index.php?page=accounting&record_id=<?php echo $selectedRecordId; ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    View All & Manage
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function changeRecord() {
    const recordId = document.getElementById('recordSelector').value;
    window.location.href = 'index.php?page=accounting-list&record_id=' + recordId;
}
</script>

<?php include __DIR__ . '/../../layout/footer.php'; ?>