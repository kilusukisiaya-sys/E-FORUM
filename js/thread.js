// e-forum-frontend/js/thread.js
// Thread/Discussion related JavaScript

let threadId = null;
let threadData = null;

document.addEventListener('DOMContentLoaded', function() {
    // Get thread ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    threadId = urlParams.get('id');

    if (!threadId) {
        AlertManager.error('Invalid thread ID');
        setTimeout(() => window.location.href = 'dashboard.html', 2000);
        return;
    }

    loadThread();
    loadReplies();

    // Reply form handler
    const replyForm = document.getElementById('replyForm');
    if (replyForm) {
        replyForm.addEventListener('submit', handleReplySubmit);
    }
});

// Load thread data
async function loadThread() {
    try {
        // Mock thread data
        const mockThread = {
            id: threadId,
            title: 'Getting Started with Web Development',
            category: 'technology',
            author: 'John Doe',
            authorId: 1,
            authorAvatar: 'JD',
            createdAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
            content: 'Web development is an exciting field that combines creativity with technical skills. In this discussion, I want to share some fundamental concepts and best practices for beginners.',
            views: 234,
            replies: 15,
            tags: ['web-dev', 'beginner', 'tutorial']
        };

        threadData = mockThread;
        displayThreadHeader();
        displayOriginalPost();

    } catch (error) {
        console.error('Error loading thread:', error);
        AlertManager.error('Failed to load thread. Please try again.');
    }
}

// Display thread header
function displayThreadHeader() {
    const threadHeader = document.getElementById('threadHeader');
    
    threadHeader.innerHTML = `
        <h1>${escapeHtml(threadData.title)}</h1>
        <div class="thread-header-meta">
            <span>Posted by ${escapeHtml(threadData.author)} on ${formatDate(threadData.createdAt)}</span>
        </div>
    `;

    // Update thread info bar
    document.getElementById('threadCategory').textContent = threadData.category;
    document.getElementById('threadAuthor').textContent = threadData.author;
    document.getElementById('threadCreated').textContent = formatDate(threadData.createdAt);
    document.getElementById('threadReplies').textContent = threadData.replies;
}

// Display original post
function displayOriginalPost() {
    const originalPost = document.getElementById('originalPost');
    
    originalPost.innerHTML = `
        <div class="post-container">
            <div class="post-header">
                <div class="post-author-info">
                    <div class="post-avatar">${threadData.authorAvatar}</div>
                    <div class="post-author-details">
                        <h4>${escapeHtml(threadData.author)}</h4>
                        <p>Member since 2023</p>
                    </div>
                </div>
                <div class="post-actions">
                    <button class="post-action-btn">Report</button>
                    <button class="post-action-btn">Share</button>
                </div>
            </div>
            <div class="post-content">
                ${escapeHtml(threadData.content)}
            </div>
            <div class="post-footer">
                <div class="post-stats">
                    <span>Views: ${threadData.views}</span>
                </div>
            </div>
        </div>
    `;
}

// Load replies
async function loadReplies() {
    try {
        // Mock replies data
        const mockReplies = [
            {
                id: 1,
                author: 'Jane Smith',
                authorId: 2,
                authorAvatar: 'JS',
                content: 'Great introduction! I would also recommend learning about responsive design early on.',
                createdAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
                likes: 5
            },
            {
                id: 2,
                author: 'Mike Johnson',
                authorId: 3,
                authorAvatar: 'MJ',
                content: 'Thanks for sharing! This is really helpful for beginners. Do you have any recommendations for learning resources?',
                createdAt: new Date(Date.now() - 12 * 60 * 60 * 1000),
                likes: 3
            },
            {
                id: 3,
                author: 'Sarah Wilson',
                authorId: 4,
                authorAvatar: 'SW',
                content: 'I started learning web development last month and this thread is exactly what I needed. Looking forward to more posts!',
                createdAt: new Date(Date.now() - 6 * 60 * 60 * 1000),
                likes: 8
            }
        ];

        displayReplies(mockReplies);

    } catch (error) {
        console.error('Error loading replies:', error);
        AlertManager.error('Failed to load replies. Please try again.');
    }
}

// Display replies
function displayReplies(replies) {
    const repliesList = document.getElementById('repliesList');
    
    if (replies.length === 0) {
        repliesList.innerHTML = '<p class="text-center">No replies yet. Be the first to reply!</p>';
        return;
    }

    repliesList.innerHTML = replies.map(reply => `
        <div class="reply-item">
            <div class="post-header">
                <div class="post-author-info">
                    <div class="post-avatar">${reply.authorAvatar}</div>
                    <div class="post-author-details">
                        <h4>${escapeHtml(reply.author)}</h4>
                        <p>${formatDate(reply.createdAt)}</p>
                    </div>
                </div>
                <div class="post-actions">
                    <button class="post-action-btn">Like (${reply.likes})</button>
                    <button class="post-action-btn">Reply</button>
                </div>
            </div>
            <div class="post-content">
                ${escapeHtml(reply.content)}
            </div>
        </div>
    `).join('');
}

// Handle reply submission
async function handleReplySubmit(e) {
    e.preventDefault();

    const replyContent = document.getElementById('replyContent').value.trim();

    // Validation
    if (!FormValidator.validateRequired(replyContent)) {
        showError('replyContentError', 'Reply content is required');
        return;
    }

    if (!FormValidator.validateMinLength(replyContent, 10)) {
        showError('replyContentError', 'Reply must be at least 10 characters long');
        return;
    }

    try {
        // Show loading state
        const submitBtn = document.querySelector('#replyForm button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Posting...';

        // API Call
        const response = await APIClient.post(`/threads/${threadId}/replies`, {
            content: replyContent
        });

        AlertManager.success('Reply posted successfully!');
        
        // Clear form
        document.getElementById('replyForm').reset();
        clearErrors();

        // Reload replies
        loadReplies();

        // Reset button
        submitBtn.disabled = false;
        submitBtn.textContent = 'Post Reply';

    } catch (error) {
        console.error('Error posting reply:', error);
        AlertManager.error('Failed to post reply. Please try again.');
        
        const submitBtn = document.querySelector('#replyForm button[type="submit"]');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Post Reply';
    }
}

// Utility functions
function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.textContent = '';
        element.style.display = 'none';
    });
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
