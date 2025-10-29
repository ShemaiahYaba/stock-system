<?php
require_once __DIR__ . '/../../config/constants.php';

$pageTitle = 'Login';
$errors = $errors ?? [];
$flash = getFlash();
?>

<?php include __DIR__ . '/../../layout/header.php'; ?>

<div class="auth-container">
    <div class="auth-card">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-box-seam fs-1 text-primary"></i>
                    <h3 class="mt-2"><?php echo APP_NAME; ?></h3>
                    <p class="text-muted">Sign in to your account</p>
                </div>
                
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php displayErrors($errors); ?>
                
                <form method="POST" action="login.php">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-right"></i> Login
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p class="mb-0">Don't have an account? 
                        <a href="register.php" class="text-decoration-none">Register here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layout/footer.php'; ?>