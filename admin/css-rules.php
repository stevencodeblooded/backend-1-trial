<?php
/**
 * CSS Rules Management
 * 
 * Manage CSS modification rules
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
    
    if ($action === 'add') {
        // Add new rule
        $ruleData = [
            'url_pattern' => isset($_POST['url_pattern']) ? sanitizeInput($_POST['url_pattern']) : '',
            'selector' => isset($_POST['selector']) ? sanitizeInput($_POST['selector']) : '',
            'action' => isset($_POST['action_type']) ? sanitizeInput($_POST['action_type']) : '',
            'css_properties' => isset($_POST['css_properties']) ? $_POST['css_properties'] : null,
            'description' => isset($_POST['description']) ? sanitizeInput($_POST['description']) : '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Validate data
        if (empty($ruleData['url_pattern']) || empty($ruleData['selector']) || empty($ruleData['action'])) {
            $message = 'URL pattern, selector, and action are required';
            $messageType = 'danger';
        } else if ($ruleData['action'] === 'modify' && empty($ruleData['css_properties'])) {
            $message = 'CSS properties are required for modify action';
            $messageType = 'danger';
        } else {
            // Add rule
            $ruleId = addCssRule($ruleData, $pdo);
            
            if ($ruleId) {
                $message = 'CSS rule added successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to add CSS rule';
                $messageType = 'danger';
            }
        }
    } else if ($action === 'edit') {
        // Edit existing rule
        $ruleId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        $ruleData = [
            'url_pattern' => isset($_POST['url_pattern']) ? sanitizeInput($_POST['url_pattern']) : '',
            'selector' => isset($_POST['selector']) ? sanitizeInput($_POST['selector']) : '',
            'action' => isset($_POST['action_type']) ? sanitizeInput($_POST['action_type']) : '',
            'css_properties' => isset($_POST['css_properties']) ? $_POST['css_properties'] : null,
            'description' => isset($_POST['description']) ? sanitizeInput($_POST['description']) : '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        // Validate data
        if (empty($ruleData['url_pattern']) || empty($ruleData['selector']) || empty($ruleData['action'])) {
            $message = 'URL pattern, selector, and action are required';
            $messageType = 'danger';
        } else if ($ruleData['action'] === 'modify' && empty($ruleData['css_properties'])) {
            $message = 'CSS properties are required for modify action';
            $messageType = 'danger';
        } else {
            // Update rule
            $result = updateCssRule($ruleId, $ruleData, $pdo);
            
            if ($result) {
                $message = 'CSS rule updated successfully';
                $messageType = 'success';
            } else {
                $message = 'Failed to update CSS rule';
                $messageType = 'danger';
            }
        }
    } else if ($action === 'delete') {
        // Delete rule
        $ruleId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        // Delete rule
        $result = deleteCssRule($ruleId, $pdo);
        
        if ($result) {
            $message = 'CSS rule deleted successfully';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete CSS rule';
            $messageType = 'danger';
        }
    }
}

// Get all CSS rules
$rules = getCssRules($pdo, false);

// Page title
$pageTitle = 'CSS Rules';

// Include header
include '../admin/partials/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <!-- Include sidebar -->
    <?php include '../admin/partials/sidebar.php'; ?>
    
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">CSS Rules</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addRuleModal">
            <i class="bi bi-plus-lg"></i> Add Rule
          </button>
        </div>
      </div>
      
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
      
      <!-- CSS Rules table -->
      <div class="table-responsive">
        <table class="table table-striped table-sm" id="rulesTable">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">URL Pattern</th>
              <th scope="col">Selector</th>
              <th scope="col">Action</th>
              <th scope="col">Description</th>
              <th scope="col">Status</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rules as $rule): ?>
              <tr>
                <td><?php echo $rule['id']; ?></td>
                <td><?php echo htmlspecialchars($rule['url_pattern']); ?></td>
                <td><?php echo htmlspecialchars($rule['selector']); ?></td>
                <td>
                  <?php if ($rule['action'] === 'hide'): ?>
                    <span class="badge text-bg-primary">Hide</span>
                  <?php elseif ($rule['action'] === 'modify'): ?>
                    <span class="badge text-bg-warning">Modify</span>
                  <?php else: ?>
                    <span class="badge text-bg-danger">Remove</span>
                  <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($rule['description'] ?: '-'); ?></td>
                <td>
                  <?php if ($rule['is_active']): ?>
                    <span class="badge text-bg-success">Active</span>
                  <?php else: ?>
                    <span class="badge text-bg-danger">Inactive</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-primary edit-rule" 
                    data-id="<?php echo $rule['id']; ?>"
                    data-url-pattern="<?php echo htmlspecialchars($rule['url_pattern']); ?>"
                    data-selector="<?php echo htmlspecialchars($rule['selector']); ?>"
                    data-action="<?php echo $rule['action']; ?>"
                    data-css-properties="<?php echo htmlspecialchars($rule['css_properties'] ?: ''); ?>"
                    data-description="<?php echo htmlspecialchars($rule['description'] ?: ''); ?>"
                    data-active="<?php echo $rule['is_active']; ?>"
                  >
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <button type="button" class="btn btn-sm btn-outline-danger delete-rule" data-id="<?php echo $rule['id']; ?>">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
            
            <?php if (empty($rules)): ?>
              <tr>
                <td colspan="7" class="text-center">No CSS rules found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>

<!-- Add Rule Modal -->
<div class="modal fade" id="addRuleModal" tabindex="-1" aria-labelledby="addRuleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addRuleModalLabel">Add CSS Rule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          
          <div class="mb-3">
            <label for="url_pattern" class="form-label">URL Pattern</label>
            <input type="text" class="form-control" id="url_pattern" name="url_pattern" required>
            <div class="form-text">Supports wildcards (*) and regular expressions</div>
          </div>
          
          <div class="mb-3">
            <label for="selector" class="form-label">CSS Selector</label>
            <input type="text" class="form-control" id="selector" name="selector" required>
            <div class="form-text">E.g., #header, .menu-item, div.sidebar</div>
          </div>
          
          <div class="mb-3">
            <label for="action_type" class="form-label">Action</label>
            <select class="form-select" id="action_type" name="action_type" required>
              <option value="">Select action</option>
              <option value="hide">Hide</option>
              <option value="modify">Modify</option>
              <option value="remove">Remove</option>
            </select>
          </div>
          
          <div class="mb-3" id="cssPropertiesField">
            <label for="css_properties" class="form-label">CSS Properties</label>
            <textarea class="form-control" id="css_properties" name="css_properties" rows="3"></textarea>
            <div class="form-text">Required for modify action. Format: {"color":"red","display":"none"}</div>
          </div>
          
          <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
          </div>
          
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
            <label class="form-check-label" for="is_active">
              Active
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Rule</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit Rule Modal -->
<div class="modal fade" id="editRuleModal" tabindex="-1" aria-labelledby="editRuleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editRuleModalLabel">Edit CSS Rule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit_id">
          
          <div class="mb-3">
            <label for="edit_url_pattern" class="form-label">URL Pattern</label>
            <input type="text" class="form-control" id="edit_url_pattern" name="url_pattern" required>
            <div class="form-text">Supports wildcards (*) and regular expressions</div>
          </div>
          
          <div class="mb-3">
            <label for="edit_selector" class="form-label">CSS Selector</label>
            <input type="text" class="form-control" id="edit_selector" name="selector" required>
            <div class="form-text">E.g., #header, .menu-item, div.sidebar</div>
          </div>
          
          <div class="mb-3">
            <label for="edit_action_type" class="form-label">Action</label>
            <select class="form-select" id="edit_action_type" name="action_type" required>
              <option value="">Select action</option>
              <option value="hide">Hide</option>
              <option value="modify">Modify</option>
              <option value="remove">Remove</option>
            </select>
          </div>
          
          <div class="mb-3" id="editCssPropertiesField">
            <label for="edit_css_properties" class="form-label">CSS Properties</label>
            <textarea class="form-control" id="edit_css_properties" name="css_properties" rows="3"></textarea>
            <div class="form-text">Required for modify action. Format: {"color":"red","display":"none"}</div>
          </div>
          
          <div class="mb-3">
            <label for="edit_description" class="form-label">Description</label>
            <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
          </div>
          
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active">
            <label class="form-check-label" for="edit_is_active">
              Active
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Delete Rule Modal -->
<div class="modal fade" id="deleteRuleModal" tabindex="-1" aria-labelledby="deleteRuleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteRuleModalLabel">Delete CSS Rule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" id="delete_id">
          
          <p>Are you sure you want to delete this CSS rule? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Custom JavaScript for CSS rules page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Show/hide CSS properties field based on action
  const actionSelect = document.getElementById('action_type');
  const cssPropertiesField = document.getElementById('cssPropertiesField');
  
  actionSelect.addEventListener('change', function() {
    if (this.value === 'modify') {
      cssPropertiesField.style.display = 'block';
    } else {
      cssPropertiesField.style.display = 'none';
    }
  });
  
  // Edit rule
  const editButtons = document.querySelectorAll('.edit-rule');
  
  editButtons.forEach(button => {
    button.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const urlPattern = this.getAttribute('data-url-pattern');
      const selector = this.getAttribute('data-selector');
      const action = this.getAttribute('data-action');
      const cssProperties = this.getAttribute('data-css-properties');
      const description = this.getAttribute('data-description');
      const active = this.getAttribute('data-active') === '1';
      
      document.getElementById('edit_id').value = id;
      document.getElementById('edit_url_pattern').value = urlPattern;
      document.getElementById('edit_selector').value = selector;
      document.getElementById('edit_action_type').value = action;
      document.getElementById('edit_css_properties').value = cssProperties;
      document.getElementById('edit_description').value = description;
      document.getElementById('edit_is_active').checked = active;
      
      // Show/hide CSS properties field
      const editCssPropertiesField = document.getElementById('editCssPropertiesField');
      
      if (action === 'modify') {
        editCssPropertiesField.style.display = 'block';
      } else {
        editCssPropertiesField.style.display = 'none';
      }
      
      // Show modal
      const editModal = new bootstrap.Modal(document.getElementById('editRuleModal'));
      editModal.show();
    });
  });
  
  // Show/hide edit CSS properties field based on action
  const editActionSelect = document.getElementById('edit_action_type');
  const editCssPropertiesField = document.getElementById('editCssPropertiesField');
  
  editActionSelect.addEventListener('change', function() {
    if (this.value === 'modify') {
      editCssPropertiesField.style.display = 'block';
    } else {
      editCssPropertiesField.style.display = 'none';
    }
  });
  
  // Delete rule
  const deleteButtons = document.querySelectorAll('.delete-rule');
  
  deleteButtons.forEach(button => {
    button.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      
      document.getElementById('delete_id').value = id;
      
      // Show modal
      const deleteModal = new bootstrap.Modal(document.getElementById('deleteRuleModal'));
      deleteModal.show();
    });
  });
});
</script>

<?php
// Include footer
include '../admin/partials/footer.php';
?>