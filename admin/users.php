<?php
/**
 * Users Management
 * 
 * Manage admin users
 */

// Include configuration
require_once '../config/config.php';

// Include required files
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/auth-functions.php';
require_once INCLUDES_PATH . '/db-functions.php';

// Initialize database connection
$pdo = initializeApp();

// Require login and admin role
requireAdmin();

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check form action
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'add') {
        // Add new user
        $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
        $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $role = isset($_POST['role']) ? sanitizeInput($_POST['role']) : 'editor';
        
        // Validate data
        if (empty($username) || empty($email) || empty($password)) {
            $message = 'Username, email, and password are required';
            $messageType = 'danger';
        } else if ($password !== $confirmPassword) {
            $message = 'Passwords do not match';
            $messageType = 'danger';
        } else if (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters long';
            $messageType = 'danger';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format';
            $messageType = 'danger';
        } else {
            try {
                // Check if username or email already exists
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM users 
                    WHERE username = :username OR email = :email
                ");
                
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email
                ]);
                
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $message = 'Username or email already exists';
                    $messageType = 'danger';
                } else {
                    // Hash password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, password, email, role)
                        VALUES (:username, :password, :email, :role)
                    ");
                    
                    $result = $stmt->execute([
                        ':username' => $username,
                        ':password' => $hashedPassword,
                        ':email' => $email,
                        ':role' => $role
                    ]);
                    
                    if ($result) {
                        $message = 'User added successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to add user';
                        $messageType = 'danger';
                    }
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    } else if ($action === 'edit') {
        // Edit existing user
        $userId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
        $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
        $role = isset($_POST['role']) ? sanitizeInput($_POST['role']) : 'editor';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Check if current user is trying to edit themselves
        $isSelf = ($userId === $_SESSION['user_id']);
        
        // Validate data
        if (empty($username) || empty($email)) {
            $message = 'Username and email are required';
            $messageType = 'danger';
        } else if (!empty($password) && $password !== $confirmPassword) {
            $message = 'Passwords do not match';
            $messageType = 'danger';
        } else if (!empty($password) && strlen($password) < 8) {
            $message = 'Password must be at least 8 characters long';
            $messageType = 'danger';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format';
            $messageType = 'danger';
        } else {
            try {
                // Check if username or email already exists for other users
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM users 
                    WHERE (username = :username OR email = :email) AND id != :id
                ");
                
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':id' => $userId
                ]);
                
                $count = $stmt->fetchColumn();
                
                if ($count > 0) {
                    $message = 'Username or email already exists';
                    $messageType = 'danger';
                } else {
                    // Build query
                    $query = "UPDATE users SET username = :username, email = :email";
                    $params = [
                        ':username' => $username,
                        ':email' => $email,
                        ':id' => $userId
                    ];
                    
                    // Add role parameter if not editing self
                    if (!$isSelf) {
                        $query .= ", role = :role";
                        $params[':role'] = $role;
                    }
                    
                    // Add password parameter if provided
                    if (!empty($password)) {
                        $query .= ", password = :password";
                        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    // Add WHERE clause
                    $query .= " WHERE id = :id";
                    
                    // Update user
                    $stmt = $pdo->prepare($query);
                    $result = $stmt->execute($params);
                    
                    if ($result) {
                        $message = 'User updated successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to update user';
                        $messageType = 'danger';
                    }
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    } else if ($action === 'delete') {
        // Delete user
        $userId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        
        // Check if trying to delete self
        if ($userId === $_SESSION['user_id']) {
            $message = 'You cannot delete your own account';
            $messageType = 'danger';
        } else {
            try {
                // Check if this is the last admin
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM users 
                    WHERE role = 'admin' AND id != :id
                ");
                
                $stmt->execute([':id' => $userId]);
                $adminCount = $stmt->fetchColumn();
                
                // Get user role
                $stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
                $stmt->execute([':id' => $userId]);
                $userRole = $stmt->fetchColumn();
                
                if ($userRole === 'admin' && $adminCount === 0) {
                    $message = 'Cannot delete the last admin user';
                    $messageType = 'danger';
                } else {
                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                    $result = $stmt->execute([':id' => $userId]);
                    
                    if ($result) {
                        $message = 'User deleted successfully';
                        $messageType = 'success';
                    } else {
                        $message = 'Failed to delete user';
                        $messageType = 'danger';
                    }
                }
            } catch (PDOException $e) {
                $message = 'Database error: ' . $e->getMessage();
                $messageType = 'danger';
            }
        }
    }
}

// Get all users
try {
    $stmt = $pdo->query("
        SELECT id, username, email, role, last_login, created_at 
        FROM users 
        ORDER BY created_at DESC
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
    $message = 'Failed to retrieve users: ' . $e->getMessage();
    $messageType = 'danger';
}

// Page title
$pageTitle = 'Users';

// Include header
include '../admin/partials/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <!-- Include sidebar -->
    <?php include '../admin/partials/sidebar.php'; ?>
    
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Users</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-plus-lg"></i> Add User
          </button>
        </div>
      </div>
      
      <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
          <?php echo $message; ?>
        </div>
      <?php endif; ?>
      
      <!-- Users table -->
      <div class="table-responsive">
        <table class="table table-striped table-sm" id="usersTable">
          <thead>
            <tr>
              <th scope="col">ID</th>
              <th scope="col">Username</th>
              <th scope="col">Email</th>
              <th scope="col">Role</th>
              <th scope="col">Last Login</th>
              <th scope="col">Created</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
                <td>
                  <?php if ($user['role'] === 'admin'): ?>
                    <span class="badge text-bg-primary">Admin</span>
                  <?php else: ?>
                    <span class="badge text-bg-secondary">Editor</span>
                  <?php endif; ?>
                </td>
                <td><?php echo $user['last_login'] ? formatDate($user['last_login']) : 'Never'; ?></td>
                <td><?php echo formatDate($user['created_at']); ?></td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-primary edit-user" 
                    data-id="<?php echo $user['id']; ?>"
                    data-username="<?php echo htmlspecialchars($user['username']); ?>"
                    data-email="<?php echo htmlspecialchars($user['email']); ?>"
                    data-role="<?php echo $user['role']; ?>"
                  >
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  
                  <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-user" data-id="<?php echo $user['id']; ?>">
                      <i class="bi bi-trash"></i>
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
            
            <?php if (empty($users)): ?>
              <tr>
                <td colspan="7" class="text-center">No users found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addUserModalLabel">Add User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
          
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          
          <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role" name="role" required>
              <option value="editor">Editor</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="form-text">Password must be at least 8 characters long</div>
          </div>
          
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Add User</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="id" id="edit_id">
          
          <div class="mb-3">
            <label for="edit_username" class="form-label">Username</label>
            <input type="text" class="form-control" id="edit_username" name="username" required>
          </div>
          
          <div class="mb-3">
            <label for="edit_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="edit_email" name="email" required>
          </div>
          
          <div class="mb-3" id="edit_role_field">
            <label for="edit_role" class="form-label">Role</label>
            <select class="form-select" id="edit_role" name="role">
              <option value="editor">Editor</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label for="edit_password" class="form-label">Password</label>
            <input type="password" class="form-control" id="edit_password" name="password">
            <div class="form-text">Leave blank to keep current password</div>
          </div>
          
          <div class="mb-3">
            <label for="edit_confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="edit_confirm_password" name="confirm_password">
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

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteUserModalLabel">Delete User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="post" action="">
        <div class="modal-body">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" id="delete_id">
          
          <p>Are you sure you want to delete this user? This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Custom JavaScript for users page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Edit user
  const editButtons = document.querySelectorAll('.edit-user');
  const currentUserId = <?php echo $_SESSION['user_id']; ?>;
  
  editButtons.forEach(button => {
    button.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const username = this.getAttribute('data-username');
      const email = this.getAttribute('data-email');
      const role = this.getAttribute('data-role');
      const isSelf = (parseInt(id) === currentUserId);
      
      document.getElementById('edit_id').value = id;
      document.getElementById('edit_username').value = username;
      document.getElementById('edit_email').value = email;
      document.getElementById('edit_role').value = role;
      
      // Hide role field if editing self
      const roleField = document.getElementById('edit_role_field');
      if (isSelf) {
        roleField.style.display = 'none';
      } else {
        roleField.style.display = 'block';
      }
      
      // Clear password fields
      document.getElementById('edit_password').value = '';
      document.getElementById('edit_confirm_password').value = '';
      
      // Show modal
      const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
      editModal.show();
    });
  });
  
  // Delete user
  const deleteButtons = document.querySelectorAll('.delete-user');
  
  deleteButtons.forEach(button => {
    button.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      
      document.getElementById('delete_id').value = id;
      
      // Show modal
      const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
      deleteModal.show();
    });
  });
});
</script>

<?php
// Include footer
include '../admin/partials/footer.php';
?>