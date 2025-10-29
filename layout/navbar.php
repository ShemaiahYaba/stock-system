<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">
            <i class="bi bi-box-seam"></i> <?php echo APP_NAME; ?>
        </span>
        
        <div class="d-flex align-items-center">
            <span class="text-white me-3">
                <i class="bi bi-person-circle"></i> 
                <?php echo htmlspecialchars($_SESSION[SESSION_USER_NAME] ?? 'User'); ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
</nav>