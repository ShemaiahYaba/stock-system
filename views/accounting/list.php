<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/accounting/index.php';
require_once __DIR__ . '/../../controllers/records/index.php';

$pageTitle = 'Stock Accounting';

// Debug: Log all GET parameters
error_log("GET parameters: " . print_r($_GET, true));

// Get record ID from query parameter
$recordId = isset($_GET['record_id']) ? intval($_GET['record_id']) : 0;

// Debug: Log user session
error_log("Current user ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set'));
error_log("Record ID from URL: $recordId");

if ($recordId <= 0) {
    error_log("Invalid record ID: $recordId");
    setFlash('Invalid record ID', FLASH_ERROR);
    redirect('index.php?page=stockbook');
}

// Debug: Log before fetching record
error_log("Fetching record with ID: $recordId");

// Get the stock record
$record = getRecordById($recordModel, $recordId);

// Debug: Log record fetch result
error_log("Record fetch result: " . ($record ? 'Found' : 'Not found'));

if (!$record) {
    // Check if record exists without user check
    $query = "SELECT id FROM records WHERE id = ?";
    $stmt = $recordModel->conn->prepare($query);
    $stmt->bind_param("i", $recordId);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    
    error_log("Record exists in database (without user check): " . ($exists ? 'Yes' : 'No'));
    
    setFlash('Record not found or access denied', FLASH_ERROR);
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
    $errors = handleCreateAccounting($accountingModel, $recordModel);
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

// Get all accounting entries for this record with pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;

// Get movement history with pagination
$movementHistory = $accountingModel->getMovementHistory($recordId, $_SESSION['user_id'], $page, $perPage);
$entries = $movementHistory['data'] ?? [];
$totalEntries = $movementHistory['total'] ?? 0;
$totalPages = $totalEntries > 0 ? ceil($totalEntries / $perPage) : 1;

// Get stock summary
$stockSummary = $accountingModel->getStockSummary($_SESSION['user_id']);
$currentRecordSummary = null;

// Find the current record in the summary
if (is_array($stockSummary)) {
    foreach ($stockSummary as $item) {
        if (isset($item['record_id']) && $item['record_id'] == $recordId) {
            $currentRecordSummary = $item;
            break;
        }
    }
}

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
        
        <!-- Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase">Total Inflow</h6>
                        <h3><?php echo formatNumber($currentRecordSummary ? $currentRecordSummary['total_in'] : 0); ?> m</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase">Total Outflow</h6>
                        <h3><?php echo formatNumber($currentRecordSummary ? $currentRecordSummary['total_out'] : 0); ?> m</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase">Current Balance</h6>
                        <h3><?php echo formatNumber($currentRecordSummary ? $currentRecordSummary['balance'] : 0); ?> m</h3>
                    </div>
                </div>
            </div>
        </div>
        

        <!-- Record Info Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-2">
                            <i class="bi bi-calculator"></i> Account Card for <?php echo htmlspecialchars($record['code']); ?>
                        </h3>
                        <div class="d-flex flex-wrap gap-3">
                            <span><strong>Stock Type:</strong> <?php echo htmlspecialchars($record['color']); ?></span>
                            <span><strong>Description:</strong> <?php echo htmlspecialchars($record['code']); ?></span>
                            <span><strong>Part No.:</strong> N/A</span>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Movement History</h5>
                            <div>
                                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#addInflowModal">
                                    <i class="bi bi-box-arrow-in-down"></i> Add Inflow
                                </button>
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addOutflowModal">
                                    <i class="bi bi-box-arrow-up"></i> Add Outflow
                                </button>
                            </div>
                        </div>
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
        
        <!-- Accounting Entries Table -->
        <?php if (empty($entries)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <h4 class="mt-3">No Accounting Entries</h4>
                    <p class="text-muted">Start by adding the first accounting entry for this stock item</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInflowModal">
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
                                    <th>Transaction Type</th>
                                    <th class="text-end">Quantity (m)</th>
                                    <th class="text-end">Balance (m)</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td><?php echo formatDate($entry['entry_date']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $entry['transaction_type'] === 'inflow' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($entry['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <?php echo formatNumber($entry['transaction_type'] === 'inflow' ? $entry['quantity_in'] : $entry['quantity_out']); ?>
                                    </td>
                                    <td class="text-end fw-bold"><?php echo formatNumber($entry['balance']); ?></td>
                                    <td><?php echo htmlspecialchars($entry['remarks'] ?? '-'); ?></td>
                                    <td>
                                        <a href="index.php?page=accounting&record_id=<?php echo $recordId; ?>&action=edit&id=<?php echo $entry['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="index.php?page=accounting&record_id=<?php echo $recordId; ?>&action=delete&id=<?php echo $entry['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this entry? This will recalculate all subsequent balances.')">
                                            <i class="bi bi-trash"></i>
                                        </a>
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

<!-- Add Inflow Modal -->
<div class="modal fade" id="addInflowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=accounting&record_id=<?php echo $recordId; ?>&action=create">
                <input type="hidden" name="action" value="create_accounting">
                <input type="hidden" name="record_id" value="<?php echo $recordId; ?>">
                <input type="hidden" name="transaction_type" value="inflow">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down"></i> Add Stock Inflow</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="inflow_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="inflow_date" name="entry_date" required 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="quantity_in" class="form-label">Quantity (m)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="quantity_in" 
                               name="quantity" required placeholder="Enter quantity in meters">
                    </div>
                    <div class="mb-3">
                        <label for="remarks_in" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks_in" name="remarks" rows="2" 
                                 placeholder="Optional notes about this inflow"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg"></i> Record Inflow
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Outflow Modal -->
<div class="modal fade" id="addOutflowModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=accounting&record_id=<?php echo $recordId; ?>&action=create">
                <input type="hidden" name="action" value="create_accounting">
                <input type="hidden" name="record_id" value="<?php echo $recordId; ?>">
                <input type="hidden" name="transaction_type" value="outflow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-up"></i> Add Stock Outflow</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="outflow_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="outflow_date" name="entry_date" required 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="quantity_out" class="form-label">Quantity (m)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01" class="form-control" id="quantity_out" 
                                   name="quantity" required placeholder="Enter quantity in meters">
                            <span class="input-group-text">
                                Max: <?php echo formatNumber($currentRecordSummary ? $currentRecordSummary['balance'] : 0); ?> m
                            </span>
                        </div>
                        <div class="form-text">
                            Current balance: <?php echo formatNumber($currentRecordSummary ? $currentRecordSummary['balance'] : 0); ?> m
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="remarks_out" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks_out" name="remarks" rows="2" 
                                 placeholder="Optional notes about this outflow"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-check-lg"></i> Record Outflow
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Entry Modal -->
<?php if ($action === 'edit' && $editEntry): ?>
<div class="modal fade show" id="editEntryModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=accounting&record_id=<?php echo $recordId; ?>&action=edit&id=<?php echo $editEntry['id']; ?>">
                <input type="hidden" name="action" value="update_accounting">
                <div class="modal-header bg-<?php echo $editEntry['transaction_type'] === 'inflow' ? 'success' : 'danger'; ?> text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-<?php echo $editEntry['transaction_type'] === 'inflow' ? 'box-arrow-in-down' : 'box-arrow-up'; ?>"></i>
                        Edit <?php echo ucfirst($editEntry['transaction_type']); ?> Entry
                    </h5>
                    <a href="index.php?page=accounting&record_id=<?php echo $recordId; ?>" class="btn-close btn-close-white"></a>
                </div>
                <div class="modal-body">
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="edit_entry_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="edit_entry_date" name="entry_date" required 
                               value="<?php echo htmlspecialchars($editEntry['entry_date']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_quantity" class="form-label">Quantity (m)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="edit_quantity" 
                               name="quantity" required 
                               value="<?php echo $editEntry['transaction_type'] === 'inflow' ? $editEntry['quantity_in'] : $editEntry['quantity_out']; ?>">
                        
                        <?php if ($editEntry['transaction_type'] === 'outflow'): ?>
                            <div class="form-text">
                                Current balance after this entry: <?php echo formatNumber($editEntry['balance']); ?> m
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="edit_remarks" name="remarks" rows="3"
                                 placeholder="Add any additional notes about this entry"><?php echo htmlspecialchars($editEntry['remarks'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=accounting&record_id=<?php echo $recordId; ?>" class="btn btn-secondary">
                        <i class="bi bi-x-lg"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-<?php echo $editEntry['transaction_type'] === 'inflow' ? 'success' : 'danger'; ?>">
                        <i class="bi bi-check-lg"></i> Update <?php echo ucfirst($editEntry['transaction_type']); ?>
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