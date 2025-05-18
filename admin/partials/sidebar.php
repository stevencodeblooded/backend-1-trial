<?php
/**
 * Admin Sidebar with Extension Management
 * 
 * Enhanced sidebar template for admin pages
 */
?>
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
  <div class="position-sticky pt-3">
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link <?php echo ($pageTitle === 'Dashboard') ? 'active' : ''; ?>" href="/semrush-backend/admin/">
          <i class="bi bi-speedometer2 me-1"></i>
          Dashboard
        </a>
      </li>
      
      <li class="nav-item">
        <a class="nav-link <?php echo ($pageTitle === 'URL Rules') ? 'active' : ''; ?>" href="/semrush-backend/admin/url-rules.php">
          <i class="bi bi-link-45deg me-1"></i>
          URL Rules
        </a>
      </li>
      
      <li class="nav-item">
        <a class="nav-link <?php echo ($pageTitle === 'CSS Rules') ? 'active' : ''; ?>" href="/semrush-backend/admin/css-rules.php">
          <i class="bi bi-code-slash me-1"></i>
          CSS Rules
        </a>
      </li>
      
      <li class="nav-item">
        <a class="nav-link <?php echo ($pageTitle === 'Cookie Rules') ? 'active' : ''; ?>" href="/semrush-backend/admin/cookie-rules.php">
          <svg class="me-1" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cookie" viewBox="0 0 16 16">
            <path d="M6 7.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m4.5.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m-.5 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
            <path d="M8 0a7.96 7.96 0 0 0-4.075 1.114q-.245.102-.437.28A8 8 0 1 0 8 0m3.25 14.201a1.5 1.5 0 0 0-2.13.71A7 7 0 0 1 8 15a6.97 6.97 0 0 1-3.845-1.15 1.5 1.5 0 1 0-2.005-2.005A6.97 6.97 0 0 1 1 8c0-1.953.8-3.719 2.09-4.989a1.5 1.5 0 1 0 2.469-1.574A7 7 0 0 1 8 1c1.42 0 2.742.423 3.845 1.15a1.5 1.5 0 1 0 2.005 2.005A6.97 6.97 0 0 1 15 8c0 .596-.074 1.174-.214 1.727a1.5 1.5 0 1 0-1.025 2.25 7 7 0 0 1-2.51 2.224Z"/>
          </svg>
          Cookie Rules
        </a>
      </li>
      
      <!-- NEW: Extension Management -->
      <li class="nav-item">
        <a class="nav-link <?php echo ($pageTitle === 'Extension Management') ? 'active' : ''; ?>" href="/semrush-backend/admin/extension-management.php">
          <i class="bi bi-puzzle-fill me-1"></i>
          Extension Management
        </a>
      </li>
      
      <li class="nav-item">
        <a class="nav-link <?php echo ($pageTitle === 'Extensions') ? 'active' : ''; ?>" href="/semrush-backend/admin/extensions.php">
          <i class="bi bi-puzzle me-1"></i>
          Extensions
        </a>
      </li>
    </ul>
    
    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
      <span>Administration</span>
    </h6>
    <ul class="nav flex-column mb-2">
      <?php if (isAdmin()): ?>
      <li class="nav-item">
        <a class="nav-link <?php echo ($pageTitle === 'Users') ? 'active' : ''; ?>" href="/semrush-backend/admin/users.php">
          <i class="bi bi-people me-1"></i>
          Users
        </a>
      </li>
      <?php endif; ?>
      
      <li class="nav-item">
        <a class="nav-link <?php echo ($pageTitle === 'Settings') ? 'active' : ''; ?>" href="/semrush-backend/admin/settings.php">
          <i class="bi bi-gear me-1"></i>
          Settings
        </a>
      </li>
    </ul>
  </div>
</nav>