<?php
session_start();
require_once 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$thread_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($thread_id <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Fetch the thread and the author's name
try {
    $stmt = $pdo->prepare("SELECT threads.*, users.username, categories.name as category_name 
                          FROM threads 
                          JOIN users ON threads.user_id = users.id 
                          LEFT JOIN categories ON threads.category_id = categories.id 
                          WHERE threads.id = ?");
    $stmt->execute([$thread_id]);
    $thread = $stmt->fetch();

    if (!$thread) {
        header("Location: dashboard.php?error=thread_not_found");
        exit;
    }

    // Handle New Reply Submission - USING replies table
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['replyContent']) && isset($_SESSION['user_id'])) {
        $reply = trim($_POST['replyContent']);
        $user_id = $_SESSION['user_id'];

        if (!empty($reply)) {
            // INSERT INTO replies table
            $insert = $pdo->prepare("INSERT INTO replies (thread_id, user_id, content) VALUES (?, ?, ?)");
            $insert->execute([$thread_id, $user_id, $reply]);
            
            // Update thread reply count
            $pdo->prepare("UPDATE threads SET reply_count = reply_count + 1 WHERE id = ?")->execute([$thread_id]);
            
            header("Location: thread.php?id=" . $thread_id . "&success=reply_added");
            exit;
        }
    }

    // Fetch all replies FROM replies table
    $replyStmt = $pdo->prepare("SELECT replies.*, users.username 
                               FROM replies 
                               JOIN users ON replies.user_id = users.id 
                               WHERE thread_id = ? 
                               ORDER BY created_at ASC");
    $replyStmt->execute([$thread_id]);
    $replies = $replyStmt->fetchAll();
    
    // Get reply count
    $replyCount = count($replies);
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="E-Forum Discussion Thread">
    <title><?php echo htmlspecialchars($thread['title']); ?> - E-Forum</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/thread.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="navbar-brand"><a href="../index.php" style="color: var(--surface-color); text-decoration: none;">E-Forum</a></div>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="categories.php" class="nav-link">Categories</a></li>
                <li><a href="profile.php" class="nav-link">Profile</a></li>
                <li>
                    <?php if ($isLoggedIn): ?>
                        <a href="logout.php" class="nav-link">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">Login</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if (isset($_GET['success']) && $_GET['success'] == 'reply_added'): ?>
            <div class="alert alert-success mb-3">
                Your reply has been posted successfully!
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error mb-3">
                <?php 
                $errors = [
                    'thread_not_found' => 'Thread not found.',
                    'login_required' => 'Please login to post a reply.'
                ];
                echo $errors[$_GET['error']] ?? 'An error occurred.';
                ?>
            </div>
        <?php endif; ?>

        <!-- Thread Header -->
        <div class="thread-header mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="categories.php">Categories</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($thread['title']); ?></li>
                </ol>
            </nav>
            
            <?php if ($thread['is_pinned']): ?>
                <span class="badge badge-pinned mb-2">📌 Pinned</span>
            <?php endif; ?>
            
            <h1><?php echo htmlspecialchars($thread['title']); ?></h1>
        </div>

        <!-- Thread Info Bar -->
        <div class="thread-info-bar mb-4">
            <div class="thread-info-item">
                <span class="info-label">Category:</span>
                <span class="info-value"><?php echo htmlspecialchars($thread['category_name'] ?? 'Uncategorized'); ?></span>
            </div>
            <div class="thread-info-item">
                <span class="info-label">Started by:</span>
                <span class="info-value"><?php echo htmlspecialchars($thread['username']); ?></span>
            </div>
            <div class="thread-info-item">
                <span class="info-label">Created:</span>
                <span class="info-value"><?php echo date('F j, Y, g:i a', strtotime($thread['created_at'])); ?></span>
            </div>
            <div class="thread-info-item">
                <span class="info-label">Replies:</span>
                <span class="info-value"><?php echo $replyCount; ?></span>
            </div>
            <div class="thread-info-item">
                <span class="info-label">Views:</span>
                <span class="info-value"><?php echo $thread['views'] ?? 0; ?></span>
            </div>
        </div>

        <!-- Original Post -->
        <div class="post-container mb-4">
            <div class="post-card">
                <div class="post-header">
                    <div class="post-author">
                        <div class="author-avatar">
                            <?php echo strtoupper(substr($thread['username'], 0, 2)); ?>
                        </div>
                        <div class="author-info">
                            <h4><?php echo htmlspecialchars($thread['username']); ?></h4>
                            <p class="text-muted">Original Poster</p>
                        </div>
                    </div>
                    <div class="post-meta">
                        <?php echo date('F j, Y, g:i a', strtotime($thread['created_at'])); ?>
                    </div>
                </div>
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($thread['content'])); ?>
                </div>
                <?php if ($isLoggedIn && ($_SESSION['user_id'] == $thread['user_id'] || $_SESSION['role'] == 'admin')): ?>
                <div class="post-actions">
                    <a href="edit-thread.php?id=<?php echo $thread['id']; ?>" class="btn-sm">Edit</a>
                    <a href="delete-thread.php?id=<?php echo $thread['id']; ?>" class="btn-sm btn-error">Delete</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Replies Section -->
        <div class="replies-section mb-4">
            <h3 class="mb-3">
                Replies 
                <span class="badge"><?php echo $replyCount; ?></span>
            </h3>
            
            <div id="repliesList" class="replies-list">
                <?php if (count($replies) > 0): ?>
                    <?php foreach ($replies as $reply): ?>
                        <div class="reply-card mb-3">
                            <div class="reply-header">
                                <div class="reply-author">
                                    <div class="author-avatar small">
                                        <?php echo strtoupper(substr($reply['username'], 0, 2)); ?>
                                    </div>
                                    <div class="author-info">
                                        <h5><?php echo htmlspecialchars($reply['username']); ?></h5>
                                        <p class="text-muted"><?php echo date('M j, Y, g:i a', strtotime($reply['created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="reply-content">
                                <?php echo nl2br(htmlspecialchars($reply['content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <p class="text-muted">No replies yet. Start the conversation!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reply Form -->
        <?php if ($isLoggedIn): ?>
            <div class="reply-form-section mb-5">
                <h3 class="mb-3">Post a Reply</h3>
                <form id="replyForm" class="reply-form" method="POST" action="thread.php?id=<?php echo $thread['id']; ?>">
                    <div class="form-group">
                        <label for="replyContent">Your Reply</label>
                        <textarea 
                            id="replyContent" 
                            name="replyContent" 
                            class="form-control" 
                            placeholder="Share your thoughts..."
                            rows="6"
                            required></textarea>
                    </div>
                    <button type="submit" class="btn btn-accent">Post Reply</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-5">
                <p>Please <a href="login.php">login</a> to post a reply to this discussion.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> E-Forum. All rights reserved.</p>
        </div>
    </footer>

    <!-- Alert Messages -->
    <div id="alertContainer"></div>

    <script src="../js/main.js"></script>
    <script src="../js/thread.js"></script>
</body>
</html>