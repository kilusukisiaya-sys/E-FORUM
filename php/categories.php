<?php
session_start(); // Add if needed
require_once 'db_connect.php';

// ALL PHP PROCESSING HERE
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();

// NO OUTPUT UNTIL DOCTYPE
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Head content -->
</head>
<body>
    <!-- Navigation -->

    <div class="categories-grid">
        <?php foreach ($categories as $cat): ?>
            <div class="category-item">
                <h2><?php echo htmlspecialchars($cat['name']); ?></h2>
                <p><?php echo htmlspecialchars($cat['description']); ?></p>
                <a href="dashboard.php?category=<?php echo $cat['id']; ?>">Browse</a>
            </div>
        <?php endforeach; ?>
    </div>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="E-Forum Categories - Browse discussion categories">
    <title>Categories - E-Forum</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/categories.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="navbar-brand"><a href="../index.html" style="color: var(--surface-color);">E-Forum</a></div>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="categories.php" class="nav-link">Categories</a></li>
                <li><a href="profile.php" class="nav-link">Profile</a></li>
                <li><a href="logout.php" id="logoutBtn" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <!-- Header Section -->
        <div class="categories-header mb-5">
            <h1>Forum Categories</h1>
            <p>Explore discussions by category and find topics that interest you</p>
        </div>

        <!-- Categories Grid -->
        <div id="categoriesGrid" class="categories-grid">
            <!-- Category items will be loaded here via JavaScript -->
            <div class="loading-spinner">Loading categories...</div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container text-center">
            <p>&copy; 2025 E-Forum. All rights reserved.</p>
        </div>
    </footer>

    <!-- Alert Messages -->
    <div id="alertContainer"></div>

    <script src="js/main.js"></script>
    <script src="js/categories.js"></script>
</body>
</html>
