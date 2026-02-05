<?php
// profile.php - Complete working version
error_reporting(E_ALL);
ini_set('display_errors', 1);


// Include required files
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Initialize Auth
Auth::init();

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

// Get user data
$userId = Auth::getUserId();
$username = Auth::getUsername();

// Get user profile
$userProfile = Auth::getUserProfile($userId);

if (!$userProfile) {
    // If profile not found, create a basic one
    $userProfile = [
        'id' => $userId,
        'username' => $username,
        'avatar' => 'default-avatar.png',
        'bio' => 'No bio yet',
        'location' => '',
        'website' => '',
        'reputation' => 0,
        'thread_count' => 0,
        'post_count' => 0,
        'like_count' => 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
}

// Get database connection
global $pdo;

// Get user's recent threads
$recentThreads = [];
try {
    // First check if threads table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'threads'")->fetch();
    
    if ($tableCheck) {
        $stmt = $pdo->prepare("
            SELECT 
                t.*, 
                c.name as category_name,
                c.slug as category_slug,
                (SELECT COUNT(*) FROM posts WHERE thread_id = t.id) as reply_count,
                u.username as author_name,
                u.avatar as author_avatar
            FROM threads t
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $recentThreads = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Table might not exist yet, that's OK
    error_log("Threads query error: " . $e->getMessage());
}

// Get user's recent posts
$recentPosts = [];
try {
    // Check if posts table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'posts'")->fetch();
    
    if ($tableCheck) {
        $stmt = $pdo->prepare("
            SELECT 
                p.*, 
                t.title as thread_title, 
                t.id as thread_id,
                c.name as category_name,
                c.slug as category_slug,
                u.username as author_name,
                u.avatar as author_avatar
            FROM posts p
            JOIN threads t ON p.thread_id = t.id
            LEFT JOIN categories c ON t.category_id = c.id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ?
            ORDER BY p.created_at DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        $recentPosts = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Table might not exist yet, that's OK
    error_log("Posts query error: " . $e->getMessage());
}

// Calculate total views
$totalViews = 0;
foreach ($recentThreads as $thread) {
    $totalViews += $thread['view_count'] ?? 0;
}

// Time ago function (if not in Auth class)
function time_ago($date) {
    return Auth::timeAgo($date);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($username); ?>'s Profile - e-Forum</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/style2.css">
    <link rel="stylesheet" href="css/profile3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Fallback styles in case profile.css is missing */
        .text-muted { color: #6c757d; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .profile-header { background: #fff; border-radius: 10px; padding: 30px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .avatar { width: 150px; height: 150px; border-radius: 50%; object-fit: cover; }
        .profile-stats { display: flex; gap: 30px; margin-top: 20px; }
        .stat-item { text-align: center; }
        .stat-number { font-size: 24px; font-weight: bold; display: block; }
        .stat-label { color: #666; }
        .profile-nav { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .profile-nav a { margin-right: 20px; text-decoration: none; color: #333; }
        .profile-nav a:hover { color: #007bff; }
        .nav-active { color: #007bff !important; font-weight: bold; }
        .profile-content { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .profile-section { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 5px rgba(0,0,0,0.05); }
        .activity-item { border-bottom: 1px solid #eee; padding: 10px 0; }
        .quick-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .quick-action { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; text-decoration: none; color: #333; }
        .quick-action:hover { background: #e9ecef; }
        .badges-list { display: flex; flex-wrap: wrap; gap: 10px; }
        .badge-item { background: #ffc107; color: #000; padding: 5px 10px; border-radius: 20px; font-size: 12px; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .stat-box { background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .meta-item { margin-right: 20px; color: #666; }
        .meta-item i { margin-right: 5px; }
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
                            <button class="avatar-upload" onclick="document.getElementById('avatarInput').click()" title="Change avatar">
                                <i class="fas fa-camera"></i>
                            </button>
                            <input type="file" id="avatarInput" accept="image/*" style="display: none;">
                        </div>
                        <div class="profile-details">
                            <h1><?php echo htmlspecialchars($username); ?></h1>
                            <p class="profile-bio">
                                <?php echo htmlspecialchars($userProfile['bio'] ?? 'No bio yet'); ?>
                            </p>
                            <div class="profile-meta">
                                <?php if (!empty($userProfile['location'])): ?>
                                    <span class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars($userProfile['location']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if (!empty($userProfile['website'])): ?>
                                    <span class="meta-item">
                                        <i class="fas fa-globe"></i>
                                        <a href="<?php echo htmlspecialchars($userProfile['website']); ?>" target="_blank" rel="nofollow">
                                            <?php echo parse_url($userProfile['website'], PHP_URL_HOST); ?>
                                        </a>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="meta-item">
                                    <i class="fas fa-calendar"></i>
                                    Joined <?php echo date('F Y', strtotime($userProfile['created_at'])); ?>
                                </span>
                                
                                <?php if (Auth::isAdmin()): ?>
                                    <span class="meta-item" style="color: #dc3545;">
                                        <i class="fas fa-crown"></i>
                                        Administrator
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $userProfile['thread_count'] ?? 0; ?></span>
                            <span class="stat-label">Threads</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $userProfile['post_count'] ?? 0; ?></span>
                            <span class="stat-label">Replies</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $userProfile['like_count'] ?? 0; ?></span>
                            <span class="stat-label">Likes</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $userProfile['reputation'] ?? 0; ?></span>
                            <span class="stat-label">Reputation</span>
                        </div>
                    </div>
                </div>

                <!-- Profile Navigation -->
                <div class="profile-nav">
                    <a href="profile.php" class="nav-active">
                        <i class="fas fa-user"></i> Profile
                    </a>
                    <a href="my-threads.php">
                        <i class="fas fa-list"></i> My Threads
                    </a>
                    <a href="my-posts.php">
                        <i class="fas fa-comments"></i> My Posts
                    </a>
                    <a href="edit-profile.php">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                    <a href="settings.php">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <?php if (Auth::isAdmin()): ?>
                    <a href="admin.php" style="color: #dc3545;">
                        <i class="fas fa-shield-alt"></i> Admin
                    </a>
                    <?php endif; ?>
                </div>

                <!-- Profile Content -->
                <div class="profile-content">
                    <!-- Left Column -->
                    <div class="profile-left">
                        <!-- About Section -->
                        <div class="profile-section">
                            <h3><i class="fas fa-info-circle"></i> About</h3>
                            <div class="about-content">
                                <?php if (!empty($userProfile['bio'])): ?>
                                    <p><?php echo nl2br(htmlspecialchars($userProfile['bio'])); ?></p>
                                <?php else: ?>
                                    <p class="text-muted">
                                        No bio provided yet. 
                                        <a href="edit-profile.php">Add a bio to tell others about yourself!</a>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if (!empty($userProfile['location']) || !empty($userProfile['website'])): ?>
                                    <div class="about-details" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                        <?php if (!empty($userProfile['location'])): ?>
                                            <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> <?php echo htmlspecialchars($userProfile['location']); ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($userProfile['website'])): ?>
                                            <p><i class="fas fa-globe"></i> <strong>Website:</strong> 
                                                <a href="<?php echo htmlspecialchars($userProfile['website']); ?>" target="_blank" rel="nofollow">
                                                    <?php echo htmlspecialchars($userProfile['website']); ?>
                                                </a>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="profile-section">
                            <h3><i class="fas fa-history"></i> Recent Activity</h3>
                            <div class="activity-list">
                                <?php if (empty($recentThreads) && empty($recentPosts)): ?>
                                    <p class="text-muted">
                                        No recent activity yet. 
                                        <a href="create-thread.php">Start your first thread</a> or 
                                        <a href="index.php">join a discussion</a>!
                                    </p>
                                <?php else: ?>
                                    <?php 
                                    $activities = [];
                                    
                                    // Add threads as activities
                                    foreach ($recentThreads as $thread) {
                                        $activities[] = [
                                            'type' => 'thread',
                                            'title' => $thread['title'],
                                            'content' => 'Started a new thread in ' . ($thread['category_name'] ?? 'General'),
                                            'link' => 'thread.php?id=' . $thread['id'],
                                            'time' => $thread['created_at'],
                                            'icon' => 'fas fa-file-alt',
                                            'color' => '#007bff'
                                        ];
                                    }
                                    
                                    // Add posts as activities
                                    foreach ($recentPosts as $post) {
                                        $activities[] = [
                                            'type' => 'post',
                                            'title' => $post['thread_title'],
                                            'content' => 'Replied to thread',
                                            'link' => 'thread.php?id=' . $post['thread_id'] . '#post-' . $post['id'],
                                            'time' => $post['created_at'],
                                            'icon' => 'fas fa-comment',
                                            'color' => '#28a745'
                                        ];
                                    }
                                    
                                    // Sort by time (newest first)
                                    usort($activities, function($a, $b) {
                                        return strtotime($b['time']) - strtotime($a['time']);
                                    });
                                    
                                    // Display activities
                                    foreach (array_slice($activities, 0, 10) as $activity):
                                    ?>
                                        <div class="activity-item">
                                            <div class="activity-icon" style="color: <?php echo $activity['color']; ?>">
                                                <i class="<?php echo $activity['icon']; ?> fa-lg"></i>
                                            </div>
                                            <div class="activity-content">
                                                <a href="<?php echo $activity['link']; ?>" class="activity-title" style="font-weight: 500;">
                                                    <?php echo htmlspecialchars($activity['title']); ?>
                                                </a>
                                                <p class="activity-desc" style="margin: 5px 0; color: #666;">
                                                    <?php echo $activity['content']; ?>
                                                </p>
                                                <span class="activity-time" style="font-size: 12px; color: #999;">
                                                    <i class="far fa-clock"></i> <?php echo time_ago($activity['time']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if (count($activities) > 10): ?>
                                        <div style="text-align: center; margin-top: 15px;">
                                            <a href="my-threads.php" class="btn" style="background: #f8f9fa; padding: 8px 15px; border-radius: 5px; text-decoration: none;">
                                                View All Activity <i class="fas fa-arrow-right"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="profile-right">
                        <!-- Quick Actions -->
                        <div class="profile-section">
                            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                            <div class="quick-actions">
                                <a href="create-thread.php" class="quick-action">
                                    <i class="fas fa-pen-square fa-2x" style="margin-bottom: 10px;"></i>
                                    <span>New Thread</span>
                                </a>
                                <a href="edit-profile.php" class="quick-action">
                                    <i class="fas fa-user-edit fa-2x" style="margin-bottom: 10px;"></i>
                                    <span>Edit Profile</span>
                                </a>
                                <a href="settings.php" class="quick-action">
                                    <i class="fas fa-cog fa-2x" style="margin-bottom: 10px;"></i>
                                    <span>Settings</span>
                                </a>
                                <a href="my-threads.php" class="quick-action">
                                    <i class="fas fa-list fa-2x" style="margin-bottom: 10px;"></i>
                                    <span>My Threads</span>
                                </a>
                            </div>
                        </div>

                        <!-- Badges -->
                        <div class="profile-section">
                            <h3><i class="fas fa-award"></i> Badges</h3>
                            <div class="badges-list">
                                <?php
                                $badges = [];
                                
                                // New Member (joined less than 30 days ago)
                                $joinedDays = floor((time() - strtotime($userProfile['created_at'])) / (60 * 60 * 24));
                                if ($joinedDays < 30) {
                                    $badges[] = ['icon' => 'fas fa-seedling', 'name' => 'New Member', 'color' => '#28a745'];
                                }
                                
                                // Thread Starter
                                if ($userProfile['thread_count'] > 0) {
                                    $badges[] = ['icon' => 'fas fa-file-alt', 'name' => 'Thread Starter', 'color' => '#007bff'];
                                }
                                
                                // Active Member (more than 10 posts)
                                if ($userProfile['post_count'] > 10) {
                                    $badges[] = ['icon' => 'fas fa-comment', 'name' => 'Active Member', 'color' => '#17a2b8'];
                                }
                                
                                // Helpful (has likes)
                                if ($userProfile['like_count'] > 0) {
                                    $badges[] = ['icon' => 'fas fa-thumbs-up', 'name' => 'Helpful', 'color' => '#ffc107'];
                                }
                                
                                // Top Contributor (high reputation)
                                if ($userProfile['reputation'] > 50) {
                                    $badges[] = ['icon' => 'fas fa-star', 'name' => 'Top Contributor', 'color' => '#dc3545'];
                                }
                                
                                // Admin badge
                                if (Auth::isAdmin()) {
                                    $badges[] = ['icon' => 'fas fa-crown', 'name' => 'Administrator', 'color' => '#6f42c1'];
                                }
                                
                                // Display badges
                                if (empty($badges)) {
                                    echo '<p class="text-muted">No badges yet. Stay active to earn badges!</p>';
                                } else {
                                    foreach ($badges as $badge):
                                ?>
                                    <div class="badge-item" style="background: <?php echo $badge['color']; ?>20; color: <?php echo $badge['color']; ?>; border: 1px solid <?php echo $badge['color']; ?>30;">
                                        <i class="<?php echo $badge['icon']; ?>"></i>
                                        <span><?php echo $badge['name']; ?></span>
                                    </div>
                                <?php endforeach; } ?>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="profile-section">
                            <h3><i class="fas fa-chart-bar"></i> Statistics</h3>
                            <div class="stats-grid">
                                <div class="stat-box">
                                    <div class="stat-icon" style="color: #007bff;">
                                        <i class="fas fa-file-alt fa-2x"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-value"><?php echo $userProfile['thread_count'] ?? 0; ?></span>
                                        <span class="stat-label">Threads Created</span>
                                    </div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-icon" style="color: #28a745;">
                                        <i class="fas fa-comment fa-2x"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-value"><?php echo $userProfile['post_count'] ?? 0; ?></span>
                                        <span class="stat-label">Replies Posted</span>
                                    </div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-icon" style="color: #6c757d;">
                                        <i class="fas fa-eye fa-2x"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-value"><?php echo $totalViews; ?></span>
                                        <span class="stat-label">Total Views</span>
                                    </div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-icon" style="color: #dc3545;">
                                        <i class="fas fa-heart fa-2x"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-value"><?php echo $userProfile['like_count'] ?? 0; ?></span>
                                        <span class="stat-label">Total Likes</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Stats -->
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                <p><strong>Member since:</strong> <?php echo date('F j, Y', strtotime($userProfile['created_at'])); ?></p>
                                <p><strong>Active for:</strong> <?php echo $joinedDays; ?> days</p>
                                <p><strong>Post ratio:</strong> 
                                    <?php 
                                    $threads = $userProfile['thread_count'] ?? 0;
                                    $posts = $userProfile['post_count'] ?? 0;
                                    $total = $threads + $posts;
                                    if ($total > 0) {
                                        echo round(($posts / $total) * 100) . '% replies';
                                    } else {
                                        echo 'No posts yet';
                                    }
                                    ?>
                                </p>
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
    <?php if (file_exists('js/profile.js')): ?>
        <script src="js/profile.js"></script>
    <?php else: ?>
        <script>
            // Basic profile JavaScript
            document.addEventListener('DOMContentLoaded', function() {
                // Avatar upload
                const avatarInput = document.getElementById('avatarInput');
                if (avatarInput) {
                    avatarInput.addEventListener('change', function(e) {
                        if (this.files && this.files[0]) {
                            const formData = new FormData();
                            formData.append('avatar', this.files[0]);
                            
                            fetch('upload-avatar.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Avatar updated successfully!');
                                    location.reload();
                                } else {
                                    alert('Error: ' + (data.message || 'Failed to upload avatar'));
                                }
                            })
                            .catch(error => {
                                alert('Error uploading avatar');
                            });
                        }
                    });
                }
                
                // Tooltips
                const statItems = document.querySelectorAll('.stat-item');
                statItems.forEach(item => {
                    item.title = 'Click to view details';
                    item.style.cursor = 'pointer';
                    item.addEventListener('click', function() {
                        const label = this.querySelector('.stat-label').textContent;
                        alert(`Viewing details for: ${label}`);
                    });
                });
            });
        </script>
    <?php endif; ?>
</body>
</html>