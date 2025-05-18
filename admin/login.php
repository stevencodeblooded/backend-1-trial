<?php
/**
 * Admin Login
 * 
 * Login page for admin panel
 */

// Include configuration
require_once '../config/config.php';

// Include required files
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/auth-functions.php';

// Initialize database connection
$pdo = initializeApp();

// Check if already logged in
if (isLoggedIn()) {
    redirect('/semrush-backend/admin/');
}

// Process login form
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get username and password
    $username = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Authenticate user
    $user = authenticateUser($username, $password, $pdo);
    
    if ($user) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        // Redirect to dashboard or return URL
        $redirectUrl = isset($_SESSION['return_url']) ? $_SESSION['return_url'] : '/semrush-backend/admin/';
        unset($_SESSION['return_url']);
        
        redirect($redirectUrl);
    } else {
        $error = 'Invalid username or password';
    }
}

// Page title
$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo APP_NAME; ?> - <?php echo $pageTitle; ?></title>
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Custom styles -->
  <link href="../assets/css/admin.css" rel="stylesheet">
  
  <style>
    html, body {
      height: 100%;
    }
    
    body {
      display: flex;
      align-items: center;
      padding-top: 40px;
      padding-bottom: 40px;
      background-color: #f5f5f5;
    }
    
    .form-signin {
      width: 100%;
      max-width: 330px;
      padding: 15px;
      margin: auto;
    }
    
    .form-signin .form-floating:focus-within {
      z-index: 2;
    }
    
    .form-signin input[type="text"] {
      margin-bottom: -1px;
      border-bottom-right-radius: 0;
      border-bottom-left-radius: 0;
    }
    
    .form-signin input[type="password"] {
      margin-bottom: 10px;
      border-top-left-radius: 0;
      border-top-right-radius: 0;
    }
  </style>
</head>
<body class="text-center">
  <main class="form-signin">
    <form method="post" action="">
      <img class="mb-4" src="../assets/icon.png" alt="<?php echo APP_NAME; ?>" width="72" height="72">
      <h1 class="h3 mb-3 fw-normal"><?php echo APP_NAME; ?></h1>
      <h2 class="h5 mb-3 fw-normal">Admin Panel</h2>
      
      <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
          <?php echo $error; ?>
        </div>
      <?php endif; ?>
      
      <div class="form-floating">
        <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
        <label for="username">Username</label>
      </div>
      <div class="form-floating">
        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
        <label for="password">Password</label>
      </div>
      
      <button class="w-100 btn btn-lg btn-primary" type="submit">Sign in</button>
      
      <p class="mt-5 mb-3 text-muted">&copy; <?php echo date('Y'); ?> SemrushToolz</p>
    </form>
  </main>
</body>
</html>