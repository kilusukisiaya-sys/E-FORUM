<?php
// includes/db.php
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'e_forum';
$username = 'root';
$password = '';

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // First connect without database to create it if needed
    $pdo_temp = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Create database if it doesn't exist
    $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo_temp->exec("USE `$dbname`");
    
    // Now create the main PDO connection
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Check and create tables if they don't exist
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
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
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_username (username),
            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'categories' => "CREATE TABLE IF NOT EXISTS categories (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'threads' => "CREATE TABLE IF NOT EXISTS threads (
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
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_category_id (category_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'posts' => "CREATE TABLE IF NOT EXISTS posts (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content TEXT NOT NULL,
            user_id INT NOT NULL,
            thread_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_thread_id (thread_id),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        
        'post_likes' => "CREATE TABLE IF NOT EXISTS post_likes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (user_id, post_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_post_id (post_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];
    
    // Create tables
    foreach ($tables as $table => $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Continue even if some tables fail
        }
    }
    
    // Insert default categories if none exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch();
    if ($result['count'] == 0) {
        $defaultCategories = [
            ['General Discussion', 'general', 'Talk about anything and everything'],
            ['Technology', 'technology', 'Computers, gadgets, and tech news'],
            ['Programming', 'programming', 'Coding discussions and help'],
            ['Gaming', 'gaming', 'Video games and esports'],
            ['Off Topic', 'off-topic', 'Everything else']
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, slug, description) VALUES (?, ?, ?)");
        foreach ($defaultCategories as $category) {
            $stmt->execute($category);
        }
    }
    
    // Create a default admin user if no users exist
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    if ($result['count'] == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, email, password, reputation) VALUES (?, ?, ?, ?)")
            ->execute(['admin', 'admin@example.com', $hashedPassword, 100]);
        
        $hashedPassword = password_hash('user123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)")
            ->execute(['user', 'user@example.com', $hashedPassword]);
    }
    
} catch (PDOException $e) {
    // Fallback connection if database exists but we can't create it
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e2) {
        die("Database connection failed: " . $e2->getMessage());
    }
}

// Global database connection available
return $pdo;
?>