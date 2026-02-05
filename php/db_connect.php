<?php
// includes/db_connect.php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$db   = 'e_forum';
$user = 'root';
$pass = ''; // CHANGE THIS IN PRODUCTION!
$charset = 'utf8mb4';

// Data Source Name
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// PDO options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Create PDO instance
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Optional: Test connection
    // $pdo->query("SELECT 1");
    
} catch (\PDOException $e) {
    // Log error (in production)
    error_log("Database Connection Failed: " . $e->getMessage());
    
    // Show user-friendly message
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        die("<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'>
                <h3>Database Connection Error</h3>
                <p>We're experiencing technical difficulties. Please try again later.</p>
                <small>Development mode: " . htmlspecialchars($e->getMessage()) . "</small>
             </div>");
    } else {
        // For AJAX/API requests
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}

// Optional: Set timezone if needed
// $pdo->query("SET time_zone = '+00:00'");

// Function to get database instance (optional)
function getDB() {
    global $pdo;
    return $pdo;
}
?>