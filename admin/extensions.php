<?php
/**
 * Extensions Management
 * 
 * View and manage extension instances
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

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check form action
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'toggle_status') {
        // Toggle extension status
        $extensionId = isset($_POST['extension_id']) ? sanitizeInput($_POST['extension_id']) : '';
        $isActive = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
        
        // Update extension status
        $result = updateExtensionStatus($extensionId, $isActive, $pdo);
        
        if ($result) {
            $message = 'Extension status updated successfully';
            $messageType = 'success';
        } else {
            $message = 'Failed to update extension status';
            $messageType = 'danger';
        }
    }
}

// Get all extensions
$extensions = getExtensions($pdo);

// Page title
$pageTitle = 'Extensions';

// Include header
include '../admin/partials/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <!-- Include sidebar -->
    <?php include '../admin/partials/sidebar.php'; ?>
    
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Extensions</h1>
      </div>
      
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
      
      <!-- Extensions table -->
      <div class="table-responsive">
        <table class="table table-striped table-sm" id="extensionsTable">
          <thead>
            <tr>
              <th scope="col">Extension ID</th>
              <th scope="col">Name</th>
              <th scope="col">Version</th>
              <th scope="col">Last Sync</th>
              <th scope="col">Status</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($extensions as $extension): ?>
              <tr>
                <td><?php echo htmlspecialchars($extension['extension_id']); ?></td>
                <td><?php echo htmlspecialchars($extension['name'] ?: 'SemrushToolz Ultimate'); ?></td>
                <td><?php echo htmlspecialchars($extension['version'] ?: 'Unknown'); ?></td>
                <td><?php echo formatDate($extension['last_sync']); ?></td>
                <td>
                  <?php if ($extension['is_active']): ?>
                    <span class="badge text-bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge text-bg-danger">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <form method="post" action="" class="d-inline">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="extension_id" value="<?php echo htmlspecialchars($extension['extension_id']); ?>">
                    <input type="hidden" name="is_active" value="<?php echo $extension['is_active'] ? 0 : 1; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-<?php echo $extension['is_active'] ? 'danger' : 'success'; ?>">
                      <?php echo $extension['is_active'] ? 'Deactivate' : 'Activate'; ?>
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            
            <?php if (empty($extensions)): ?>
              <tr>
                <td colspan="6" class="text-center">No extensions found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>

<?php
// Include footer
include '../admin/partials/footer.php';
?>