<?php
// Check if user is logged in (if Auth class is available)
$isLoggedIn = false;
$username = '';
$userAvatar = 'default-avatar.png';

if (function_exists('Auth::isLoggedIn') || class_exists('Auth')) {
    $isLoggedIn = Auth::isLoggedIn();
    if ($isLoggedIn) {
        $username = Auth::getUsername();
        $userId = Auth::getUserId();
        $userProfile = Auth::getUserProfile($userId);
        $userAvatar = $userProfile['avatar'] ?? 'default-avatar.png';
    }
}
?>
<header class="header">
    <div class="header-container">
        <!-- Logo -->
        <div class="logo">
            <a href="index.php">
                <i class="fas fa-comments"></i>
                <span>e-Forum</span>
            </a>
        </div>

        <!-- Search Bar -->
        <div class="search-bar">
            <form action="search.php" method="GET">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" placeholder="Search discussions..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    <button type="submit">Search</button>
                </div>
            </form>
        </div>

        <!-- Navigation -->
        <nav class="main-nav">
            <ul>
                <li>
                    <a href="index.php">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li>
                    <a href="categories.php">
                        <i class="fas fa-th-list"></i>
                        <span>Categories</span>
                    </a>
                </li>
                <li>
                    <a href="topics.php">
                        <i class="fas fa-fire"></i>
                        <span>Trending</span>
                    </a>
                </li>
                <li>
                    <a href="new-topic.php">
                        <i class="fas fa-plus-circle"></i>
                        <span>New Topic</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- User Menu -->
        <div class="user-menu">
            <?php if ($isLoggedIn): ?>
                <div class="user-dropdown">
                    <button class="user-btn">
                        <img src="uploads/avatars/<?php echo htmlspecialchars($userAvatar); ?>" 
                             alt="<?php echo htmlspecialchars($username); ?>" class="user-avatar">
                        <span class="user-name"><?php echo htmlspecialchars($username); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu">
                        <a href="profile.php">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="edit-profile.php">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <a href="my-topics.php">
                            <i class="fas fa-file-alt"></i> My Topics
                        </a>
                        <a href="my-replies.php">
                            <i class="fas fa-reply"></i> My Replies
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="settings.php">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <a href="logout.php" class="logout">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn-login">Login</a>
                    <a href="register.php" class="btn-register">Register</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>