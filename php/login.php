<?php
session_start();
require_once 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
            
            // Set remember me cookie if checked
            if (isset($_POST['rememberMe'])) {
                setcookie('forum_remember', $user['id'], time() + (30 * 24 * 60 * 60), '/');
            }
            
            // REDIRECT to dashboard
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $error = "Database error. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="E-Forum Login - Sign in to your account">
    <title>Login - E-Forum</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <style>
        .loading {
            display: none;
            text-align: center;
            padding: 10px;
        }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--accent-color);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="navbar-brand"><a href="../index.php" style="color: var(--surface-color); text-decoration: none;">E-Forum</a></div>
            <ul class="navbar-nav">
                <li><a href="../index.php" class="nav-link">Home</a></li>
                <li><a href="register.php" class="nav-link">Register</a></li>
            </ul>
        </div>
    </nav>

    <!-- Login Form Container -->
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="text-center mb-4">Sign In to E-Forum</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error mb-3">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 'account_created'): ?>
                <div class="alert alert-success mb-3">
                    Account created successfully! Please login.
                </div>
            <?php endif; ?>
            
            <form id="loginForm" class="auth-form" method="POST" action="login.php">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        placeholder="Enter your email"
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Enter your password"
                        required>
                </div>

                <div class="form-group d-flex justify-content-between align-items-center">
                    <label class="checkbox-label">
                        <input type="checkbox" id="rememberMe" name="rememberMe">
                        Remember me
                    </label>
                    <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-accent w-100" id="loginBtn">
                    Sign In
                </button>
                
                <div class="loading" id="loadingIndicator">
                    <div class="loading-spinner"></div>
                    <p>Signing in...</p>
                </div>
            </form>

            <div class="auth-divider">
                <span>or continue with</span>
            </div>

            <div class="social-login">
                <button type="button" class="btn-social" id="googleLogin">
                    <span>Google</span>
                </button>
                <button type="button" class="btn-social" id="facebookLogin">
                    <span>Facebook</span>
                </button>
            </div>

            <p class="auth-footer text-center mt-4">
                Don't have an account? <a href="register.php">Create one here</a>
            </p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> E-Forum. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Simple form submission without AJAX interference
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginBtn');
            const loading = document.getElementById('loadingIndicator');
            
            // Show loading, hide button
            loginBtn.style.display = 'none';
            loading.style.display = 'block';
            
            // Let the form submit normally - PHP will handle it
            // Don't use e.preventDefault() unless you're doing AJAX
        });
        
        // Optional: Remove any JavaScript that might be preventing form submission
        document.addEventListener('DOMContentLoaded', function() {
            // Check if auth.js is interfering
            console.log('Login form ready to submit normally');
        });
    </script>
    
    <!-- Only include auth.js if it doesn't interfere with form submission -->
    <!-- <script src="../js/auth.js"></script> -->
    <script src="js/main.js"></script>
</body>
</html>