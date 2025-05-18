<?php
/**
 * Settings
 * 
 * Manage application settings
 */

// Include configuration
require_once '../config/config.php';

// Include required files
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/auth-functions.php';
require_once INCLUDES_PATH . '/db-functions.php';

// Initialize database connection
$pdo = initializeApp();

// Require login
requireLogin();

// Require admin role
requireAdmin();

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check form action
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'change_password') {
        // Change password
        $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Validate passwords
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $message = 'All password fields are required';
            $messageType = 'danger';
        } else if ($newPassword !== $confirmPassword) {
            $message = 'New passwords do not match';
            $messageType = 'danger';
        } else if (strlen($newPassword) < 8) {
            $message = 'New password must be at least 8 characters long';
            $messageType = 'danger';
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($currentPassword, $user['password'])) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $result = $stmt->execute([
                    ':password' => $hashedPassword,
                    ':id' => $_SESSION['user_id']
                ]);
                
                if ($result) {
                    $message = 'Password changed successfully';
                    $messageType = 'success';
                } else {
                    $message = 'Failed to change password';
                    $messageType = 'danger';
                }
            } else {
                $message = 'Current password is incorrect';
                $messageType = 'danger';
            }
        }
    }
}

// Get current user
$user = getCurrentUser($pdo);

// Page title
$pageTitle = 'Settings';

// Include header
include '../admin/partials/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <!-- Include sidebar -->
    <?php include '../admin/partials/sidebar.php'; ?>
    
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Settings</h1>
      </div>
      
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
      
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title">Account Information</h3>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="<?php echo ucfirst(htmlspecialchars($user['role'])); ?>" readonly>
              </div>
              
              <div class="mb-3">
                <label class="form-label">Last Login</label>
                <input type="text" class="form-control" value="<?php echo formatDate($user['last_login']); ?>" readonly>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title">Change Password</h3>
            </div>
            <div class="card-body">
              <form method="post" action="">
                <input type="hidden" name="action" value="change_password">
                
                <div class="mb-3">
                  <label for="current_password" class="form-label">Current Password</label>
                  <input type="password" class="form-control" id="current_password" name="current_password" required>
                </div>
                
                <div class="mb-3">
                  <label for="new_password" class="form-label">New Password</label>
                  <input type="password" class="form-control" id="new_password" name="new_password" required>
                  <div class="form-text">Password must be at least 8 characters long</div>
                </div>
                
                <div class="mb-3">
                  <label for="confirm_password" class="form-label">Confirm New Password</label>
                  <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php
// Include footer
include '../admin/partials/footer.php';
?>