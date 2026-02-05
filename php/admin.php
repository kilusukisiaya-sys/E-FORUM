<?php
session_start();
require_once 'db_connect.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// SECURITY: Only allow Admins to see this page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit;
}

// Fetch Stats with error handling
try {
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $totalThreads = $pdo->query("SELECT COUNT(*) FROM threads")->fetchColumn();
    $pendingReports = $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
    
    // Fetch Recent Users for the table
    $usersStmt = $pdo->query("SELECT id, username, email, role, status FROM users ORDER BY created_at DESC LIMIT 10");
    $users = $usersStmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="E-Forum Admin Panel">
    <title>Admin Panel - E-Forum</title>
    <link rel="stylesheet" href="cc/style.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="navbar-brand"><a href="../index.php" style="color: var(--surface-color);">E-Forum Admin</a></div>
            <ul class="navbar-nav">
                <li><a href="admin.php" class="nav-link">Dashboard</a></li>
                <li><a href="#users" class="nav-link">Users</a></li>
                <li><a href="#content" class="nav-link">Content</a></li>
                <li><a href="#reports" class="nav-link">Reports</a></li>
                <li><a href="logout.php" id="logoutBtn" class="nav-link">Logout</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <!-- Admin Header -->
        <div class="admin-header mb-5">
            <h1>Admin Dashboard</h1>
            <p>Manage forum content, users, and moderation</p>
        </div>

        <div class="stats-grid mb-5">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p class="stat-number"><?php echo $totalUsers; ?></p>
            </div>
            <div class="stat-card">
                <h3>Discussions</h3>
                <p class="stat-number"><?php echo $totalThreads; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Reports</h3>
                <p class="stat-number" style="color: var(--error-color);"><?php echo $pendingReports; ?></p>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="admin-tabs mb-4">
            <button class="tab-btn active" data-tab="users">User Management</button>
            <button class="tab-btn" data-tab="content">Content Moderation</button>
            <button class="tab-btn" data-tab="reports">Reported Content</button>
        </div>

        <!-- Users Tab -->
        <div id="usersTab" class="tab-content active">
            <div class="admin-section">
                <h3>Manage Users</h3>
                <div class="search-box mb-3">
                    <input type="text" id="userSearch" class="form-control" placeholder="Search users...">
                </div>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>#<?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role']; ?>">
                                    <?php echo $user['role']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="status status-<?php echo $user['status']; ?>">
                                    <?php echo $user['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn-sm btn-edit">Edit</a>
                                <button class="btn-sm btn-delete" data-user-id="<?php echo $user['id']; ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Content Tab -->
        <div id="contentTab" class="tab-content">
            <div class="admin-section">
                <h3>Content Moderation</h3>
                <div class="search-box mb-3">
                    <input type="text" id="contentSearch" class="form-control" placeholder="Search content...">
                </div>
                <div id="contentList" class="content-list">
                    <p class="text-muted">Content moderation features coming soon. Use the database directly for now.</p>
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reportsTab" class="tab-content">
            <div class="admin-section">
                <h3>Reported Content</h3>
                <div id="reportsList" class="reports-list">
                    <?php if ($pendingReports > 0): ?>
                        <p>There are <?php echo $pendingReports; ?> pending reports.</p>
                        <!-- You would loop through reports here -->
                    <?php else: ?>
                        <p class="text-success">No pending reports! 🎉</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- User Action Modal -->
    <div id="userActionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>User Actions</h2>
                <button class="modal-close" id="closeUserModal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="userActionSelect">Select Action</label>
                    <select id="userActionSelect" class="form-control">
                        <option value="">Choose an action</option>
                        <option value="promote">Promote to Moderator</option>
                        <option value="demote">Demote from Moderator</option>
                        <option value="suspend">Suspend User</option>
                        <option value="ban">Ban User</option>
                        <option value="activate">Activate User</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="actionReason">Reason (Optional)</label>
                    <textarea id="actionReason" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelUserActionBtn">Cancel</button>
                <button type="button" class="btn btn-accent" id="confirmUserActionBtn">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container text-center">
            <p>&copy; <?php echo date('Y'); ?> E-Forum Admin Panel. All rights reserved.</p>
        </div>
    </footer>

    <!-- Alert Messages -->
    <div id="alertContainer"></div>

    <script src="js/main.js"></script>
    <script src="js/admin.js"></script>
</body>
</html>