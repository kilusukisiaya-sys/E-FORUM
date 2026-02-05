<?php
session_start();
require_once 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $bio = trim($_POST['bio'] ?? '');
    
    // Validation
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (!isset($_POST['termsAccepted'])) {
        $error = "You must accept the terms and conditions.";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = "Username must be between 3 and 20 characters.";
    } else {
        try {
            // Check if email already exists
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $error = "Email already registered.";
            } else {
                // Check if username already exists
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $checkStmt->execute([$username]);
                if ($checkStmt->fetch()) {
                    $error = "Username already taken.";
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert user
                    $sql = "INSERT INTO users (username, email, password_hash, bio, role, status) 
                            VALUES (?, ?, ?, ?, 'user', 'active')";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$username, $email, $hashed_password, $bio]);
                    
                    header("Location: login.php?success=account_created");
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = "Registration failed. Please try again.";
            error_log("Registration error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="E-Forum Register - Create a new account">
    <title>Register - E-Forum</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="navbar-brand"><a href="../index.php" style="color: var(--surface-color); text-decoration: none;">E-Forum</a></div>
            <ul class="navbar-nav">
                <li><a href="../index.php" class="nav-link">Home</a></li>
                <li><a href="login.php" class="nav-link">Login</a></li>
            </ul>
        </div>
    </nav>

    <!-- Register Form Container -->
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="text-center mb-4">Create Your E-Forum Account</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error mb-3">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form id="registerForm" class="auth-form" method="POST" action="register.php">
                <div class="form-group">
                    <label for="username">Username *</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Choose a username (3-20 characters)"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required
                        minlength="3"
                        maxlength="20">
                    <small class="form-hint">This will be your public display name</small>
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
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
                    <label for="password">Password *</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Create a strong password (min 8 characters)"
                        required
                        minlength="8">
                    <small class="form-hint">Use at least 8 characters with letters and numbers</small>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password *</label>
                    <input 
                        type="password" 
                        id="confirmPassword" 
                        name="confirmPassword" 
                        class="form-control" 
                        placeholder="Confirm your password"
                        required>
                </div>

                <div class="form-group">
                    <label for="bio">Bio (Optional)</label>
                    <textarea 
                        id="bio" 
                        name="bio" 
                        class="form-control" 
                        placeholder="Tell us about yourself (max 200 characters)"
                        rows="3"
                        maxlength="200"><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                    <small class="form-hint">Brief introduction about yourself</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="termsAccepted" name="termsAccepted" required
                               <?php echo isset($_POST['termsAccepted']) ? 'checked' : ''; ?>>
                        I agree to the <a href="#" style="color: var(--accent-color);">Terms of Service</a> and <a href="#" style="color: var(--accent-color);">Privacy Policy</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-accent w-100">Create Account</button>
            </form>

            <div class="auth-divider">
                <span>or sign up with</span>
            </div>

            <div class="social-login">
                <button type="button" class="btn-social" id="googleSignup">
                    <span>Google</span>
                </button>
                <button type="button" class="btn-social" id="facebookSignup">
                    <span>Facebook</span>
                </button>
            </div>

            <p class="auth-footer text-center mt-4">
                Already have an account? <a href="login.php" style="color: var(--accent-color);">Sign in here</a>
            </p>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> E-Forum. All rights reserved.</p>
        </div>
    </footer>

    <script src="jss/main.js"></script>
    <script src="jss/auth.js"></script>
</body>
</html>