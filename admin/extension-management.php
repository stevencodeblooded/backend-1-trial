<?php
/**
 * Extension Management Admin Page
 * 
 * Manage browser extensions from backend
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

// Process form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'toggle_extension') {
        // Toggle extension status
        $extensionId = isset($_POST['extension_id']) ? sanitizeInput($_POST['extension_id']) : '';
        $isEnabled = isset($_POST['is_enabled']) ? (int)$_POST['is_enabled'] : 0;
        
        $result = updateManagedExtensionStatus($extensionId, $isEnabled, $pdo, 'admin');
        
        if ($result) {
            $status = $isEnabled ? 'enabled' : 'disabled';
            $message = "Extension $status successfully";
            $messageType = 'success';
        } else {
            $message = 'Failed to update extension status';
            $messageType = 'danger';
        }
    } else if ($action === 'set_backend_control') {
        // Set backend control
        $extensionId = isset($_POST['extension_id']) ? sanitizeInput($_POST['extension_id']) : '';
        $backendControlled = isset($_POST['backend_controlled']) ? (int)$_POST['backend_controlled'] : 0;
        
        $result = setExtensionBackendControl($extensionId, $backendControlled, $pdo);
        
        if ($result) {
            $controlStatus = $backendControlled ? 'enabled' : 'disabled';
            $message = "Backend control $controlStatus successfully";
            $messageType = 'success';
        } else {
            $message = 'Failed to update backend control status';
            $messageType = 'danger';
        }
    } else if ($action === 'delete_extension') {
        // Delete extension
        $extensionId = isset($_POST['extension_id']) ? sanitizeInput($_POST['extension_id']) : '';
        
        $result = deleteManagedExtension($extensionId, $pdo);
        
        if ($result) {
            $message = 'Extension deleted successfully';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete extension';
            $messageType = 'danger';
        }
    } else if ($action === 'update_policy') {
        // Update extension policy
        $policyName = isset($_POST['policy_name']) ? sanitizeInput($_POST['policy_name']) : '';
        $policyValue = [];
        
        // Parse policy settings based on policy name
        switch ($policyName) {
            case 'auto_disable_new_extensions':
                $policyValue = [
                    'enabled' => isset($_POST['auto_disable_enabled']) ? true : false,
                    'excluded_types' => isset($_POST['excluded_types']) ? explode(',', $_POST['excluded_types']) : []
                ];
                break;
                
            case 'block_extensions_page_access':
                $policyValue = [
                    'enabled' => isset($_POST['block_access_enabled']) ? true : false,
                    'allow_dev_mode' => isset($_POST['allow_dev_mode']) ? true : false
                ];
                break;
                
            case 'auto_logout_on_disable':
                $policyValue = [
                    'enabled' => isset($_POST['auto_logout_enabled']) ? true : false,
                    'clear_cookies' => isset($_POST['clear_cookies']) ? true : false,
                    'clear_storage' => isset($_POST['clear_storage']) ? true : false
                ];
                break;
        }
        
        if (!empty($policyName) && !empty($policyValue)) {
            $result = updateExtensionPolicy($policyName, $policyValue, $pdo);
            
            if ($result) {
                $message = 'Policy updated successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to update policy';
                $messageType = 'danger';
            }
        } else {
            $message = 'Invalid policy data';
            $messageType = 'danger';
        }
    }
}

// Get all managed extensions
$extensions = getManagedExtensions($pdo, false);

// Get extension management statistics
$stats = getExtensionManagementStats($pdo);

// Get extension policies
$policies = [];
$policyNames = ['auto_disable_new_extensions', 'block_extensions_page_access', 'auto_logout_on_disable'];
foreach ($policyNames as $policyName) {
    $policy = getExtensionPolicy($policyName, $pdo);
    if ($policy) {
        $policies[$policyName] = $policy;
    }
}

// Page title
$pageTitle = 'Extension Management';

// Include header
include '../admin/partials/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <!-- Include sidebar -->
    <?php include '../admin/partials/sidebar.php'; ?>
    
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Extension Management</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#settingsModal">
            <i class="bi bi-gear"></i> Settings
          </button>
          <button type="button" class="btn btn-sm btn-outline-info ms-2" onclick="refreshExtensions()">
            <i class="bi bi-arrow-clockwise"></i> Refresh
          </button>
        </div>
      </div>
      
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
      
      <!-- Statistics cards -->
      <div class="row mb-4">
        <div class="col-sm-6 col-xl-3">
          <div class="card text-white bg-primary">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-semibold">Total Managed</div>
                  <div class="h4"><?php echo $stats['total_managed']; ?></div>
                </div>
                <div class="align-self-center">
                  <i class="bi bi-collection" style="font-size: 2rem;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-sm-6 col-xl-3">
          <div class="card text-white bg-success">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-semibold">Enabled</div>
                  <div class="h4"><?php echo $stats['total_enabled']; ?></div>
                </div>
                <div class="align-self-center">
                  <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-sm-6 col-xl-3">
          <div class="card text-white bg-danger">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-semibold">Disabled</div>
                  <div class="h4"><?php echo $stats['total_disabled']; ?></div>
                </div>
                <div class="align-self-center">
                  <i class="bi bi-x-circle" style="font-size: 2rem;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-sm-6 col-xl-3">
          <div class="card text-white bg-info">
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div>
                  <div class="fw-semibold">Recent Actions</div>
                  <div class="h4"><?php echo $stats['recent_actions_24h']; ?></div>
                  <small>Last 24h</small>
                </div>
                <div class="align-self-center">
                  <i class="bi bi-activity" style="font-size: 2rem;"></i>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Extensions table -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Managed Extensions</h3>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover" id="extensionsTable">
              <thead>
                <tr>
                  <th>Extension Name</th>
                  <th>Version</th>
                  <th>Install Type</th>
                  <th>Backend Control</th>
                  <th>Status</th>
                  <th>Last Sync</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($extensions as $extension): ?>
                  <tr>
                    <td>
                      <div class="fw-semibold"><?php echo htmlspecialchars($extension['extension_name']); ?></div>
                      <small class="text-muted"><?php echo htmlspecialchars(substr($extension['extension_id'], 0, 20) . '...'); ?></small>
                    </td>
                    <td><?php echo htmlspecialchars($extension['version'] ?: 'Unknown'); ?></td>
                    <td>
                      <span class="badge bg-secondary"><?php echo ucfirst(htmlspecialchars($extension['install_type'])); ?></span>
                    </td>
                    <td>
                      <?php if ($extension['backend_controlled']): ?>
                        <span class="badge bg-success">Controlled</span>
                      <?php else: ?>
                        <span class="badge bg-warning">Not Controlled</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($extension['is_enabled']): ?>
                        <span class="badge bg-success">Enabled</span>
                      <?php else: ?>
                        <span class="badge bg-danger">Disabled</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo formatDate($extension['last_sync']); ?></td>
                    <td>
                      <!-- Toggle Status -->
                      <form method="post" action="" class="d-inline me-1">
                        <input type="hidden" name="action" value="toggle_extension">
                        <input type="hidden" name="extension_id" value="<?php echo htmlspecialchars($extension['extension_id']); ?>">
                        <input type="hidden" name="is_enabled" value="<?php echo $extension['is_enabled'] ? 0 : 1; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-<?php echo $extension['is_enabled'] ? 'danger' : 'success'; ?>" 
                                title="<?php echo $extension['is_enabled'] ? 'Disable' : 'Enable'; ?> Extension">
                          <i class="bi bi-<?php echo $extension['is_enabled'] ? 'x-circle' : 'check-circle'; ?>"></i>
                        </button>
                      </form>
                      
                      <!-- Toggle Backend Control -->
                      <form method="post" action="" class="d-inline me-1">
                        <input type="hidden" name="action" value="set_backend_control">
                        <input type="hidden" name="extension_id" value="<?php echo htmlspecialchars($extension['extension_id']); ?>">
                        <input type="hidden" name="backend_controlled" value="<?php echo $extension['backend_controlled'] ? 0 : 1; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-info" 
                                title="<?php echo $extension['backend_controlled'] ? 'Remove' : 'Add'; ?> Backend Control">
                          <i class="bi bi-<?php echo $extension['backend_controlled'] ? 'unlock' : 'lock'; ?>"></i>
                        </button>
                      </form>
                      
                      <!-- Delete Extension -->
                      <button type="button" class="btn btn-sm btn-outline-danger delete-extension" 
                              data-extension-id="<?php echo htmlspecialchars($extension['extension_id']); ?>"
                              data-extension-name="<?php echo htmlspecialchars($extension['extension_name']); ?>">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
                
                <?php if (empty($extensions)): ?>
                  <tr>
                    <td colspan="7" class="text-center">No extensions found</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="settingsModalLabel">Extension Management Settings</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="accordion" id="policiesAccordion">
          <!-- Auto Disable New Extensions -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="autoDisableHeading">
              <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#autoDisableCollapse" aria-expanded="true" aria-controls="autoDisableCollapse">
                Auto Disable New Extensions
              </button>
            </h2>
            <div id="autoDisableCollapse" class="accordion-collapse collapse show" aria-labelledby="autoDisableHeading" data-bs-parent="#policiesAccordion">
              <div class="accordion-body">
                <form method="post" action="">
                  <input type="hidden" name="action" value="update_policy">
                  <input type="hidden" name="policy_name" value="auto_disable_new_extensions">
                  
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="auto_disable_enabled" name="auto_disable_enabled" 
                           <?php echo (isset($policies['auto_disable_new_extensions']) && $policies['auto_disable_new_extensions']['policy_value']['enabled']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="auto_disable_enabled">
                      Automatically disable newly installed extensions
                    </label>
                  </div>
                  
                  <div class="mb-3 mt-3">
                    <label for="excluded_types" class="form-label">Excluded Types (comma-separated)</label>
                    <input type="text" class="form-control" id="excluded_types" name="excluded_types" 
                           value="<?php echo isset($policies['auto_disable_new_extensions']) ? implode(',', $policies['auto_disable_new_extensions']['policy_value']['excluded_types'] ?? []) : 'theme'; ?>"
                           placeholder="theme,app">
                    <div class="form-text">Extension types that should not be auto-disabled</div>
                  </div>
                  
                  <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
              </div>
            </div>
          </div>
          
          <!-- Block Extensions Page Access -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="blockAccessHeading">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#blockAccessCollapse" aria-expanded="false" aria-controls="blockAccessCollapse">
                Block Extensions Page Access
              </button>
            </h2>
            <div id="blockAccessCollapse" class="accordion-collapse collapse" aria-labelledby="blockAccessHeading" data-bs-parent="#policiesAccordion">
              <div class="accordion-body">
                <form method="post" action="">
                  <input type="hidden" name="action" value="update_policy">
                  <input type="hidden" name="policy_name" value="block_extensions_page_access">
                  
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="block_access_enabled" name="block_access_enabled"
                           <?php echo (isset($policies['block_extensions_page_access']) && $policies['block_extensions_page_access']['policy_value']['enabled']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="block_access_enabled">
                      Block access to chrome://extensions page
                    </label>
                  </div>
                  
                  <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="allow_dev_mode" name="allow_dev_mode"
                           <?php echo (isset($policies['block_extensions_page_access']) && $policies['block_extensions_page_access']['policy_value']['allow_dev_mode']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="allow_dev_mode">
                      Allow access in developer mode
                    </label>
                  </div>
                  
                  <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
                </form>
              </div>
            </div>
          </div>
          
          <!-- Auto Logout on Disable -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="autoLogoutHeading">
              <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#autoLogoutCollapse" aria-expanded="false" aria-controls="autoLogoutCollapse">
                Auto Logout on Disable
              </button>
            </h2>
            <div id="autoLogoutCollapse" class="accordion-collapse collapse" aria-labelledby="autoLogoutHeading" data-bs-parent="#policiesAccordion">
              <div class="accordion-body">
                <form method="post" action="">
                  <input type="hidden" name="action" value="update_policy">
                  <input type="hidden" name="policy_name" value="auto_logout_on_disable">
                  
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="auto_logout_enabled" name="auto_logout_enabled"
                           <?php echo (isset($policies['auto_logout_on_disable']) && $policies['auto_logout_on_disable']['policy_value']['enabled']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="auto_logout_enabled">
                      Auto logout when extension is disabled
                    </label>
                  </div>
                  
                  <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="clear_cookies" name="clear_cookies"
                           <?php echo (isset($policies['auto_logout_on_disable']) && $policies['auto_logout_on_disable']['policy_value']['clear_cookies']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="clear_cookies">
                      Clear all cookies
                    </label>
                  </div>
                  
                  <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="clear_storage" name="clear_storage"
                           <?php echo (isset($policies['auto_logout_on_disable']) && $policies['auto_logout_on_disable']['policy_value']['clear_storage']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="clear_storage">
                      Clear local storage
                    </label>
                  </div>
                  
                  <button type="submit" class="btn btn-primary mt-3">Save Settings</button>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Extension Modal -->
<div class="modal fade" id="deleteExtensionModal" tabindex="-1" aria-labelledby="deleteExtensionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteExtensionModalLabel">Delete Extension</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <input type="hidden" name="action" value="delete_extension">
          <input type="hidden" name="extension_id" id="delete_extension_id">
          
          <p>Are you sure you want to delete the extension <strong id="delete_extension_name"></strong>?</p>
          <p class="text-muted">This will remove it from backend management but will not uninstall it from the user's browser.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Custom JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Delete extension functionality
  const deleteButtons = document.querySelectorAll('.delete-extension');
  
  deleteButtons.forEach(button => {
    button.addEventListener('click', function() {
      const extensionId = this.getAttribute('data-extension-id');
      const extensionName = this.getAttribute('data-extension-name');
      
      document.getElementById('delete_extension_id').value = extensionId;
      document.getElementById('delete_extension_name').textContent = extensionName;
      
      const deleteModal = new bootstrap.Modal(document.getElementById('deleteExtensionModal'));
      deleteModal.show();
    });
  });
});

// Refresh extensions function
function refreshExtensions() {
  // Show loading indicator
  const button = document.querySelector('[onclick="refreshExtensions()"]');
  const originalText = button.innerHTML;
  button.innerHTML = '<i class="bi bi-arrow-clockwise spinner-border spinner-border-sm"></i> Refreshing...';
  button.disabled = true;
  
  // Reload the page after a short delay
  setTimeout(() => {
    window.location.reload();
  }, 1000);
}
</script>

<?php
// Include footer
include '../admin/partials/footer.php';
?>