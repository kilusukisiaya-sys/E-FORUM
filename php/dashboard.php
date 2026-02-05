<?php
session_start();
require_once 'db_connect.php';

// Enable error reporting (MUST BE AT THE VERY TOP)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch all threads joined with the author's username
try {
    $stmt = $pdo->query("SELECT threads.*, users.username 
                         FROM threads 
                         JOIN users ON threads.user_id = users.id 
                         ORDER BY is_pinned DESC, created_at DESC");
    $threads = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Check if user is logged in (optional but good practice)
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="E-Forum Dashboard - Browse and manage discussions">
    <title>Dashboard - E-Forum</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="navbar-brand"><a href="index.php" style="color: var(--surface-color);">E-Forum</a></div>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="categories.php" class="nav-link">Categories</a></li>
                <li><a href="profile.php" class="nav-link">Profile</a></li>
                <li>
                    <?php if ($isLoggedIn): ?>
                        <a href="logout.php" id="logoutBtn" class="nav-link">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">Login</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <!-- Header Section -->
        <div class="dashboard-header mb-5">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>Forum Dashboard</h1>
                    <p class="text-light">
                        <?php if ($isLoggedIn): ?>
                            Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>!
                        <?php else: ?>
                            Welcome! Browse discussions or login to participate.
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($isLoggedIn): ?>
                    <a href="create-thread.php" class="btn btn-accent">Create New Discussion</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-accent">Login to Post</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="search-filter-section mb-4">
            <div class="search-box">
                <input 
                    type="text" 
                    id="searchInput" 
                    class="form-control" 
                    placeholder="Search discussions..."
                >
            </div>
            <div class="filter-options mt-3">
                <select id="categoryFilter" class="form-control">
                    <option value="">All Categories</option>
                    <option value="technology">Technology</option>
                    <option value="business">Business</option>
                    <option value="education">Education</option>
                    <option value="health">Health</option>
                    <option value="entertainment">Entertainment</option>
                </select>
                <select id="sortFilter" class="form-control ml-2">
                    <option value="recent">Most Recent</option>
                    <option value="popular">Most Popular</option>
                    <option value="trending">Trending</option>
                </select>
            </div>
        </div>

        <!-- Discussions List -->
        <div id="discussionsList" class="discussions-container">
            <h3 class="mb-3">Recent Discussions</h3>
            <?php if (count($threads) > 0): ?>
                <?php foreach ($threads as $thread): ?>
                    <div class="thread-card mb-3 p-3">
                        <?php if ($thread['is_pinned']): ?>
                            <span class="badge badge-pinned">📌 Pinned</span>
                        <?php endif; ?>
                        <h3>
                            <a href="thread.php?id=<?php echo $thread['id']; ?>">
                                <?php echo htmlspecialchars($thread['title']); ?>
                            </a>
                        </h3>
                        <p class="thread-meta">
                            Posted by: <strong><?php echo htmlspecialchars($thread['username']); ?></strong> | 
                            <?php echo date('M d, Y', strtotime($thread['created_at'])); ?> | 
                            Category: <span class="category-tag"><?php echo $thread['category_id']; ?></span>
                        </p>
                        <p class="thread-excerpt">
                            <?php 
                            $content = htmlspecialchars($thread['content']);
                            echo strlen($content) > 150 ? substr($content, 0, 150) . '...' : $content;
                            ?>
                        </p>
                        <a href="thread.php?id=<?php echo $thread['id']; ?>" class="btn btn-sm btn-outline">View Discussion</a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p class="text-muted">No discussions found. Be the first to start one!</p>
                    <?php if ($isLoggedIn): ?>
                        <a href="create-thread.php" class="btn btn-accent mt-2">Create First Discussion</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-accent mt-2">Join Now to Post</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <div id="paginationContainer" class="pagination-container mt-5">
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> E-Forum. All rights reserved.</p>
        </div>
    </footer>

    <!-- Alert Messages -->
    <div id="alertContainer"></div>

    <script src="js/main.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>