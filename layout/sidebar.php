<!-- /layout/sidebar.php -->
<?php
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
        <a class="nav-link <?php echo $currentPage === 'stockbook' ? 'active' : ''; ?>" href="index.php?page=stockbook">
            <i class="bi bi-journal-text"></i> Stock Book
        </a>
        <!-- ✅ NEW MENU ITEM -->
        <a class="nav-link <?php echo $currentPage === 'accounting' ? 'active' : ''; ?>" href="index.php?page=accounting">
            <i class="bi bi-calculator"></i> Stock Accounting
        </a>
    </nav>
</div>