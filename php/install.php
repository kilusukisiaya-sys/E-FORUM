<?php
// install.php - Run this once to set up the database
require_once 'includes/db.php';

echo "<h2>Setting up e-Forum Database...</h2>";

try {
    // 1. Create users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            avatar VARCHAR(255) DEFAULT 'default-avatar.png',
            bio TEXT,
            location VARCHAR(100),
            website VARCHAR(255),
            reputation INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Created users table<br>";
    
    // 2. Create categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    echo "✓ Created categories table<br>";
    
    // 3. Create threads table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS threads (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            user_id INT NOT NULL,
            category_id INT NOT NULL,
            view_count INT DEFAULT 0,
            is_locked BOOLEAN DEFAULT FALSE,
            is_sticky BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Created threads table<br>";
    
    // 4. Create posts table (replies to threads)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS posts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content TEXT NOT NULL,
            user_id INT NOT NULL,
            thread_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Created posts table<br>";
    
    // 5. Create post_likes table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS post_likes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (user_id, post_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
        )
    ");
    echo "✓ Created post_likes table<br>";
    
    // 6. Insert default categories
    $categories = [
        ['General Discussion', 'general', 'Talk about anything and everything'],
        ['Technology', 'technology', 'Computers, gadgets, and tech news'],
        ['Programming', 'programming', 'Coding discussions and help'],
        ['Gaming', 'gaming', 'Video games and esports'],
        ['Off Topic', 'off-topic', 'Everything else']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
    foreach ($categories as $category) {
        $stmt->execute($category);
    }
    echo "✓ Added default categories<br>";
    
    // 7. Create a test user (password: 'password123')
    $hashedPassword = password_hash('password123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO users (username, email, password) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute(['testuser', 'test@example.com', $hashedPassword]);
    echo "✓ Created test user (username: testuser, password: password123)<br>";
    
    echo "<h3 style='color: green;'>✅ Database setup completed successfully!</h3>";
    echo "<p>You can now:</p>";
    echo "<ol>";
    echo "<li><a href='login.php'>Login</a> with testuser/password123</li>";
    echo "<li><a href='profile.php'>View profile page</a></li>";
    echo "<li>Delete this install.php file for security</li>";
    echo "</ol>";
    
} catch (PDOException $e) {
    die("<p style='color: red;'>Error setting up database: " . $e->getMessage() . "</p>");
}
?>