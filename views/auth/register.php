<?php
require_once __DIR__ . '/../../config/constants.php';

$pageTitle = 'Register';
$errors = $errors ?? [];
?>

<?php include __DIR__ . '/../../layout/header.php'; ?>

<div class="auth-container">
    <div class="auth-card">
        <div class="card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <i class="bi bi-person-plus fs-1 text-primary"></i>
                    <h3 class="mt-2">Create Account</h3>
                    <p class="text-muted">Register for <?php echo APP_NAME; ?></p>
                </div>
                
                <?php displayErrors($errors); ?>
                
                <form method="POST" action="register.php">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-person-check"></i> Register
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <p class="mb-0">Already have an account? 
                        <a href="login.php" class="text-decoration-none">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layout/footer.php'; ?>