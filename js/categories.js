// e-forum-frontend/js/categories.js
// Categories related JavaScript

document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
});

// Load categories
async function loadCategories() {
    try {
        const categoriesGrid = document.getElementById('categoriesGrid');
        categoriesGrid.innerHTML = '<div class="loading-spinner">Loading categories...</div>';

        // Mock categories data
        const mockCategories = [
            {
                id: 1,
                name: 'Technology',
                slug: 'technology',
                description: 'Discuss programming, web development, software engineering, and technology trends.',
                icon: 'T',
                discussions: 234,
                replies: 1256,
                members: 456
            },
            {
                id: 2,
                name: 'Business',
                slug: 'business',
                description: 'Business strategies, entrepreneurship, marketing, and professional development.',
                icon: 'B',
                discussions: 178,
                replies: 892,
                members: 312
            },
            {
                id: 3,
                name: 'Education',
                slug: 'education',
                description: 'Learning resources, courses, educational platforms, and academic discussions.',
                icon: 'E',
                discussions: 145,
                replies: 723,
                members: 289
            },
            {
                id: 4,
                name: 'Health',
                slug: 'health',
                description: 'Health tips, wellness, fitness routines, and medical information sharing.',
                icon: 'H',
                discussions: 98,
                replies: 456,
                members: 201
            },
            {
                id: 5,
                name: 'Entertainment',
                slug: 'entertainment',
                description: 'Movies, music, games, books, and entertainment industry discussions.',
                icon: 'E',
                discussions: 267,
                replies: 1534,
                members: 523
            },
            {
                id: 6,
                name: 'General',
                slug: 'general',
                description: 'General discussions, off-topic conversations, and community announcements.',
                icon: 'G',
                discussions: 156,
                replies: 834,
                members: 378
            }
        ];

        displayCategories(mockCategories);

    } catch (error) {
        console.error('Error loading categories:', error);
        AlertManager.error('Failed to load categories. Please try again.');
    }
}

// Display categories
function displayCategories(categories) {
    const categoriesGrid = document.getElementById('categoriesGrid');
    
    if (categories.length === 0) {
        categoriesGrid.innerHTML = '<div class="empty-state"><p>No categories available.</p></div>';
        return;
    }

    categoriesGrid.innerHTML = categories.map(category => `
        <div class="category-card" onclick="viewCategory('${category.slug}')">
            <div class="category-icon">${category.icon}</div>
            <h3>${escapeHtml(category.name)}</h3>
            <p class="category-description">${escapeHtml(category.description)}</p>
            <div class="category-stats">
                <div class="category-stat">
                    <div class="category-stat-value">${category.discussions}</div>
                    <div class="category-stat-label">Discussions</div>
                </div>
                <div class="category-stat">
                    <div class="category-stat-value">${category.replies}</div>
                    <div class="category-stat-label">Replies</div>
                </div>
                <div class="category-stat">
                    <div class="category-stat-value">${category.members}</div>
                    <div class="category-stat-label">Members</div>
                </div>
            </div>
            <button class="btn btn-accent">View Category</button>
        </div>
    `).join('');
}

// View category
function viewCategory(categorySlug) {
    window.location.href = `dashboard.html?category=${categorySlug}`;
}

// Utility function
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
