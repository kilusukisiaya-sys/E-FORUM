// e-forum-frontend/js/dashboard.js
// Dashboard related JavaScript

let currentPage = 1;
const itemsPerPage = 10;
let allDiscussions = [];

document.addEventListener('DOMContentLoaded', function() {
    // Load discussions
    loadDiscussions();

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }

    // Filter functionality
    const categoryFilter = document.getElementById('categoryFilter');
    const sortFilter = document.getElementById('sortFilter');

    if (categoryFilter) {
        categoryFilter.addEventListener('change', applyFilters);
    }

    if (sortFilter) {
        sortFilter.addEventListener('change', applyFilters);
    }

    // Create new discussion button
    const createBtn = document.querySelector('.btn-accent');
    if (createBtn) {
        createBtn.addEventListener('click', function(e) {
            if (this.textContent.includes('Create')) {
                window.location.href = 'create-thread.html';
            }
        });
    }
});

// Load discussions from API
async function loadDiscussions() {
    try {
        const discussionsList = document.getElementById('discussionsList');
        discussionsList.innerHTML = '<div class="loading-spinner">Loading discussions...</div>';

        // Mock data for demonstration
        const mockDiscussions = [
            {
                id: 1,
                title: 'Getting Started with Web Development',
                category: 'technology',
                author: 'John Doe',
                authorId: 1,
                createdAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
                description: 'A comprehensive guide to starting your web development journey. Learn HTML, CSS, and JavaScript basics.',
                replies: 15,
                views: 234,
                tags: ['web-dev', 'beginner', 'tutorial']
            },
            {
                id: 2,
                title: 'Best Practices for Database Design',
                category: 'technology',
                author: 'Jane Smith',
                authorId: 2,
                createdAt: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000),
                description: 'Discussion about normalization, indexing, and optimization techniques for database design.',
                replies: 8,
                views: 156,
                tags: ['database', 'sql', 'optimization']
            },
            {
                id: 3,
                title: 'Digital Marketing Strategies for 2025',
                category: 'business',
                author: 'Mike Johnson',
                authorId: 3,
                createdAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
                description: 'Share and discuss the latest digital marketing trends and strategies for the upcoming year.',
                replies: 22,
                views: 412,
                tags: ['marketing', 'digital', 'strategy']
            },
            {
                id: 4,
                title: 'Online Learning Platforms Review',
                category: 'education',
                author: 'Sarah Wilson',
                authorId: 4,
                createdAt: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000),
                description: 'Compare and review popular online learning platforms. Share your experiences and recommendations.',
                replies: 31,
                views: 567,
                tags: ['education', 'learning', 'review']
            },
            {
                id: 5,
                title: 'Health and Wellness Tips',
                category: 'health',
                author: 'Dr. Emily Brown',
                authorId: 5,
                createdAt: new Date(Date.now() - 4 * 24 * 60 * 60 * 1000),
                description: 'Discuss health tips, fitness routines, and wellness practices for a healthy lifestyle.',
                replies: 18,
                views: 289,
                tags: ['health', 'wellness', 'fitness']
            }
        ];

        allDiscussions = mockDiscussions;
        displayDiscussions(mockDiscussions);
        setupPagination(mockDiscussions.length);

    } catch (error) {
        console.error('Error loading discussions:', error);
        AlertManager.error('Failed to load discussions. Please try again.');
    }
}

// Display discussions
function displayDiscussions(discussions) {
    const discussionsList = document.getElementById('discussionsList');
    
    if (discussions.length === 0) {
        discussionsList.innerHTML = '<div class="empty-state"><p>No discussions found. Be the first to create one!</p></div>';
        return;
    }

    // Paginate discussions
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedDiscussions = discussions.slice(startIndex, endIndex);

    discussionsList.innerHTML = paginatedDiscussions.map(discussion => `
        <div class="discussion-item" onclick="viewThread(${discussion.id})">
            <div class="discussion-header">
                <h3 class="discussion-title">${escapeHtml(discussion.title)}</h3>
                <span class="discussion-category">${discussion.category}</span>
            </div>
            <div class="discussion-meta">
                <span class="discussion-meta-item">By ${escapeHtml(discussion.author)}</span>
                <span class="discussion-meta-item">${formatDate(discussion.createdAt)}</span>
            </div>
            <p class="discussion-description">${escapeHtml(discussion.description)}</p>
            <div class="discussion-footer">
                <div class="discussion-stats">
                    <span class="discussion-stat">
                        <span>Replies:</span>
                        <span>${discussion.replies}</span>
                    </span>
                    <span class="discussion-stat">
                        <span>Views:</span>
                        <span>${discussion.views}</span>
                    </span>
                </div>
                <div class="discussion-tags">
                    ${discussion.tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
                </div>
            </div>
        </div>
    `).join('');
}

// Setup pagination
function setupPagination(totalItems) {
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const paginationContainer = document.getElementById('paginationContainer');
    
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    let paginationHTML = '';

    // Previous button
    if (currentPage > 1) {
        paginationHTML += `<button class="pagination-btn" onclick="goToPage(${currentPage - 1})">Previous</button>`;
    }

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const isActive = i === currentPage ? 'active' : '';
        paginationHTML += `<button class="pagination-btn ${isActive}" onclick="goToPage(${i})">${i}</button>`;
    }

    // Next button
    if (currentPage < totalPages) {
        paginationHTML += `<button class="pagination-btn" onclick="goToPage(${currentPage + 1})">Next</button>`;
    }

    paginationContainer.innerHTML = paginationHTML;
}

// Go to page
function goToPage(page) {
    currentPage = page;
    displayDiscussions(allDiscussions);
    setupPagination(allDiscussions.length);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Handle search
function handleSearch(e) {
    const searchTerm = e.target.value.toLowerCase();
    
    if (searchTerm === '') {
        displayDiscussions(allDiscussions);
        setupPagination(allDiscussions.length);
        return;
    }

    const filtered = allDiscussions.filter(discussion =>
        discussion.title.toLowerCase().includes(searchTerm) ||
        discussion.description.toLowerCase().includes(searchTerm) ||
        discussion.tags.some(tag => tag.toLowerCase().includes(searchTerm))
    );

    currentPage = 1;
    displayDiscussions(filtered);
    setupPagination(filtered.length);
}

// Apply filters
function applyFilters() {
    const categoryFilter = document.getElementById('categoryFilter').value;
    const sortFilter = document.getElementById('sortFilter').value;

    let filtered = allDiscussions;

    // Category filter
    if (categoryFilter) {
        filtered = filtered.filter(d => d.category === categoryFilter);
    }

    // Sorting
    if (sortFilter === 'popular') {
        filtered.sort((a, b) => b.replies - a.replies);
    } else if (sortFilter === 'trending') {
        filtered.sort((a, b) => b.views - a.views);
    } else {
        filtered.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    }

    currentPage = 1;
    displayDiscussions(filtered);
    setupPagination(filtered.length);
}

// View thread
function viewThread(threadId) {
    window.location.href = `thread.html?id=${threadId}`;
}

// Utility Functions
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

function formatDate(date) {
    const now = new Date();
    const diffMs = now - new Date(date);
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;

    return new Date(date).toLocaleDateString();
}
