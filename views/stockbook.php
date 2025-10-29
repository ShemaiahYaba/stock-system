<!-- /views/stockbook.php -->
<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/records/index.php';
require_once __DIR__ . '/../layout/table-item.php';

$pageTitle = 'Stock Book';

// Handle actions
$action = $_GET['action'] ?? '';
$recordId = isset($_GET['id']) ? intval($_GET['id']) : null;

// Handle delete
handleDeleteRecord($recordModel);

// Handle create
$errors = [];
if ($action === 'create') {
    $errors = handleCreateRecord($recordModel);
}

// Handle update
if ($action === 'edit') {
    $errors = handleUpdateRecord($recordModel);
    $editRecord = getRecordForEdit($recordModel, $recordId);
}

// Get all records
$records = getAllRecords($recordModel);
$flash = getFlash();
?>

<?php include __DIR__ . '/../layout/header.php'; ?>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<?php include __DIR__ . '/../layout/navbar.php'; ?>

<div class="main-content">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-journal-text"></i> Stock Book</h2>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                <i class="bi bi-plus-circle"></i> Add New Record
            </button>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php displayErrors($errors); ?>
        
        <?php if (empty($records)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <h4 class="mt-3">No Records Found</h4>
                    <p class="text-muted">Start by adding your first stock record</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="bi bi-plus-circle"></i> Add Record
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>S/N</th>
                            <th>CODE</th>
                            <th>COLOR</th>
                            <th>NET WEIGHT (KG)</th>
                            <th>GAUGE</th>
                            <th>SALES STATUS</th>
                            <th>NO. OF METERS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $index = 1;
                        foreach ($records as $record): 
                            renderTableItem($record, $index++);
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-3">
                <p class="text-muted">Total Records: <strong><?php echo count($records); ?></strong></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Record Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=stockbook&action=create">
                <input type="hidden" name="action" value="create">
                <div class="modal-header">
                    <h5 class="modal-title" id="addModalLabel">Add New Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="code" class="form-label">Code *</label>
                        <input type="text" class="form-control" id="code" name="code" 
                               value="<?php echo generateCode(); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="color" class="form-label">Color *</label>
                        <select class="form-select" id="color" name="color" required>
                            <option value="">Select Color</option>
                            <?php foreach (COLORS as $color): ?>
                                <option value="<?php echo $color; ?>"><?php echo $color; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="net_weight" class="form-label">Net Weight (KG) *</label>
                        <input type="number" step="0.01" class="form-control" id="net_weight" name="net_weight" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="gauge" class="form-label">Gauge *</label>
                        <select class="form-select" id="gauge" name="gauge" required>
                            <option value="">Select Gauge</option>
                            <?php foreach (GAUGES as $gauge): ?>
                                <option value="<?php echo $gauge; ?>"><?php echo $gauge; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="sales_status" class="form-label">Sales Status *</label>
                        <select class="form-select" id="sales_status" name="sales_status" required>
                            <option value="">Select Status</option>
                            <?php foreach (SALE_STATUSES as $status): ?>
                                <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="no_of_meters" class="form-label">Number of Meters *</label>
                        <input type="number" step="0.01" class="form-control" id="no_of_meters" name="no_of_meters" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Add Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Record Modal -->
<?php if ($action === 'edit' && $editRecord): ?>
<div class="modal fade show" id="editModal" tabindex="-1" style="display: block; background: rgba(0,0,0,0.5);">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=stockbook&action=edit&id=<?php echo $editRecord['id']; ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $editRecord['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Record</h5>
                    <a href="index.php?page=stockbook" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($editRecord['code']); ?>" disabled>
                        <small class="text-muted">Code cannot be changed</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_color" class="form-label">Color *</label>
                        <select class="form-select" id="edit_color" name="color" required>
                            <?php foreach (COLORS as $color): ?>
                                <option value="<?php echo $color; ?>" <?php echo $editRecord['color'] === $color ? 'selected' : ''; ?>>
                                    <?php echo $color; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_net_weight" class="form-label">Net Weight (KG) *</label>
                        <input type="number" step="0.01" class="form-control" id="edit_net_weight" name="net_weight" 
                               value="<?php echo $editRecord['net_weight']; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_gauge" class="form-label">Gauge *</label>
                        <select class="form-select" id="edit_gauge" name="gauge" required>
                            <?php foreach (GAUGES as $gauge): ?>
                                <option value="<?php echo $gauge; ?>" <?php echo $editRecord['gauge'] === $gauge ? 'selected' : ''; ?>>
                                    <?php echo $gauge; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_sales_status" class="form-label">Sales Status *</label>
                        <select class="form-select" id="edit_sales_status" name="sales_status" required>
                            <?php foreach (SALE_STATUSES as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo $editRecord['sales_status'] === $status ? 'selected' : ''; ?>>
                                    <?php echo $status; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_no_of_meters" class="form-label">Number of Meters *</label>
                        <input type="number" step="0.01" class="form-control" id="edit_no_of_meters" name="no_of_meters" 
                               value="<?php echo $editRecord['no_of_meters']; ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="index.php?page=stockbook" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-circle"></i> Update Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- View Modals for each record -->
<?php foreach ($records as $record): ?>
<div class="modal fade" id="viewModal<?php echo $record['id']; ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Code:</th>
                        <td><?php echo htmlspecialchars($record['code']); ?></td>
                    </tr>
                    <tr>
                        <th>Color:</th>
                        <td><?php echo htmlspecialchars($record['color']); ?></td>
                    </tr>
                    <tr>
                        <th>Net Weight (KG):</th>
                        <td><?php echo formatNumber($record['net_weight']); ?></td>
                    </tr>
                    <tr>
                        <th>Gauge:</th>
                        <td><?php echo htmlspecialchars($record['gauge']); ?></td>
                    </tr>
                    <tr>
                        <th>Sales Status:</th>
                        <td>
                            <?php 
                            $statusColors = [
                                'Available' => 'success',
                                'Sold' => 'secondary',
                                'Reserved' => 'warning',
                                'Pending' => 'info',
                                'Out of Stock' => 'danger'
                            ];
                            $statusColor = $statusColors[$record['sales_status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $statusColor; ?>">
                                <?php echo htmlspecialchars($record['sales_status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Number of Meters:</th>
                        <td><?php echo formatNumber($record['no_of_meters']); ?></td>
                    </tr>
                    <tr>
                        <th>Created At:</th>
                        <td><?php echo date('M d, Y H:i', strtotime($record['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <th>Updated At:</th>
                        <td><?php echo date('M d, Y H:i', strtotime($record['updated_at'])); ?></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include __DIR__ . '/../layout/footer.php'; ?>