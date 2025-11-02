<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../controllers/records/index.php';

$pageTitle = 'Dashboard';

// Get statistics
$totalRecords = getTotalRecords($recordModel);
$records = getAllRecords($recordModel);

// Calculate statistics
$factoryCount = 0;
$inUseCount = 0;
$soldOutCount = 0;
$totalMeters = 0;
$totalWeight = 0;

foreach ($records as $record) {
    if ($record['sales_status'] === 'factory use') {
        $factoryCount++;
    } elseif ($record['sales_status'] === 'in production') {
        $inUseCount++;
    } elseif ($record['sales_status'] === 'sold out') {
        $soldOutCount++;
    }
    $totalMeters += floatval($record['no_of_meters']);
    $totalWeight += floatval($record['net_weight']);
}

$flash = getFlash();
?>

<?php include __DIR__ . '/../layout/header.php'; ?>

<?php include __DIR__ . '/../layout/sidebar.php'; ?>

<?php include __DIR__ . '/../layout/navbar.php'; ?>

<div class="main-content">
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
        </div>
        
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Card -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title text-uppercase mb-1">No. Of Coils in Stock</h6>
                                <h2 class="mb-0"><?php echo $totalRecords; ?></h2>
                            </div>
                            <i class="bi bi-journal-text fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Quick Actions</h5>
                        <a href="index.php?page=stockbook" class="btn btn-primary me-2">
                            <i class="bi bi-journal-text"></i> View Stock Book
                        </a>
                        <a href="index.php?page=stockbook&action=create" class="btn btn-success">
                            <i class="bi bi-plus-circle"></i> Add New Stock
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Records -->
        <?php if (!empty($records)): ?>
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Recent Stock</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Color</th>
                                        <th>Net Weight (KG)</th>
                                        <th>Status</th>
                                        <th>No. of Meters</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $recentRecords = array_slice($records, 0, 5);
                                    foreach ($recentRecords as $record): 
                                        $statusColors = [
                                            'Available' => 'success',
                                            'Sold' => 'secondary',
                                            'Reserved' => 'warning',
                                            'Pending' => 'info',
                                            'Out of Stock' => 'danger'
                                        ];
                                        $statusColor = $statusColors[$record['sales_status']] ?? 'secondary';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($record['code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($record['color']); ?></td>
                                        <td><?php echo formatNumber($record['net_weight']); ?></td>
                                        <td><span class="badge bg-<?php echo $statusColor; ?>"><?php echo htmlspecialchars($record['sales_status']); ?></span></td>
                                        <td><?php echo formatNumber($record['no_of_meters']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../layout/footer.php'; ?>