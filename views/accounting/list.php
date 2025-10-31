<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/accounting/index.php';
require_once __DIR__ . '/../../controllers/records/index.php';

$pageTitle = 'Stock Accounting';

// Get record ID from query parameter
$recordId = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;

if ($recordId <= 0) {
    setFlash('Invalid record ID', FLASH_ERROR);
    redirect('index.php?page=stockbook');
}

// Get the stock record
$record = getRecordById($recordModel, $recordId);
if (!$record) {
    setFlash('Record not found', FLASH_ERROR);
    redirect('index.php?page=stockbook');
}

// Handle actions
$action = $_GET['action'] ?? '';
$entryId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Handle delete
handleDeleteAccounting($accountingModel);

// Handle create
$errors = [];
if (isset($_POST['action']) && $_POST['action'] === 'create_accounting') {
    $errors = handleCreateAccounting($accountingModel);
}

// Handle update
if (isset($_POST['action']) && $_POST['action'] === 'update_accounting') {
    $errors = handleUpdateAccounting($accountingModel);
}

// Get edit entry if editing
$editEntry = null;
if ($action === 'edit' && $entryId) {
    $editEntry = getAccountingForEdit($accountingModel, $entryId);
}

// Get all accounting entries for this record
$entries = getAccountingByRecord($accountingModel, $recordId);
$summary = getAccountingSummary($accountingModel, $recordId);
$flash = getFlash();
?>

<?php include __DIR__ . '/../../layout/header.php'; ?>

<?php include __DIR__ . '/../../layout/sidebar.php'; ?>

<?php include __DIR__ . '/../../layout/navbar.php'; ?>

<div class="main-content">
    <div class="container-fluid p-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php?page=stockbook">Stock Book</a></li>
                <li class="breadcrumb-item active" aria-current="page">Accounting</li>
            </ol>
        </nav>
        
        <!-- Record Info Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-2">
                            <i class="bi bi-calculator"></i> Stock Accounting
                        </h3>
                        <div class="d-flex flex-wrap gap-3">
                            <span><strong>Code:</strong> <?php echo htmlspecialchars($record['code']); ?></span>
                            <span><strong>Color:</strong> <?php echo htmlspecialchars($record['color']); ?></span>
                            <span><strong>Gauge:</strong> <?php echo htmlspecialchars($record['gauge']); ?></span>
                            <span><strong>Weight:</strong> <?php echo formatNumber($record['net_weight']); ?> KG</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                            <i class="bi bi-plus-circle"></i> Add Entry
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php displayErrors($errors); ?>
        
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
        
        <!-- Accounting Entries Table -->
        <?php if (empty($entries)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <h4 class="mt-3">No Accounting Entries</h4>
                    <p class="text-muted">Start by adding the first accounting entry for this stock item</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEntryModal">
                        <i class="bi bi-plus-circle"></i> Add Entry
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Accounting Entries</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Quantity In</th>
                                    <th>Quantity Out</th>
                                    <th>Balance</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entries as $entry): ?>
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
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="index.php?page=accounting&record_id=<?php echo $recordId; ?>&action=edit&id=<?php echo $entry['id']; ?>" 
                                               class="btn btn-warning btn-action" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-action" 
                                                    onclick="confirmDeleteEntry(<?php echo $entry['id']; ?>, <?php echo $recordId; ?>)" 
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <p class="text-muted">Total Entries: <strong><?php echo count($entries); ?></strong></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Entry Modal -->
<div class="modal fade" id="addEntryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=accounting&record_id=<?php echo $recordId; ?>">
                <input type="hidden" name="action" value="create_accounting">
                <input type="hidden" name="record_id" value="<?php echo $recordId; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Add Accounting Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="entry_date" class="form-label">Date *</label>
                        <input type="date" class="form-control" id="entry_date" name="entry_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity_in" class="form-label">Quantity In</label>
                        <input type="number" step="0.01" class="form-control" id="quantity_in" 
                               name="quantity_in" value="0" min="0">
                        <small class="text-muted">Stock added/received</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity_out" class="form-label">Quantity Out</label>
                        <input type="number" step="0.01" class="form-control" id="quantity_out" 
                               name="quantity_out" value="0" min="0">
                        <small class="text-muted">Stock removed/sold</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="bi bi-info-circle"></i> 
                            Current Balance: <strong><?php echo formatNumber($summary['current_balance']); ?></strong>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Add Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Entry Modal -->
<?php if ($editEntry): ?>
<div class="modal fade show" id="editEntryModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=accounting&record_id=<?php echo $recordId; ?>&action=edit&id=<?php echo $editEntry['id']; ?>">
                <input type="hidden" name="action" value="update_accounting">
                <input type="hidden" name="id" value="<?php echo $editEntry['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Accounting Entry</h5>
                    <a href="index.php?page=accounting&record_id=<?php echo $recordId; ?>" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_entry_date" class="form-label">Date *</label>
                        <input type="date" class="form-control" id="edit_entry_date" name="entry_date" 
                               value="<?php echo $editEntry['entry_date']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_quantity_in" class="form-label">Quantity In</label>
                        <input type="number" step="0.01" class="form-control" id="edit_quantity_in" 
                               name="quantity_in" value="<?php echo $editEntry['quantity_in']; ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_quantity_out" class="form-label">Quantity Out</label>
                        <input type="number" step="0.01" class="form-control" id="edit_quantity_out" 
                               name="quantity_out" value="<?php echo $editEntry['quantity_out']; ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="edit_remarks" name="remarks" rows="3"><?php echo htmlspecialchars($editEntry['remarks'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=accounting&record_id=<?php echo $recordId; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle"></i> Update Entry
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function confirmDeleteEntry(entryId, recordId) {
    if (confirm('Are you sure you want to delete this accounting entry?')) {
        window.location.href = 'index.php?page=accounting&record_id=' + recordId + '&action=delete_accounting&id=' + entryId;
    }
}
</script>

<?php include __DIR__ . '/../../layout/footer.php'; ?>