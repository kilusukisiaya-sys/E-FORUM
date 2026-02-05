<?php
session_start();

// All files are in C:/xampp/htdocs/vx/
// Simple relative paths
$base_dir = __DIR__; // C:/xampp/htdocs/vx/
$includes_dir = $base_dir . '/includes';

// Check and create includes directory if needed
if (!is_dir($includes_dir)) {
    mkdir($includes_dir, 0755, true);
}

// Simple auth check - for development
if (!isset($_SESSION['user_id'])) {
    // Auto-login for development
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'TestUser';
    $_SESSION['role'] = 'member';
    $_SESSION['logged_in'] = true;
}

// Simple database connection for categories
$categories = [];
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=e_forum;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Check if categories table exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'categories'");
    if ($table_check->rowCount() == 0) {
        // Create categories table
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            is_active BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert sample categories
        $sample_categories = [
            ['General Discussion', 'General topics and discussions'],
            ['Programming', 'Programming languages and concepts'],
            ['Web Development', 'HTML, CSS, JavaScript, PHP'],
            ['Database', 'MySQL, PostgreSQL, MongoDB'],
            ['Mobile Development', 'iOS, Android, React Native'],
            ['Career Advice', 'Job tips and career guidance']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        foreach ($sample_categories as $cat) {
            $stmt->execute([$cat[0], $cat[1]]);
        }
    }
    
    // Fetch categories
    $stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    // Use default categories if database fails
    $categories = [
        ['id' => 1, 'name' => 'General Discussion'],
        ['id' => 2, 'name' => 'Programming'],
        ['id' => 3, 'name' => 'Web Development']
    ];
}

// Check user role
$isModerator = isset($_SESSION['role']) && ($_SESSION['role'] === 'moderator' || $_SESSION['role'] === 'admin');
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$userName = $_SESSION['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Discussion - e-Forum</title>
    <link rel="stylesheet" href="css/create-thread.css">
    <link rel="stylesheet" href="css/https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="forum-header">
            <div class="header-content">
                <a href="index.php" class="logo">
                    <i class="fas fa-comments"></i>
                    <span>e-Forum</span>
                </a>
                <nav class="main-nav">
                    <a href="index.php"><i class="fas fa-home"></i> Home</a>
                    <a href="categories.php"><i class="fas fa-folder"></i> Categories</a>
                    <a href="my-threads.php"><i class="fas fa-list"></i> My Threads</a>
                    <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <div class="create-thread-container">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="header-left">
                        <h1><i class="fas fa-pen-square"></i> Create New Discussion</h1>
                        <p class="subtitle">Share your thoughts, questions, or ideas with the community</p>
                    </div>
                    <div class="user-info">
                        <span><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($userName); ?></span>
                    </div>
                </div>

                <!-- Quick Tips -->
                <div class="tips-card">
                    <h3><i class="fas fa-lightbulb"></i> Tips for a Great Discussion</h3>
                    <div class="tips-grid">
                        <div class="tip-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Be clear and descriptive in your title</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Choose the right category</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Provide enough context in your content</span>
                        </div>
                        <div class="tip-item">
                            <i class="fas fa-check-circle"></i>
                            <span>Use relevant tags for better visibility</span>
                        </div>
                    </div>
                </div>

                <!-- Create Thread Form -->
                <div class="form-card">
                    <form id="createThreadForm" method="POST" action="api/create_thread.php">
                        <!-- Title Field -->
                        <div class="form-group">
                            <div class="label-container">
                                <label for="title">
                                    <i class="fas fa-heading"></i> Discussion Title
                                    <span class="required">*</span>
                                </label>
                                <span class="char-count" id="titleCharCount">0/100</span>
                            </div>
                            <input type="text" id="title" name="title" 
                                   placeholder="Enter a clear and descriptive title for your discussion" 
                                   maxlength="100" required>
                            <div class="error-message" id="titleError"></div>
                            <div class="field-info">Minimum 5 characters, maximum 100 characters</div>
                        </div>

                        <!-- Category Field -->
                        <div class="form-group">
                            <label for="category">
                                <i class="fas fa-folder-open"></i> Category
                                <span class="required">*</span>
                            </label>
                            <div class="select-wrapper">
                                <select id="category" name="category" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            <div class="error-message" id="categoryError"></div>
                        </div>

                        <!-- Content Field -->
                        <div class="form-group">
                            <div class="label-container">
                                <label for="content">
                                    <i class="fas fa-align-left"></i> Discussion Content
                                    <span class="required">*</span>
                                </label>
                                <span class="char-count" id="contentCharCount">0/5000</span>
                            </div>
                            <div class="editor-toolbar">
                                <button type="button" class="editor-btn" onclick="formatText('bold')" title="Bold">
                                    <i class="fas fa-bold"></i>
                                </button>
                                <button type="button" class="editor-btn" onclick="formatText('italic')" title="Italic">
                                    <i class="fas fa-italic"></i>
                                </button>
                                <button type="button" class="editor-btn" onclick="formatText('link')" title="Insert Link">
                                    <i class="fas fa-link"></i>
                                </button>
                                <button type="button" class="editor-btn" onclick="formatText('code')" title="Code Block">
                                    <i class="fas fa-code"></i>
                                </button>
                                <button type="button" class="editor-btn" onclick="formatText('quote')" title="Quote">
                                    <i class="fas fa-quote-right"></i>
                                </button>
                            </div>
                            <textarea id="content" name="content" 
                                      placeholder="Write your discussion content here... (Minimum 20 characters)"
                                      rows="8" maxlength="5000" required></textarea>
                            <div class="error-message" id="contentError"></div>
                            <div class="field-info">You can use basic Markdown formatting (**, *, `, >)</div>
                        </div>

                        <!-- Tags Field -->
                        <div class="form-group">
                            <label for="tags">
                                <i class="fas fa-tags"></i> Tags
                                <span class="optional">(optional)</span>
                            </label>
                            <input type="text" id="tags" name="tags" 
                                   placeholder="Enter tags separated by commas (e.g., javascript, php, web-development)">
                            <div class="error-message" id="tagsError"></div>
                            <div class="field-info">Add up to 5 tags to help others find your discussion</div>
                        </div>

                        <!-- Moderator Options -->
                        <?php if ($isModerator || $isAdmin): ?>
                        <div class="moderator-options">
                            <h3><i class="fas fa-shield-alt"></i> Moderator Options</h3>
                            <div class="option-group">
                                <div class="checkbox-group">
                                    <input type="checkbox" id="isPinned" name="isPinned">
                                    <label for="isPinned">
                                        <span class="checkbox-custom"></span>
                                        <span class="checkbox-label">Pin this discussion at the top</span>
                                    </label>
                                </div>
                                <div class="checkbox-group">
                                    <input type="checkbox" id="isLocked" name="isLocked">
                                    <label for="isLocked">
                                        <span class="checkbox-custom"></span>
                                        <span class="checkbox-label">Lock this discussion (no new replies)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="previewThread()">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                            <div class="action-buttons">
                                <button type="button" class="btn-cancel" onclick="window.location.href='index.php'">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-paper-plane"></i> Create Discussion
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Preview Modal -->
                <div id="previewModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><i class="fas fa-eye"></i> Preview Discussion</h2>
                            <button class="close-modal" onclick="closePreview()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div id="previewContent"></div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn-secondary" onclick="closePreview()">Close Preview</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="forum-footer">
            <p>&copy; <?php echo date('Y'); ?> e-Forum. All rights reserved.</p>
            <div class="footer-links">
                <a href="terms.php">Terms of Service</a>
                <a href="privacy.php">Privacy Policy</a>
                <a href="help.php">Help Center</a>
            </div>
        </footer>
    </div>

    <!-- JavaScript Files -->
    <script src="js/create-thread.js"></script>
</body>
</html>