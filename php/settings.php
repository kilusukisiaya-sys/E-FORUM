<?php
// settings.php - Fixed version

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/db.php';
require_once 'includes/auth.php';

// Initialize auth (backward compatible way)
if (function_exists('initAuth')) {
    initAuth();
} else {
    Auth::init();
}

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = Auth::getUserId();
$username = Auth::getUsername();

// Get user profile for additional info
$userProfile = Auth::getUserProfile($userId);

// Handle form submissions
$success = false;
$error = '';

// Check if user_settings table exists, if not create it
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL UNIQUE,
            email_notifications BOOLEAN DEFAULT 1,
            push_notifications BOOLEAN DEFAULT 1,
            theme VARCHAR(20) DEFAULT 'light',
            timezone VARCHAR(50) DEFAULT 'UTC',
            language VARCHAR(10) DEFAULT 'en',
            show_online_status BOOLEAN DEFAULT 1,
            allow_private_messages BOOLEAN DEFAULT 1,
            font_size VARCHAR(20) DEFAULT 'medium',
            content_density VARCHAR(20) DEFAULT 'normal',
            time_format VARCHAR(10) DEFAULT '24h',
            code_theme VARCHAR(20) DEFAULT 'default',
            line_numbers BOOLEAN DEFAULT 1,
            syntax_highlighting BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (PDOException $e) {
    // Table creation failed, but we'll continue with defaults
}

// Get current settings
try {
    $stmt = $pdo->prepare("
        SELECT email_notifications, push_notifications, theme, 
               timezone, language, show_online_status, allow_private_messages,
               font_size, content_density, time_format, code_theme,
               line_numbers, syntax_highlighting
        FROM user_settings 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $settings = $stmt->fetch();
    
    if (!$settings) {
        // Default settings if none exist
        $settings = [
            'email_notifications' => 1,
            'push_notifications' => 1,
            'theme' => 'light',
            'timezone' => 'UTC',
            'language' => 'en',
            'show_online_status' => 1,
            'allow_private_messages' => 1,
            'font_size' => 'medium',
            'content_density' => 'normal',
            'time_format' => '24h',
            'code_theme' => 'default',
            'line_numbers' => 1,
            'syntax_highlighting' => 1
        ];
    }
} catch (PDOException $e) {
    // Default settings if query fails
    $settings = [
        'email_notifications' => 1,
        'push_notifications' => 1,
        'theme' => 'light',
        'timezone' => 'UTC',
        'language' => 'en',
        'show_online_status' => 1,
        'allow_private_messages' => 1,
        'font_size' => 'medium',
        'content_density' => 'normal',
        'time_format' => '24h',
        'code_theme' => 'default',
        'line_numbers' => 1,
        'syntax_highlighting' => 1
    ];
}

// Handle notification settings update
if (isset($_POST['update_notifications'])) {
    $emailNotifications = isset($_POST['email_notifications']) ? 1 : 0;
    $pushNotifications = isset($_POST['push_notifications']) ? 1 : 0;
    $frequency = $_POST['frequency'] ?? 'instant';
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_settings (user_id, email_notifications, push_notifications, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            email_notifications = VALUES(email_notifications),
            push_notifications = VALUES(push_notifications),
            updated_at = VALUES(updated_at)
        ");
        $stmt->execute([$userId, $emailNotifications, $pushNotifications]);
        $success = 'Notification settings updated successfully!';
        $settings['email_notifications'] = $emailNotifications;
        $settings['push_notifications'] = $pushNotifications;
    } catch (PDOException $e) {
        $error = 'Failed to update notification settings: ' . $e->getMessage();
    }
}

// Handle privacy settings update
if (isset($_POST['update_privacy'])) {
    $showOnline = isset($_POST['show_online_status']) ? 1 : 0;
    $allowMessages = isset($_POST['allow_private_messages']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_settings (user_id, show_online_status, allow_private_messages, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            show_online_status = VALUES(show_online_status),
            allow_private_messages = VALUES(allow_private_messages),
            updated_at = VALUES(updated_at)
        ");
        $stmt->execute([$userId, $showOnline, $allowMessages]);
        $success = 'Privacy settings updated successfully!';
        $settings['show_online_status'] = $showOnline;
        $settings['allow_private_messages'] = $allowMessages;
    } catch (PDOException $e) {
        $error = 'Failed to update privacy settings: ' . $e->getMessage();
    }
}

// Handle appearance settings update
if (isset($_POST['update_appearance'])) {
    $theme = $_POST['theme'] ?? 'light';
    $fontSize = $_POST['font_size'] ?? 'medium';
    $density = $_POST['density'] ?? 'normal';
    $timeFormat = $_POST['time_format'] ?? '24h';
    $codeTheme = $_POST['code_theme'] ?? 'default';
    $lineNumbers = isset($_POST['line_numbers']) ? 1 : 0;
    $syntaxHighlighting = isset($_POST['syntax_highlighting']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_settings (user_id, theme, font_size, content_density, time_format, 
                                      code_theme, line_numbers, syntax_highlighting, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            theme = VALUES(theme),
            font_size = VALUES(font_size),
            content_density = VALUES(content_density),
            time_format = VALUES(time_format),
            code_theme = VALUES(code_theme),
            line_numbers = VALUES(line_numbers),
            syntax_highlighting = VALUES(syntax_highlighting),
            updated_at = VALUES(updated_at)
        ");
        $stmt->execute([$userId, $theme, $fontSize, $density, $timeFormat, $codeTheme, $lineNumbers, $syntaxHighlighting]);
        
        // Store theme in session for immediate effect
        $_SESSION['theme'] = $theme;
        $success = 'Appearance settings updated successfully!';
        
        // Update settings array
        $settings['theme'] = $theme;
        $settings['font_size'] = $fontSize;
        $settings['content_density'] = $density;
        $settings['time_format'] = $timeFormat;
        $settings['code_theme'] = $codeTheme;
        $settings['line_numbers'] = $lineNumbers;
        $settings['syntax_highlighting'] = $syntaxHighlighting;
    } catch (PDOException $e) {
        $error = 'Failed to update appearance settings: ' . $e->getMessage();
    }
}

// Get user's email from profile
$userEmail = $userProfile['email'] ?? 'Not set';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - e-Forum</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/profile2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Settings specific styles */
        .settings-nav {
            display: flex;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .settings-nav a {
            padding: 10px 20px;
            text-decoration: none;
            color: #333;
            border-radius: 5px;
            margin-right: 5px;
            transition: all 0.3s;
        }
        
        .settings-nav a:hover {
            background: #e9ecef;
        }
        
        .settings-nav a.nav-active {
            background: #007bff;
            color: white;
        }
        
        .settings-section {
            display: none;
        }
        
        .settings-section.active {
            display: block;
        }
        
        .section-description {
            color: #666;
            margin-bottom: 20px;
        }
        
        .settings-group {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .settings-options {
            margin-top: 15px;
        }
        
        .option-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: flex-start;
        }
        
        .option-item:last-child {
            border-bottom: none;
        }
        
        .option-item input[type="checkbox"],
        .option-item input[type="radio"] {
            margin-right: 15px;
            margin-top: 5px;
        }
        
        .option-title {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .option-desc {
            display: block;
            color: #666;
            font-size: 14px;
        }
        
        .theme-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 15px;
        }
        
        .theme-option input {
            display: none;
        }
        
        .theme-label {
            display: block;
            cursor: pointer;
            text-align: center;
        }
        
        .theme-preview {
            width: 100%;
            height: 100px;
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
            border: 2px solid transparent;
            transition: border-color 0.3s;
        }
        
        .theme-label:hover .theme-preview {
            border-color: #007bff;
        }
        
        input:checked + .theme-label .theme-preview {
            border-color: #007bff;
            border-width: 3px;
        }
        
        .light-theme {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            position: relative;
        }
        
        .light-theme .theme-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: #007bff;
        }
        
        .light-theme .theme-content {
            position: absolute;
            top: 30px;
            left: 10px;
            right: 10px;
            height: 20px;
            background: #e9ecef;
            border-radius: 3px;
        }
        
        .light-theme .theme-sidebar {
            position: absolute;
            bottom: 10px;
            left: 10px;
            width: 30%;
            height: 15px;
            background: #f8f9fa;
            border-radius: 3px;
        }
        
        .dark-theme {
            background: linear-gradient(135deg, #343a40 0%, #212529 100%);
            position: relative;
        }
        
        .dark-theme .theme-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: #495057;
        }
        
        .dark-theme .theme-content {
            position: absolute;
            top: 30px;
            left: 10px;
            right: 10px;
            height: 20px;
            background: #6c757d;
            border-radius: 3px;
        }
        
        .dark-theme .theme-sidebar {
            position: absolute;
            bottom: 10px;
            left: 10px;
            width: 30%;
            height: 15px;
            background: #495057;
            border-radius: 3px;
        }
        
        .blue-theme {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            position: relative;
        }
        
        .blue-theme .theme-header {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 20px;
            background: #0056b3;
        }
        
        .blue-theme .theme-content {
            position: absolute;
            top: 30px;
            left: 10px;
            right: 10px;
            height: 20px;
            background: #0069d9;
            border-radius: 3px;
        }
        
        .blue-theme .theme-sidebar {
            position: absolute;
            bottom: 10px;
            left: 10px;
            width: 30%;
            height: 15px;
            background: #0056b3;
            border-radius: 3px;
        }
        
        .auto-theme {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .auto-theme .theme-icon {
            color: #6c757d;
            font-size: 24px;
        }
        
        .theme-name {
            font-weight: 500;
        }
        
        .form-select {
            width: 200px;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ced4da;
        }
        
        .form-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }
        
        .btn-primary, .btn-secondary, .btn-danger, .btn-warning {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .account-info, .danger-zone {
            margin-top: 15px;
        }
        
        .info-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 500;
            min-width: 120px;
        }
        
        .info-value {
            flex-grow: 1;
            color: #666;
        }
        
        .info-action {
            color: #007bff;
            text-decoration: none;
        }
        
        .danger-item {
            padding: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .danger-info h4 {
            color: #721c24;
            margin-bottom: 5px;
        }
        
        .danger-info p {
            color: #666;
            font-size: 14px;
        }
        
        .security-actions, .privacy-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 15px;
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
        }
        
        @media (max-width: 768px) {
            .settings-nav {
                flex-direction: column;
            }
            
            .settings-nav a {
                margin-right: 0;
                margin-bottom: 5px;
            }
            
            .theme-options {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .form-actions, .security-actions, .privacy-actions {
                flex-direction: column;
            }
            
            .btn-primary, .btn-secondary, .btn-danger, .btn-warning {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <?php 
        if (file_exists('includes/header.php')) {
            include 'includes/header.php';
        } else {
            echo '<header style="background: #007bff; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">';
            echo '<h1>e-Forum</h1>';
            echo '<nav>';
            echo '<a href="index.php" style="color: white; margin-right: 15px;">Home</a>';
            echo '<a href="profile.php" style="color: white; margin-right: 15px;">Profile</a>';
            echo '<a href="settings.php" style="color: white; margin-right: 15px;">Settings</a>';
            echo '<a href="logout.php" style="color: white;">Logout</a>';
            echo '</nav>';
            echo '</header>';
        }
        ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="profile-container">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-info">
                        <div class="avatar-container">
                            <img src="uploads/avatars/<?php echo htmlspecialchars($userProfile['avatar'] ?? 'default-avatar.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($username); ?>" class="avatar"
                                 onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=007bff&color=fff&size=150'">
                        </div>
                        <div class="profile-details">
                            <h1>Settings</h1>
                            <p class="profile-bio">Customize your forum experience</p>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <a href="profile.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Profile
                        </a>
                    </div>
                </div>

                <!-- Settings Navigation -->
                <div class="settings-nav">
                    <a href="#notifications" class="nav-active">
                        <i class="fas fa-bell"></i> Notifications
                    </a>
                    <a href="#privacy">
                        <i class="fas fa-shield-alt"></i> Privacy
                    </a>
                    <a href="#appearance">
                        <i class="fas fa-palette"></i> Appearance
                    </a>
                    <a href="#account">
                        <i class="fas fa-user-cog"></i> Account
                    </a>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Settings Content -->
                <div class="settings-content">
                    <!-- Notifications Settings -->
                    <div id="notifications" class="settings-section active">
                        <h2><i class="fas fa-bell"></i> Notification Settings</h2>
                        <p class="section-description">Choose how you want to be notified about forum activity.</p>
                        
                        <form method="POST">
                            <div class="settings-group">
                                <h3>Email Notifications</h3>
                                <div class="settings-options">
                                    <div class="option-item">
                                        <input type="checkbox" id="email_notifications" name="email_notifications" 
                                               <?php echo $settings['email_notifications'] ? 'checked' : ''; ?>>
                                        <label for="email_notifications">
                                            <span class="option-title">Enable email notifications</span>
                                            <span class="option-desc">Receive email notifications for important activities</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="settings-group">
                                <h3>Push Notifications</h3>
                                <div class="settings-options">
                                    <div class="option-item">
                                        <input type="checkbox" id="push_notifications" name="push_notifications"
                                               <?php echo $settings['push_notifications'] ? 'checked' : ''; ?>>
                                        <label for="push_notifications">
                                            <span class="option-title">Enable push notifications</span>
                                            <span class="option-desc">Receive browser notifications</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="settings-group">
                                <h3>Frequency</h3>
                                <div class="settings-options">
                                    <div class="option-item radio">
                                        <input type="radio" id="frequency_instant" name="frequency" value="instant" checked>
                                        <label for="frequency_instant">
                                            <span class="option-title">Instant</span>
                                            <span class="option-desc">Receive notifications immediately</span>
                                        </label>
                                    </div>
                                    <div class="option-item radio">
                                        <input type="radio" id="frequency_daily" name="frequency" value="daily">
                                        <label for="frequency_daily">
                                            <span class="option-title">Daily Digest</span>
                                            <span class="option-desc">Receive one email per day with all notifications</span>
                                        </label>
                                    </div>
                                    <div class="option-item radio">
                                        <input type="radio" id="frequency_weekly" name="frequency" value="weekly">
                                        <label for="frequency_weekly">
                                            <span class="option-title">Weekly Digest</span>
                                            <span class="option-desc">Receive one email per week with all notifications</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="submit" name="update_notifications" class="btn-primary">
                                    <i class="fas fa-save"></i> Save Notification Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Privacy Settings -->
                    <div id="privacy" class="settings-section">
                        <h2><i class="fas fa-shield-alt"></i> Privacy Settings</h2>
                        <p class="section-description">Control your privacy and visibility on the forum.</p>
                        
                        <form method="POST">
                            <div class="settings-group">
                                <h3>Profile Visibility</h3>
                                <div class="settings-options">
                                    <div class="option-item">
                                        <input type="checkbox" id="show_online_status" name="show_online_status" 
                                               <?php echo $settings['show_online_status'] ? 'checked' : ''; ?>>
                                        <label for="show_online_status">
                                            <span class="option-title">Show online status</span>
                                            <span class="option-desc">Allow others to see when you're online</span>
                                        </label>
                                    </div>
                                    <div class="option-item">
                                        <input type="checkbox" id="show_activity" name="show_activity" checked>
                                        <label for="show_activity">
                                            <span class="option-title">Show recent activity</span>
                                            <span class="option-desc">Display your recent activity on your profile</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="settings-group">
                                <h3>Communication</h3>
                                <div class="settings-options">
                                    <div class="option-item">
                                        <input type="checkbox" id="allow_private_messages" name="allow_private_messages"
                                               <?php echo $settings['allow_private_messages'] ? 'checked' : ''; ?>>
                                        <label for="allow_private_messages">
                                            <span class="option-title">Allow private messages</span>
                                            <span class="option-desc">Allow other users to send you private messages</span>
                                        </label>
                                    </div>
                                    <div class="option-item">
                                        <input type="checkbox" id="allow_mentions" name="allow_mentions" checked>
                                        <label for="allow_mentions">
                                            <span class="option-title">Allow mentions</span>
                                            <span class="option-desc">Allow other users to mention you in posts</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="privacy-actions">
                                <button type="submit" name="update_privacy" class="btn-primary">
                                    <i class="fas fa-save"></i> Save Privacy Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Appearance Settings -->
                    <div id="appearance" class="settings-section">
                        <h2><i class="fas fa-palette"></i> Appearance Settings</h2>
                        <p class="section-description">Customize how the forum looks and feels.</p>
                        
                        <form method="POST">
                            <div class="settings-group">
                                <h3>Theme</h3>
                                <div class="theme-options">
                                    <div class="theme-option">
                                        <input type="radio" id="theme_light" name="theme" value="light"
                                               <?php echo ($settings['theme'] ?? 'light') == 'light' ? 'checked' : ''; ?>>
                                        <label for="theme_light" class="theme-label">
                                            <div class="theme-preview light-theme">
                                                <div class="theme-header"></div>
                                                <div class="theme-content"></div>
                                                <div class="theme-sidebar"></div>
                                            </div>
                                            <span class="theme-name">Light</span>
                                        </label>
                                    </div>
                                    <div class="theme-option">
                                        <input type="radio" id="theme_dark" name="theme" value="dark"
                                               <?php echo ($settings['theme'] ?? 'light') == 'dark' ? 'checked' : ''; ?>>
                                        <label for="theme_dark" class="theme-label">
                                            <div class="theme-preview dark-theme">
                                                <div class="theme-header"></div>
                                                <div class="theme-content"></div>
                                                <div class="theme-sidebar"></div>
                                            </div>
                                            <span class="theme-name">Dark</span>
                                        </label>
                                    </div>
                                    <div class="theme-option">
                                        <input type="radio" id="theme_blue" name="theme" value="blue"
                                               <?php echo ($settings['theme'] ?? 'light') == 'blue' ? 'checked' : ''; ?>>
                                        <label for="theme_blue" class="theme-label">
                                            <div class="theme-preview blue-theme">
                                                <div class="theme-header"></div>
                                                <div class="theme-content"></div>
                                                <div class="theme-sidebar"></div>
                                            </div>
                                            <span class="theme-name">Blue</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="settings-group">
                                <h3>Display Preferences</h3>
                                <div class="settings-options">
                                    <div class="option-item">
                                        <label for="font_size">Font Size</label>
                                        <select id="font_size" name="font_size" class="form-select">
                                            <option value="small" <?php echo ($settings['font_size'] ?? 'medium') == 'small' ? 'selected' : ''; ?>>Small</option>
                                            <option value="medium" <?php echo ($settings['font_size'] ?? 'medium') == 'medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="large" <?php echo ($settings['font_size'] ?? 'medium') == 'large' ? 'selected' : ''; ?>>Large</option>
                                            <option value="xlarge" <?php echo ($settings['font_size'] ?? 'medium') == 'xlarge' ? 'selected' : ''; ?>>Extra Large</option>
                                        </select>
                                    </div>
                                    <div class="option-item">
                                        <label for="density">Content Density</label>
                                        <select id="density" name="density" class="form-select">
                                            <option value="compact" <?php echo ($settings['content_density'] ?? 'normal') == 'compact' ? 'selected' : ''; ?>>Compact</option>
                                            <option value="normal" <?php echo ($settings['content_density'] ?? 'normal') == 'normal' ? 'selected' : ''; ?>>Normal</option>
                                            <option value="comfortable" <?php echo ($settings['content_density'] ?? 'normal') == 'comfortable' ? 'selected' : ''; ?>>Comfortable</option>
                                        </select>
                                    </div>
                                    <div class="option-item">
                                        <label for="time_format">Time Format</label>
                                        <select id="time_format" name="time_format" class="form-select">
                                            <option value="12h" <?php echo ($settings['time_format'] ?? '24h') == '12h' ? 'selected' : ''; ?>>12-hour (2:30 PM)</option>
                                            <option value="24h" <?php echo ($settings['time_format'] ?? '24h') == '24h' ? 'selected' : ''; ?>>24-hour (14:30)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="settings-group">
                                <h3>Code Display</h3>
                                <div class="settings-options">
                                    <div class="option-item">
                                        <label for="code_theme">Code Syntax Theme</label>
                                        <select id="code_theme" name="code_theme" class="form-select">
                                            <option value="default" <?php echo ($settings['code_theme'] ?? 'default') == 'default' ? 'selected' : ''; ?>>Default</option>
                                            <option value="dark" <?php echo ($settings['code_theme'] ?? 'default') == 'dark' ? 'selected' : ''; ?>>Dark</option>
                                            <option value="light" <?php echo ($settings['code_theme'] ?? 'default') == 'light' ? 'selected' : ''; ?>>Light</option>
                                            <option value="monokai" <?php echo ($settings['code_theme'] ?? 'default') == 'monokai' ? 'selected' : ''; ?>>Monokai</option>
                                        </select>
                                    </div>
                                    <div class="option-item">
                                        <input type="checkbox" id="line_numbers" name="line_numbers" 
                                               <?php echo $settings['line_numbers'] ? 'checked' : ''; ?>>
                                        <label for="line_numbers">
                                            <span class="option-title">Show line numbers in code blocks</span>
                                        </label>
                                    </div>
                                    <div class="option-item">
                                        <input type="checkbox" id="syntax_highlighting" name="syntax_highlighting"
                                               <?php echo $settings['syntax_highlighting'] ? 'checked' : ''; ?>>
                                        <label for="syntax_highlighting">
                                            <span class="option-title">Enable syntax highlighting</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" class="btn-secondary" onclick="resetAppearance()">
                                    <i class="fas fa-undo"></i> Reset to Defaults
                                </button>
                                <button type="submit" name="update_appearance" class="btn-primary">
                                    <i class="fas fa-save"></i> Save Appearance Settings
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Account Settings -->
                    <div id="account" class="settings-section">
                        <h2><i class="fas fa-user-cog"></i> Account Settings</h2>
                        <p class="section-description">Manage your account preferences and security.</p>
                        
                        <div class="settings-group">
                            <h3>Account Information</h3>
                            <div class="account-info">
                                <div class="info-item">
                                    <span class="info-label">Username:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($username); ?></span>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($userEmail); ?></span>
                                    <a href="change-email.php" class="info-action">Change</a>
                                </div>
                                <div class="info-item">
                                    <span class="info-label">Member since:</span>
                                    <span class="info-value"><?php echo date('F j, Y', strtotime($userProfile['created_at'] ?? date('Y-m-d H:i:s'))); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <h3>Security</h3>
                            <div class="security-actions">
                                <a href="change-password.php" class="btn-primary">
                                    <i class="fas fa-key"></i> Change Password
                                </a>
                                <a href="two-factor.php" class="btn-secondary">
                                    <i class="fas fa-mobile-alt"></i> Two-Factor Authentication
                                </a>
                            </div>
                        </div>
                        
                        <div class="settings-group">
                            <h3>Danger Zone</h3>
                            <div class="danger-zone">
                                <div class="danger-item">
                                    <div class="danger-info">
                                        <h4>Delete Account</h4>
                                        <p>Permanently delete your account and all associated data. This action cannot be undone.</p>
                                    </div>
                                    <a href="delete-account.php" class="btn-danger" onclick="return confirm('Are you sure you want to delete your account? This cannot be undone!');">
                                        <i class="fas fa-trash"></i> Delete Account
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php 
        if (file_exists('includes/footer.php')) {
            include 'includes/footer.php';
        } else {
            echo '<footer style="text-align: center; padding: 20px; margin-top: 40px; color: #666; border-top: 1px solid #eee;">';
            echo '<p>&copy; ' . date('Y') . ' e-Forum. All rights reserved.</p>';
            echo '</footer>';
        }
        ?>
    </div>

    <!-- JavaScript -->
    <script>
    // Settings navigation
    document.querySelectorAll('.settings-nav a').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Update active nav
            document.querySelectorAll('.settings-nav a').forEach(nav => {
                nav.classList.remove('nav-active');
            });
            this.classList.add('nav-active');
            
            // Show corresponding section
            const targetId = this.getAttribute('href').substring(1);
            document.querySelectorAll('.settings-section').forEach(section => {
                section.classList.remove('active');
                if (section.id === targetId) {
                    section.classList.add('active');
                }
            });
        });
    });
    
    // Reset appearance
    function resetAppearance() {
        document.getElementById('theme_light').checked = true;
        document.getElementById('font_size').value = 'medium';
        document.getElementById('density').value = 'normal';
        document.getElementById('time_format').value = '24h';
        document.getElementById('code_theme').value = 'default';
        document.getElementById('line_numbers').checked = true;
        document.getElementById('syntax_highlighting').checked = true;
        
        alert('Appearance settings reset to defaults.');
    }
    
    // Theme preview
    document.querySelectorAll('input[name="theme"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const theme = this.value;
            document.body.className = '';
            if (theme !== 'light') {
                document.body.classList.add('theme-' + theme);
            }
        });
    });
    
    // Initialize current theme
    const currentTheme = '<?php echo $settings['theme'] ?? 'light'; ?>';
    if (currentTheme !== 'light') {
        document.body.classList.add('theme-' + currentTheme);
    }
    
    // Add theme styles
    const style = document.createElement('style');
    style.textContent = `
        .theme-dark { background: #343a40; color: #f8f9fa; }
        .theme-dark .settings-group { background: #495057; }
        .theme-dark .option-desc { color: #adb5bd; }
        .theme-dark .form-select { background: #495057; color: #f8f9fa; border-color: #6c757d; }
        
        .theme-blue { background: #007bff; color: white; }
        .theme-blue .settings-group { background: #0069d9; }
        .theme-blue .option-desc { color: #b3d7ff; }
        .theme-blue .form-select { background: #0069d9; color: white; border-color: #0056b3; }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>