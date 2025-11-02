<?php
// Include database connection
require_once __DIR__ . '/../config/db.php';

// Initialize database connection if not already done
if (!isset($db)) {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }
}

$currentPage = $_GET['page'] ?? 'dashboard';
?>

<div class="sidebar">
    <div class="px-3 py-2">
        <h5 class="text-white mb-3">
            <i class="bi bi-menu-button-wide"></i> Menu
        </h5>
    </div>
    
    <nav class="nav flex-column">
        <a class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>" href="index.php?page=dashboard">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <div class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?php echo strpos($currentPage, 'stockbook') === 0 ? 'active' : ''; ?>" href="#" id="stockDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-journal-text"></i> Stock
            </a>
            <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="stockDropdown">
                <?php
                // Get stock types from database
                $stockTypes = [];
                $selectedType = $_GET['type'] ?? null;
                
                try {
                    // Check if stock_types table exists
                    $tableCheck = $db->query("SHOW TABLES LIKE 'stock_types'");
                    
                    if ($tableCheck && $tableCheck->num_rows > 0) {
                        $result = $db->query("SELECT id, name FROM stock_types ORDER BY name");
                        
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $active = ($selectedType == $row['id']) ? 'active' : '';
                                echo '<li><a class="dropdown-item ' . $active . '" href="index.php?page=stockbook&type=' . $row['id'] . '">' . htmlspecialchars($row['name']) . '</a></li>';
                            }
                        } else {
                            // No stock types found in the table
                            echo '<li><span class="dropdown-item text-muted">No stock types found</span></li>';
                            
                            // Debug: Check if we can insert default stock types
                            $insertDefault = $db->query("
                                INSERT IGNORE INTO stock_types (name) VALUES 
                                ('Aluminium'), ('Alloy Steel'), ('Kzinc')
                            ") or die($db->error);
                            
                            if ($insertDefault) {
                                // Refresh the page to show the new stock types
                                echo '<script>window.location.reload();</script>';
                            }
                        }
                    } else {
                        echo '<li><span class="dropdown-item text-danger">Stock types table not found</span></li>';
                    }
                } catch (Exception $e) {
                    echo '<li><span class="dropdown-item text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</span></li>';
                }
                ?>
            </ul>
        </div>
    </nav>
</div>