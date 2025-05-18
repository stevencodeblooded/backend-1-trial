<?php
/**
 * Admin Dashboard
 * 
 * Main admin dashboard page
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

// Get current user
$user = getCurrentUser($pdo);

// Get statistics
$urlRulesCount = count(getUrlRules($pdo, false));
$cssRulesCount = count(getCssRules($pdo, false));
$cookieRulesCount = count(getCookieRules($pdo, false));
$extensionsCount = count(getExtensions($pdo));

// Page title
$pageTitle = 'Dashboard';

// Include header
include '../admin/partials/header.php';
?>

<div class="container-fluid">
  <div class="row">
    <!-- Include sidebar -->
    <?php include '../admin/partials/sidebar.php'; ?>
    
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <div class="btn-group me-2">
            <a href="url-rules.php" class="btn btn-sm btn-outline-secondary">URL Rules</a>
            <a href="css-rules.php" class="btn btn-sm btn-outline-secondary">CSS Rules</a>
            <a href="cookie-rules.php" class="btn btn-sm btn-outline-secondary">Cookie Rules</a>
          </div>
        </div>
      </div>
      
      <!-- Statistics cards -->
      <div class="row">
        <div class="col-md-3 mb-4">
          <div class="card text-bg-primary h-100">
            <div class="card-body">
              <h5 class="card-title">URL Rules</h5>
              <p class="card-text display-4"><?php echo $urlRulesCount; ?></p>
              <a href="url-rules.php" class="card-link text-white">Manage URL Rules</a>
            </div>
          </div>
        </div>
        
        <div class="col-md-3 mb-4">
          <div class="card text-bg-success h-100">
            <div class="card-body">
              <h5 class="card-title">CSS Rules</h5>
              <p class="card-text display-4"><?php echo $cssRulesCount; ?></p>
              <a href="css-rules.php" class="card-link text-white">Manage CSS Rules</a>
            </div>
          </div>
        </div>
        
        <div class="col-md-3 mb-4">
          <div class="card text-bg-info h-100">
            <div class="card-body">
              <h5 class="card-title">Cookie Rules</h5>
              <p class="card-text display-4"><?php echo $cookieRulesCount; ?></p>
              <a href="cookie-rules.php" class="card-link text-white">Manage Cookie Rules</a>
            </div>
          </div>
        </div>
        
        <div class="col-md-3 mb-4">
          <div class="card text-bg-warning h-100">
            <div class="card-body">
              <h5 class="card-title">Active Extensions</h5>
              <p class="card-text display-4"><?php echo $extensionsCount; ?></p>
              <a href="extensions.php" class="card-link text-white">View Extensions</a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Recent activity -->
      <h2>Recent Activity</h2>
      <div class="table-responsive">
        <table class="table table-striped table-sm">
          <thead>
            <tr>
              <th scope="col">Extension ID</th>
              <th scope="col">Last Sync</th>
              <th scope="col">Version</th>
              <th scope="col">Status</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Get recent extensions
            $extensions = getExtensions($pdo);
            $extensions = array_slice($extensions, 0, 5); // Get the first 5
            
            foreach ($extensions as $extension) {
              echo '<tr>';
              echo '<td>' . htmlspecialchars(substr($extension['extension_id'], 0, 12) . '...') . '</td>';
              echo '<td>' . htmlspecialchars(formatDate($extension['last_sync'])) . '</td>';
              echo '<td>' . htmlspecialchars($extension['version'] ?: 'Unknown') . '</td>';
              echo '<td>';
              if ($extension['is_active']) {
                echo '<span class="badge text-bg-success">Active</span>';
              } else {
                echo '<span class="badge text-bg-danger">Inactive</span>';
              }
              echo '</td>';
              echo '</tr>';
            }
            
            if (empty($extensions)) {
              echo '<tr><td colspan="4" class="text-center">No extensions found</td></tr>';
            }
            ?>
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