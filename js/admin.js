// e-forum-frontend/js/admin.js
// Admin panel related JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Check if user is admin
    if (!AuthManager.isAdmin()) {
        AlertManager.error('Access denied. Admin privileges required.');
        setTimeout(() => window.location.href = 'dashboard.html', 2000);
        return;
    }

    loadAdminDashboard();
    setupTabNavigation();
    setupSearch();
});

// Load admin dashboard
async function loadAdminDashboard() {
    try {
        // Mock admin stats
        const stats = {
            totalUsers: 1234,
            totalDiscussions: 567,
            totalReports: 23,
            totalModerators: 8
        };

        document.getElementById('totalUsers').textContent = stats.totalUsers;
        document.getElementById('totalDiscussions').textContent = stats.totalDiscussions;
        document.getElementById('totalReports').textContent = stats.totalReports;
        document.getElementById('totalModerators').textContent = stats.totalModerators;

        loadUsers();
        loadContent();
        loadReports();

    } catch (error) {
        console.error('Error loading admin dashboard:', error);
        AlertManager.error('Failed to load dashboard. Please try again.');
    }
}

// Load users
async function loadUsers() {
    try {
        // Mock users data
        const mockUsers = [
            {
                id: 1,
                username: 'john_doe',
                email: 'john@example.com',
                role: 'user',
                status: 'active',
                joined: '2024-01-15'
            },
            {
                id: 2,
                username: 'jane_smith',
                email: 'jane@example.com',
                role: 'moderator',
                status: 'active',
                joined: '2024-01-20'
            },
            {
                id: 3,
                username: 'mike_johnson',
                email: 'mike@example.com',
                role: 'user',
                status: 'suspended',
                joined: '2024-02-01'
            }
        ];

        displayUsers(mockUsers);

    } catch (error) {
        console.error('Error loading users:', error);
    }
}

// Display users
function displayUsers(users) {
    const usersList = document.getElementById('usersList');
    
    if (users.length === 0) {
        usersList.innerHTML = '<tr><td colspan="6" class="text-center">No users found</td></tr>';
        return;
    }

    usersList.innerHTML = users.map(user => `
        <tr>
            <td>${escapeHtml(user.username)}</td>
            <td>${escapeHtml(user.email)}</td>
            <td>${user.role}</td>
            <td><span class="status-badge status-${user.status}">${user.status}</span></td>
            <td>${user.joined}</td>
            <td>
                <div class="table-actions">
                    <button class="action-btn action-btn-view" onclick="viewUser(${user.id})">View</button>
                    <button class="action-btn action-btn-edit" onclick="editUser(${user.id})">Edit</button>
                    <button class="action-btn action-btn-delete" onclick="deleteUser(${user.id})">Delete</button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Load content for moderation
async function loadContent() {
    try {
        // Mock content data
        const mockContent = [
            {
                id: 1,
                type: 'thread',
                title: 'Discussion Title',
                author: 'john_doe',
                createdAt: '2024-02-01',
                status: 'pending'
            },
            {
                id: 2,
                type: 'reply',
                title: 'Reply to Discussion',
                author: 'jane_smith',
                createdAt: '2024-02-02',
                status: 'approved'
            }
        ];

        displayContent(mockContent);

    } catch (error) {
        console.error('Error loading content:', error);
    }
}

// Display content
function displayContent(content) {
    const contentList = document.getElementById('contentList');
    
    if (content.length === 0) {
        contentList.innerHTML = '<p>No content to moderate</p>';
        return;
    }

    contentList.innerHTML = content.map(item => `
        <div class="content-item">
            <div class="content-item-header">
                <h4 class="content-item-title">${escapeHtml(item.title)}</h4>
                <span class="status-badge status-${item.status}">${item.status}</span>
            </div>
            <p class="content-item-meta">Type: ${item.type} | Author: ${item.author} | Date: ${item.createdAt}</p>
            <div>
                <button class="action-btn action-btn-view" onclick="viewContent(${item.id})">View</button>
                <button class="action-btn action-btn-edit" onclick="approveContent(${item.id})">Approve</button>
                <button class="action-btn action-btn-delete" onclick="rejectContent(${item.id})">Reject</button>
            </div>
        </div>
    `).join('');
}

// Load reports
async function loadReports() {
    try {
        // Mock reports data
        const mockReports = [
            {
                id: 1,
                reportedContent: 'Inappropriate Discussion',
                reportedBy: 'user_123',
                reason: 'Spam',
                createdAt: '2024-02-01',
                status: 'pending'
            },
            {
                id: 2,
                reportedContent: 'Offensive Reply',
                reportedBy: 'user_456',
                reason: 'Harassment',
                createdAt: '2024-02-02',
                status: 'resolved'
            }
        ];

        displayReports(mockReports);

    } catch (error) {
        console.error('Error loading reports:', error);
    }
}

// Display reports
function displayReports(reports) {
    const reportsList = document.getElementById('reportsList');
    
    if (reports.length === 0) {
        reportsList.innerHTML = '<p>No reports</p>';
        return;
    }

    reportsList.innerHTML = reports.map(report => `
        <div class="report-item">
            <div class="report-item-header">
                <h4 class="report-item-title">${escapeHtml(report.reportedContent)}</h4>
                <span class="status-badge status-${report.status}">${report.status}</span>
            </div>
            <p class="report-item-meta">Reported by: ${report.reportedBy} | Reason: ${report.reason} | Date: ${report.createdAt}</p>
            <div>
                <button class="action-btn action-btn-view" onclick="viewReport(${report.id})">View</button>
                <button class="action-btn action-btn-edit" onclick="resolveReport(${report.id})">Resolve</button>
            </div>
        </div>
    `).join('');
}

// Setup tab navigation
function setupTabNavigation() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(tabName + 'Tab').classList.add('active');
        });
    });
}

// Setup search
function setupSearch() {
    const userSearch = document.getElementById('userSearch');
    if (userSearch) {
        userSearch.addEventListener('input', debounce(function(e) {
            // Implement user search
            console.log('Searching users:', e.target.value);
        }, 300));
    }

    const contentSearch = document.getElementById('contentSearch');
    if (contentSearch) {
        contentSearch.addEventListener('input', debounce(function(e) {
            // Implement content search
            console.log('Searching content:', e.target.value);
        }, 300));
    }
}

// User actions
function viewUser(userId) {
    AlertManager.info('View user ' + userId);
}

function editUser(userId) {
    AlertManager.info('Edit user ' + userId);
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        AlertManager.success('User deleted');
    }
}

// Content actions
function viewContent(contentId) {
    AlertManager.info('View content ' + contentId);
}

function approveContent(contentId) {
    AlertManager.success('Content approved');
}

function rejectContent(contentId) {
    AlertManager.success('Content rejected');
}

// Report actions
function viewReport(reportId) {
    AlertManager.info('View report ' + reportId);
}

function resolveReport(reportId) {
    AlertManager.success('Report resolved');
}

// Utility functions
function debounce(func, delay) {
    let timeoutId;
    return function(...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
