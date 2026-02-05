<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

class Auth {
    protected static $initialized = false;
    protected static $pdo = null;
    
    // Initialize the Auth class
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        try {
            // Include database connection
            require_once __DIR__ . '/db.php';
            self::$pdo = $GLOBALS['pdo'] ?? null;
            
            if (!self::$pdo) {
                // Try to get from included db.php
                self::$pdo = include(__DIR__ . '/db.php');
            }
            
            self::$initialized = true;
        } catch (Exception $e) {
            error_log("Auth initialization error: " . $e->getMessage());
        }
    }
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    // Get current user ID
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    // Get current username
    public static function getUsername() {
        return $_SESSION['username'] ?? 'Guest';
    }
    
    // Get current user email
    public static function getEmail() {
        return $_SESSION['email'] ?? null;
    }
    
    // Get user role
    public static function getUserRole() {
        return $_SESSION['role'] ?? 'member';
    }
    
    // Get complete user profile
    public static function getUserProfile($userId = null) {
        self::init();
        
        if (!$userId) {
            $userId = self::getUserId();
        }
        
        if (!$userId || !self::$pdo) {
            return null;
        }
        
        try {
            // Get user info with counts
            $stmt = self::$pdo->prepare("
                SELECT 
                    u.*,
                    (SELECT COUNT(*) FROM threads WHERE user_id = u.id) as thread_count,
                    (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count,
                    (SELECT COUNT(*) FROM post_likes WHERE user_id = u.id) as like_count,
                    COALESCE(u.reputation, 0) as reputation
                FROM users u
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Add additional calculated fields
                $user['post_count'] = $user['post_count'] ?? 0;
                $user['thread_count'] = $user['thread_count'] ?? 0;
                $user['like_count'] = $user['like_count'] ?? 0;
                $user['reputation'] = $user['reputation'] ?? 0;
                
                // Format dates
                $user['joined_date'] = date('F j, Y', strtotime($user['created_at']));
                $user['joined_time_ago'] = self::timeAgo($user['created_at']);
            }
            
            return $user;
        } catch (PDOException $e) {
            error_log("Get user profile error: " . $e->getMessage());
            return null;
        }
    }
    
    // Login user
    public static function login($username, $password, $remember = false) {
        self::init();
        
        if (!self::$pdo) {
            return false;
        }
        
        try {
            // Find user by username or email
            $stmt = self::$pdo->prepare("
                SELECT id, username, email, password, avatar, reputation, created_at 
                FROM users 
                WHERE username = ? OR email = ?
            ");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['avatar'] = $user['avatar'];
                $_SESSION['reputation'] = $user['reputation'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expire = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    setcookie('remember_token', $token, $expire, '/');
                    setcookie('remember_user', $user['id'], $expire, '/');
                    
                    // Store token in database (optional)
                    $stmt = self::$pdo->prepare("
                        UPDATE users SET remember_token = ? WHERE id = ?
                    ");
                    $stmt->execute([$token, $user['id']]);
                }
                
                // Update last login time
                $stmt = self::$pdo->prepare("
                    UPDATE users SET updated_at = NOW() WHERE id = ?
                ");
                $stmt->execute([$user['id']]);
                
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    // Register new user
    public static function register($username, $email, $password, $additionalData = []) {
        self::init();
        
        if (!self::$pdo) {
            return ['success' => false, 'message' => 'Database connection failed'];
        }
        
        try {
            // Check if username exists
            $stmt = self::$pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            
            // Check if email exists
            $stmt = self::$pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepare data
            $data = array_merge([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'avatar' => 'default-avatar.png',
                'bio' => '',
                'location' => '',
                'website' => '',
                'reputation' => 0
            ], $additionalData);
            
            // Insert user
            $columns = implode(', ', array_keys($data));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));
            
            $stmt = self::$pdo->prepare("INSERT INTO users ($columns) VALUES ($placeholders)");
            $stmt->execute(array_values($data));
            
            $userId = self::$pdo->lastInsertId();
            
            // Auto login after registration
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['avatar'] = $data['avatar'];
            $_SESSION['reputation'] = 0;
            $_SESSION['logged_in'] = true;
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    // Logout user
    public static function logout() {
        // Clear session
        $_SESSION = [];
        
        // Destroy session
        if (session_id()) {
            session_destroy();
        }
        
        // Clear remember me cookies
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('remember_user', '', time() - 3600, '/');
        
        return true;
    }
    
    // Update user profile
    public static function updateProfile($userId, $data) {
        self::init();
        
        if (!self::$pdo) {
            return false;
        }
        
        try {
            $updates = [];
            $values = [];
            
            foreach ($data as $key => $value) {
                if (in_array($key, ['username', 'email', 'avatar', 'bio', 'location', 'website'])) {
                    $updates[] = "$key = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $values[] = $userId;
            $sql = "UPDATE users SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = self::$pdo->prepare($sql);
            $result = $stmt->execute($values);
            
            // Update session if updating current user
            if ($result && $userId == self::getUserId()) {
                if (isset($data['username'])) {
                    $_SESSION['username'] = $data['username'];
                }
                if (isset($data['avatar'])) {
                    $_SESSION['avatar'] = $data['avatar'];
                }
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Update profile error: " . $e->getMessage());
            return false;
        }
    }
    
    // Change password
    public static function changePassword($userId, $currentPassword, $newPassword) {
        self::init();
        
        if (!self::$pdo) {
            return false;
        }
        
        try {
            // Verify current password
            $stmt = self::$pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user || !password_verify($currentPassword, $user['password'])) {
                return false;
            }
            
            // Update to new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = self::$pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$hashedPassword, $userId]);
            
        } catch (PDOException $e) {
            error_log("Change password error: " . $e->getMessage());
            return false;
        }
    }
    
    // Check if user exists
    public static function userExists($identifier) {
        self::init();
        
        if (!self::$pdo) {
            return false;
        }
        
        try {
            $stmt = self::$pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$identifier, $identifier]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("User exists check error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get user by ID
    public static function getUserById($userId) {
        self::init();
        
        if (!self::$pdo) {
            return null;
        }
        
        try {
            $stmt = self::$pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }
    
    // Get user by username
    public static function getUserByUsername($username) {
        self::init();
        
        if (!self::$pdo) {
            return null;
        }
        
        try {
            $stmt = self::$pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user by username error: " . $e->getMessage());
            return null;
        }
    }
    
    // Search users
    public static function searchUsers($query, $limit = 10) {
        self::init();
        
        if (!self::$pdo) {
            return [];
        }
        
        try {
            $searchQuery = "%$query%";
            $stmt = self::$pdo->prepare("
                SELECT id, username, avatar, reputation, created_at 
                FROM users 
                WHERE username LIKE ? OR email LIKE ?
                ORDER BY reputation DESC, username ASC
                LIMIT ?
            ");
            $stmt->execute([$searchQuery, $searchQuery, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Search users error: " . $e->getMessage());
            return [];
        }
    }
    
    // Check if email is verified (placeholder for future implementation)
    public static function isEmailVerified($userId) {
        // This is a placeholder - implement email verification later
        return true;
    }
    
    // Generate password reset token
    public static function generatePasswordResetToken($email) {
        self::init();
        
        if (!self::$pdo) {
            return null;
        }
        
        try {
            // Check if user exists
            $stmt = self::$pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return null;
            }
            
            // Generate token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = self::$pdo->prepare("
                UPDATE users 
                SET reset_token = ?, reset_expires = ? 
                WHERE id = ?
            ");
            $stmt->execute([$token, $expires, $user['id']]);
            
            return $token;
        } catch (PDOException $e) {
            error_log("Generate reset token error: " . $e->getMessage());
            return null;
        }
    }
    
    // Validate password reset token
    public static function validatePasswordResetToken($token) {
        self::init();
        
        if (!self::$pdo) {
            return false;
        }
        
        try {
            $stmt = self::$pdo->prepare("
                SELECT id FROM users 
                WHERE reset_token = ? AND reset_expires > NOW()
            ");
            $stmt->execute([$token]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("Validate reset token error: " . $e->getMessage());
            return false;
        }
    }
    
    // Reset password with token
    public static function resetPasswordWithToken($token, $newPassword) {
        self::init();
        
        if (!self::$pdo) {
            return false;
        }
        
        try {
            // Get user by valid token
            $stmt = self::$pdo->prepare("
                SELECT id FROM users 
                WHERE reset_token = ? AND reset_expires > NOW()
            ");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return false;
            }
            
            // Update password and clear token
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = self::$pdo->prepare("
                UPDATE users 
                SET password = ?, reset_token = NULL, reset_expires = NULL, updated_at = NOW()
                WHERE id = ?
            ");
            $result = $stmt->execute([$hashedPassword, $user['id']]);
            
            return $result;
        } catch (PDOException $e) {
            error_log("Reset password error: " . $e->getMessage());
            return false;
        }
    }
    
    // Helper function: Time ago
    public static function timeAgo($date) {
        $time = strtotime($date);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return "just now";
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . " minute" . ($minutes == 1 ? "" : "s") . " ago";
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . " hour" . ($hours == 1 ? "" : "s") . " ago";
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . " day" . ($days == 1 ? "" : "s") . " ago";
        } else {
            return date('M j, Y', $time);
        }
    }
    
    // Check if user is admin
    public static function isAdmin($userId = null) {
        if (!$userId) {
            $userId = self::getUserId();
        }
        
        // This is a simple implementation
        // In a real system, you would check a role column in the database
        $adminUsers = ['admin', 'administrator', 'superadmin'];
        $user = self::getUserById($userId);
        
        return $user && in_array(strtolower($user['username']), $adminUsers);
    }
    
    // Get all users (for admin)
    public static function getAllUsers($limit = 50, $offset = 0) {
        self::init();
        
        if (!self::$pdo) {
            return [];
        }
        
        try {
            $stmt = self::$pdo->prepare("
                SELECT id, username, email, avatar, reputation, created_at, updated_at
                FROM users 
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }
    
    // Delete user (admin only)
    public static function deleteUser($userId) {
        self::init();
        
        if (!self::$pdo || !self::isAdmin()) {
            return false;
        }
        
        try {
            $stmt = self::$pdo->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Delete user error: " . $e->getMessage());
            return false;
        }
    }
    
    // Require login - redirect if not logged in
    public static function requireLogin($redirectUrl = 'login.php') {
        if (!self::isLoggedIn()) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirectUrl);
            exit();
        }
    }
    
    // Get current user
    public static function getCurrentUser() {
        return self::getUserById(self::getUserId());
    }
    
    // Update last login
    public static function updateLastLogin($userId) {
        self::init();
        
        if (!self::$pdo) {
            return false;
        }
        
        try {
            $stmt = self::$pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
            return false;
        }
    }
    
    // Get user settings
    public static function getUserSettings($userId = null) {
        self::init();
        
        if (!$userId) {
            $userId = self::getUserId();
        }
        
        if (!$userId || !self::$pdo) {
            return null;
        }
        
        try {
            $stmt = self::$pdo->prepare("SELECT * FROM user_settings WHERE user_id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Get user settings error: " . $e->getMessage());
            return null;
        }
    }
    
    // Update user settings
    public static function updateUserSettings($userId, $settings) {
        self::init();
        
        if (!self::$pdo) {
            return false;
        }
        
        try {
            $updates = [];
            $values = [];
            
            foreach ($settings as $key => $value) {
                if (in_array($key, ['email_notifications', 'push_notifications', 'theme', 'timezone', 'language', 
                                    'show_online_status', 'allow_private_messages', 'font_size', 'content_density', 
                                    'time_format', 'code_theme', 'line_numbers', 'syntax_highlighting'])) {
                    $updates[] = "$key = ?";
                    $values[] = $value;
                }
            }
            
            if (empty($updates)) {
                return false;
            }
            
            $values[] = $userId;
            $sql = "INSERT INTO user_settings (user_id, " . implode(', ', array_keys($settings)) . ", updated_at) 
                    VALUES (?, " . implode(', ', array_fill(0, count($settings), '?')) . ", NOW())
                    ON DUPLICATE KEY UPDATE " . implode(', ', $updates) . ", updated_at = NOW()";
            
            $stmt = self::$pdo->prepare($sql);
            return $stmt->execute(array_merge([$userId], array_values($settings), $values));
            
        } catch (PDOException $e) {
            error_log("Update user settings error: " . $e->getMessage());
            return false;
        }
    }
}

// Legacy function for backward compatibility
function initAuth() {
    Auth::init();
}

// Auto-initialize Auth class
Auth::init();
?>