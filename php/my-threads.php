<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Initialize auth
initAuth();

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = Auth::getUserId();
$username = Auth::getUsername();

// Get user's threads
try {
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name, c.slug as category_slug,
               u.username, u.avatar,
               COUNT(DISTINCT p.id) as reply_count,
               MAX(p.created_at) as last_reply_date
        FROM threads t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN posts p ON t.id = p.thread_id
        WHERE t.user_id = ?
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$userId]);
    $threads = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching threads: " . $e->getMessage());
    $threads = [];
}

// Get user's stats
try {
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM threads WHERE user_id = ?) as thread_count,
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) as post_count,
            (SELECT COUNT(*) FROM likes WHERE user_id = ?) as like_count
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    $stats = ['thread_count' => 0, 'post_count' => 0, 'like_count' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Threads - e-Forum</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                            <img src="uploads/avatars/<?php echo htmlspecialchars($_SESSION['avatar'] ?? 'default-avatar.png'); ?>" 
                                 alt="<?php echo htmlspecialchars($username); ?>" class="avatar">
                        </div>
                        <div class="profile-details">
                            <h1><?php echo htmlspecialchars($username); ?>'s Threads</h1>
                            <p class="profile-bio">Manage your discussions and contributions</p>
                            <div class="profile-stats">
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $stats['thread_count']; ?></span>
                                    <span class="stat-label">Threads</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $stats['post_count']; ?></span>
                                    <span class="stat-label">Replies</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $stats['like_count']; ?></span>
                                    <span class="stat-label">Likes</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="profile-actions">
                        <a href="create-thread.php" class="btn-primary">
                            <i class="fas fa-plus"></i> New Thread
                        </a>
                        <a href="profile.php" class="btn-secondary">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                    </div>
                </div>

                <!-- Threads Navigation -->
                <div class="threads-nav">
                    <a href="my-threads.php" class="nav-active">
                        <i class="fas fa-list"></i> My Threads
                    </a>
                    <a href="my-posts.php">
                        <i class="fas fa-comment"></i> My Replies
                    </a>
                    <a href="my-likes.php">
                        <i class="fas fa-heart"></i> Liked Posts
                    </a>
                    <a href="my-bookmarks.php">
                        <i class="fas fa-bookmark"></i> Bookmarks
                    </a>
                </div>

                <!-- Threads List -->
                <div class="threads-list">
                    <?php if (empty($threads)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comment-slash"></i>
                            <h3>No Threads Yet</h3>
                            <p>You haven't created any discussions yet. Start your first thread!</p>
                            <a href="create-thread.php" class="btn-primary">
                                <i class="fas fa-plus"></i> Create Your First Thread
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="threads-header">
                            <h2>My Discussions (<?php echo count($threads); ?>)</h2>
                            <div class="thread-filters">
                                <select class="filter-select">
                                    <option value="newest">Newest First</option>
                                    <option value="oldest">Oldest First</option>
                                    <option value="popular">Most Popular</option>
                                </select>
                            </div>
                        </div>

                        <?php foreach ($threads as $thread): ?>
                            <div class="thread-item">
                                <div class="thread-main">
                                    <div class="thread-category">
                                        <span class="category-badge"><?php echo htmlspecialchars($thread['category_name']); ?></span>
                                        <?php if ($thread['is_pinned']): ?>
                                            <span class="pinned-badge"><i class="fas fa-thumbtack"></i> Pinned</span>
                                        <?php endif; ?>
                                        <?php if ($thread['is_locked']): ?>
                                            <span class="locked-badge"><i class="fas fa-lock"></i> Locked</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="thread-title">
                                        <a href="thread.php?id=<?php echo $thread['id']; ?>">
                                            <?php echo htmlspecialchars($thread['title']); ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="thread-excerpt">
                                        <?php echo substr(strip_tags($thread['content']), 0, 150) . '...'; ?>
                                    </div>
                                    
                                    <div class="thread-meta">
                                        <span class="meta-item">
                                            <i class="fas fa-eye"></i> <?php echo $thread['view_count']; ?> views
                                        </span>
                                        <span class="meta-item">
                                            <i class="fas fa-comment"></i> <?php echo $thread['reply_count']; ?> replies
                                        </span>
                                        <span class="meta-item">
                                            <i class="fas fa-heart"></i> <?php echo $thread['like_count']; ?> likes
                                        </span>
                                        <span class="meta-item">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo time_ago($thread['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="thread-actions">
                                    <a href="edit-thread.php?id=<?php echo $thread['id']; ?>" class="btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <button class="btn-delete" onclick="deleteThread(<?php echo $thread['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <a href="thread.php?id=<?php echo $thread['id']; ?>" class="btn-view">
                                        <i class="fas fa-external-link-alt"></i> View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Pagination -->
                        <div class="pagination">
                            <button class="pagination-btn disabled">
                                <i class="fas fa-chevron-left"></i> Previous
                            </button>
                            <div class="pagination-numbers">
                                <span class="page-number active">1</span>
                                <span class="page-number">2</span>
                                <span class="page-number">3</span>
                                <span class="page-dots">...</span>
                                <span class="page-number">10</span>
                            </div>
                            <button class="pagination-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <?php include 'includes/footer.php'; ?>
    </div>

    <!-- JavaScript -->
    <script src="js/profile.js"></script>
    <script>
    function deleteThread(threadId) {
        if (confirm('Are you sure you want to delete this thread? This action cannot be undone.')) {
            fetch(`api/delete_thread.php?id=${threadId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Thread deleted successfully');
                    location.reload();
                } else {
                    alert('Failed to delete thread: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete thread');
            });
        }
    }
    
    // Time ago helper function
    function time_ago(date) {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);
        
        let interval = Math.floor(seconds / 31536000);
        if (interval >= 1) return interval + " year" + (interval === 1 ? "" : "s") + " ago";
        
        interval = Math.floor(seconds / 2592000);
        if (interval >= 1) return interval + " month" + (interval === 1 ? "" : "s") + " ago";
        
        interval = Math.floor(seconds / 86400);
        if (interval >= 1) return interval + " day" + (interval === 1 ? "" : "s") + " ago";
        
        interval = Math.floor(seconds / 3600);
        if (interval >= 1) return interval + " hour" + (interval === 1 ? "" : "s") + " ago";
        
        interval = Math.floor(seconds / 60);
        if (interval >= 1) return interval + " minute" + (interval === 1 ? "" : "s") + " ago";
        
        return "just now";
    }
    </script>
</body>
</html>
<?php
// Time ago function for PHP
function time_ago($date) {
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
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return $weeks . " week" . ($weeks == 1 ? "" : "s") . " ago";
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . " month" . ($months == 1 ? "" : "s") . " ago";
    } else {
        $years = floor($diff / 31536000);
        return $years . " year" . ($years == 1 ? "" : "s") . " ago";
    }
}
?>