<?php
// Check if session is already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create uploads directory if it doesn't exist
$uploadsDir = __DIR__ . '/uploads/avatars/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
}

// Check if default avatar exists, create one if not
$defaultAvatar = $uploadsDir . 'default-avatar.png';
if (!file_exists($defaultAvatar)) {
    // Create a simple default avatar
    $image = imagecreatetruecolor(200, 200);
    $bgColor = imagecolorallocate($image, 100, 149, 237);
    $textColor = imagecolorallocate($image, 255, 255, 255);
    imagefill($image, 0, 0, $bgColor);
    imagestring($image, 5, 50, 85, 'AVATAR', $textColor);
    imagestring($image, 3, 40, 115, 'DEFAULT', $textColor);
    imagepng($image, $defaultAvatar);
    imagedestroy($image);
}

require_once 'includes/db.php';
require_once 'includes/auth.php';

// Initialize auth
initAuth();

// Check if user is logged in
Auth::requireLogin();

$userId = Auth::getUserId();
$username = Auth::getUsername();

// Get current user profile
$userProfile = Auth::getUserProfile($userId);

// Handle form submission
$success = false;
$error = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $formData = [
        'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
        'bio' => filter_var($_POST['bio'] ?? '', FILTER_SANITIZE_STRING),
        'location' => filter_var($_POST['location'] ?? '', FILTER_SANITIZE_STRING),
        'website' => filter_var($_POST['website'] ?? '', FILTER_SANITIZE_URL)
    ];
    
    // Handle avatar upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatar = $_FILES['avatar'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (in_array($avatar['type'], $allowedTypes) && $avatar['size'] <= $maxSize) {
            $uploadDir = 'uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileName = 'user_' . $userId . '_' . time() . '.' . pathinfo($avatar['name'], PATHINFO_EXTENSION);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($avatar['tmp_name'], $filePath)) {
                // Resize image if needed
                resizeImage($filePath, 200, 200);
                $formData['avatar'] = $fileName;
                
                // Delete old avatar if not default
                $oldAvatar = $userProfile['avatar'] ?? '';
                if ($oldAvatar && $oldAvatar !== 'default-avatar.png' && file_exists($uploadDir . $oldAvatar)) {
                    unlink($uploadDir . $oldAvatar);
                }
            } else {
                $error = 'Failed to upload avatar. Please try again.';
            }
        } else {
            $error = 'Invalid avatar file. Please upload JPG, PNG, GIF or WebP image under 2MB.';
        }
    }
    
    // Update profile - FIXED ERROR HANDLING
    try {
        // Check if Auth::updateProfile exists
        if (method_exists('Auth', 'updateProfile')) {
            $result = Auth::updateProfile($userId, $formData);
            
            // Check if result is valid
            if (is_array($result) && isset($result['success'])) {
                if ($result['success']) {
                    $success = true;
                    // Refresh user profile
                    $userProfile = Auth::getUserProfile($userId);
                } else {
                    $error = $result['message'] ?? 'Failed to update profile.';
                }
            } else {
                // Result is not in expected format
                $error = 'Profile update returned invalid response.';
                // For debugging, try to update manually
                $success = updateProfileManually($userId, $formData);
                if ($success) {
                    $userProfile = Auth::getUserProfile($userId);
                }
            }
        } else {
            // Method doesn't exist, use manual update
            $success = updateProfileManually($userId, $formData);
            if ($success) {
                $userProfile = Auth::getUserProfile($userId);
            } else {
                $error = 'Update profile method not available.';
            }
        }
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
    }
}

// Manual update function if Auth::updateProfile doesn't work
function updateProfileManually($userId, $data) {
    global $conn; // Assuming $conn is your database connection from db.php
    
    if (!isset($conn) || !$conn) {
        return false;
    }
    
    try {
        // Build update query
        $fields = [];
        $params = [];
        $types = '';
        
        foreach ($data as $key => $value) {
            if ($key === 'avatar' || $key === 'email' || $key === 'bio' || $key === 'location' || $key === 'website') {
                $fields[] = "$key = ?";
                $params[] = $value;
                $types .= 's'; // string type
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        // Add user ID as last parameter
        $params[] = $userId;
        $types .= 'i'; // integer type for user ID
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $result = $stmt->execute();
            $stmt->close();
            return $result;
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - e-Forum</title>
    <!-- FIXED CSS LINKS: Changed to correct file names -->
    <link rel="stylesheet" href="css/style2.css">
    <link rel="stylesheet" href="css/profile3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Fallback styling in case CSS files are missing */
        .alert {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
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
        .avatar-preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 10px;
        }
        /* Basic layout */
        .container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }
        .profile-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <?php include 'includes/header.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="profile-container">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-info">
                        <div class="avatar-container">
                            <img src="uploads/avatars/<?php echo htmlspecialchars($userProfile['avatar'] ?? 'default-avatar.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($username); ?>" class="avatar">
                        </div>
                        <div class="profile-details">
                            <h1>Edit Profile</h1>
                            <p class="profile-bio">Update your personal information and preferences</p>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <a href="profile.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Profile
                        </a>
                    </div>
                </div>

                <!-- Profile Navigation -->
                <div class="profile-nav">
                    <a href="profile.php">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="edit-profile.php" class="nav-active">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="change-password.php">
                        <i class="fas fa-key"></i> Change Password
                    </a>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        Profile updated successfully!
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Edit Profile Form -->
                <div class="edit-profile-form">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-row">
                            <!-- Left Column -->
                            <div class="form-column">
                                <!-- Avatar Upload -->
                                <div class="form-group">
                                    <label for="avatar">
                                        <i class="fas fa-camera"></i> Profile Picture
                                    </label>
                                    <div class="avatar-upload-section">
                                        <div class="avatar-preview">
                                            <img id="avatarPreview" 
                                                 src="uploads/avatars/<?php echo htmlspecialchars($userProfile['avatar'] ?? 'default-avatar.png'); ?>" 
                                                 alt="Avatar Preview">
                                        </div>
                                        <div class="avatar-upload-controls">
                                            <input type="file" id="avatar" name="avatar" 
                                                   accept="image/*" class="file-input"
                                                   onchange="previewAvatar(event)">
                                            <label for="avatar" class="file-label">
                                                <i class="fas fa-upload"></i> Choose Image
                                            </label>
                                            <p class="file-info">JPG, PNG or GIF, max 2MB</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bio -->
                                <div class="form-group">
                                    <label for="bio">
                                        <i class="fas fa-user-edit"></i> Bio
                                    </label>
                                    <textarea id="bio" name="bio" rows="4" 
                                              placeholder="Tell us about yourself..."
                                              maxlength="500"><?php echo htmlspecialchars($userProfile['bio'] ?? ''); ?></textarea>
                                    <div class="char-count" id="bioCharCount">0/500</div>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="form-column">
                                <!-- Username (read-only) -->
                                <div class="form-group">
                                    <label>
                                        <i class="fas fa-user"></i> Username
                                    </label>
                                    <input type="text" value="<?php echo htmlspecialchars($username); ?>" disabled>
                                    <small class="form-help">Username cannot be changed</small>
                                </div>

                                <!-- Email -->
                                <div class="form-group">
                                    <label for="email">
                                        <i class="fas fa-envelope"></i> Email Address
                                        <span class="required">*</span>
                                    </label>
                                    <input type="email" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($userProfile['email'] ?? ''); ?>"
                                           placeholder="your.email@example.com" required>
                                </div>

                                <!-- Location -->
                                <div class="form-group">
                                    <label for="location">
                                        <i class="fas fa-map-marker-alt"></i> Location
                                    </label>
                                    <input type="text" id="location" name="location" 
                                           value="<?php echo htmlspecialchars($userProfile['location'] ?? ''); ?>"
                                           placeholder="City, Country">
                                </div>

                                <!-- Website -->
                                <div class="form-group">
                                    <label for="website">
                                        <i class="fas fa-globe"></i> Website
                                    </label>
                                    <input type="url" id="website" name="website" 
                                           value="<?php echo htmlspecialchars($userProfile['website'] ?? ''); ?>"
                                           placeholder="https://example.com">
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <a href="profile.php" class="btn-cancel">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- JavaScript -->
    <script>
    function previewAvatar(event) {
        const input = event.target;
        const preview = document.getElementById('avatarPreview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Character counter for bio
    const bioTextarea = document.getElementById('bio');
    const bioCharCount = document.getElementById('bioCharCount');
    
    if (bioTextarea && bioCharCount) {
        // Initialize count
        bioCharCount.textContent = `${bioTextarea.value.length}/500`;
        
        bioTextarea.addEventListener('input', function() {
            const count = this.value.length;
            bioCharCount.textContent = `${count}/500`;
            
            if (count > 450) {
                bioCharCount.style.color = '#f59e0b';
            } else if (count > 500) {
                bioCharCount.style.color = '#ef4444';
            } else {
                bioCharCount.style.color = '#94a3b8';
            }
        });
    }
    
    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const email = document.getElementById('email').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        
        if (!emailRegex.test(email)) {
            e.preventDefault();
            alert('Please enter a valid email address');
            return false;
        }
        
        const bio = document.getElementById('bio').value;
        if (bio.length > 500) {
            e.preventDefault();
            alert('Bio must not exceed 500 characters');
            return false;
        }
        
        return true;
    });
    </script>
</body>
</html>
<?php
// Image resize function
function resizeImage($filePath, $maxWidth, $maxHeight) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    list($width, $height, $type) = getimagesize($filePath);
    
    if ($width <= $maxWidth && $height <= $maxHeight) {
        return true;
    }
    
    $ratio = $width / $height;
    
    if ($maxWidth / $maxHeight > $ratio) {
        $newWidth = $maxHeight * $ratio;
        $newHeight = $maxHeight;
    } else {
        $newWidth = $maxWidth;
        $newHeight = $maxWidth / $ratio;
    }
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filePath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filePath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filePath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($filePath);
            break;
        default:
            return false;
    }
    
    $destination = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagecolortransparent($destination, imagecolorallocatealpha($destination, 0, 0, 0, 127));
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
    }
    
    imagecopyresampled($destination, $source, 0, 0, 0, 0, 
                      $newWidth, $newHeight, $width, $height);
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $filePath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $filePath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $filePath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($destination, $filePath, 90);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($destination);
    
    return true;
}
?>