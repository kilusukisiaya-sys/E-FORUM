// e-forum-frontend/js/profile.js
// User profile related JavaScript

document.addEventListener('DOMContentLoaded', function() {
    loadUserProfile();
    setupTabNavigation();
    setupModalHandlers();
});

// Load user profile
async function loadUserProfile() {
    try {
        const currentUser = AuthManager.getCurrentUser();
        
        if (!currentUser) {
            AlertManager.error('User data not found');
            return;
        }

        // Mock user data
        const userData = {
            ...currentUser,
            avatar: currentUser.firstName.charAt(0) + currentUser.lastName.charAt(0),
            bio: 'Web developer and tech enthusiast',
            threadCount: 12,
            replyCount: 45,
            memberSince: 'January 2024'
        };

        displayProfileHeader(userData);
        loadUserDiscussions();
        loadUserReplies();
        loadUserActivity();

    } catch (error) {
        console.error('Error loading profile:', error);
        AlertManager.error('Failed to load profile. Please try again.');
    }
}

// Display profile header
function displayProfileHeader(userData) {
    document.getElementById('profileAvatar').textContent = userData.avatar;
    document.getElementById('profileName').textContent = `${userData.firstName} ${userData.lastName}`;
    document.getElementById('profileUsername').textContent = `@${userData.username}`;
    document.getElementById('profileBio').textContent = userData.bio;
    document.getElementById('threadCount').textContent = userData.threadCount;
    document.getElementById('replyCount').textContent = userData.replyCount;
    document.getElementById('memberSince').textContent = userData.memberSince;

    // Setup edit profile button
    const editProfileBtn = document.getElementById('editProfileBtn');
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', openEditProfileModal);
    }

    // Setup settings button
    const settingsBtn = document.getElementById('settingsBtn');
    if (settingsBtn) {
        settingsBtn.addEventListener('click', openSettingsModal);
    }
}

// Load user discussions
async function loadUserDiscussions() {
    try {
        // Mock user discussions
        const mockDiscussions = [
            {
                id: 1,
                title: 'Getting Started with Web Development',
                category: 'technology',
                createdAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
                replies: 15,
                views: 234
            },
            {
                id: 2,
                title: 'Best JavaScript Frameworks',
                category: 'technology',
                createdAt: new Date(Date.now() - 5 * 24 * 60 * 60 * 1000),
                replies: 8,
                views: 156
            }
        ];

        displayUserDiscussions(mockDiscussions);

    } catch (error) {
        console.error('Error loading user discussions:', error);
    }
}

// Display user discussions
function displayUserDiscussions(discussions) {
    const userDiscussionsList = document.getElementById('userDiscussionsList');
    
    if (discussions.length === 0) {
        userDiscussionsList.innerHTML = '<p>No discussions yet.</p>';
        return;
    }

    userDiscussionsList.innerHTML = discussions.map(discussion => `
        <div class="discussion-item" onclick="window.location.href='thread.html?id=${discussion.id}'">
            <h4>${escapeHtml(discussion.title)}</h4>
            <p class="text-light">${formatDate(discussion.createdAt)} | ${discussion.replies} replies | ${discussion.views} views</p>
        </div>
    `).join('');
}

// Load user replies
async function loadUserReplies() {
    try {
        // Mock user replies
        const mockReplies = [
            {
                id: 1,
                threadTitle: 'Getting Started with Web Development',
                content: 'Great introduction! I would also recommend learning about responsive design early on.',
                createdAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
                likes: 5
            }
        ];

        displayUserReplies(mockReplies);

    } catch (error) {
        console.error('Error loading user replies:', error);
    }
}

// Display user replies
function displayUserReplies(replies) {
    const userRepliesList = document.getElementById('userRepliesList');
    
    if (replies.length === 0) {
        userRepliesList.innerHTML = '<p>No replies yet.</p>';
        return;
    }

    userRepliesList.innerHTML = replies.map(reply => `
        <div class="reply-item">
            <h4>${escapeHtml(reply.threadTitle)}</h4>
            <p>${escapeHtml(reply.content)}</p>
            <p class="text-light">${formatDate(reply.createdAt)} | ${reply.likes} likes</p>
        </div>
    `).join('');
}

// Load user activity
async function loadUserActivity() {
    try {
        // Mock user activity
        const mockActivity = [
            {
                type: 'thread_created',
                description: 'Created discussion: Getting Started with Web Development',
                createdAt: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000)
            },
            {
                type: 'reply_posted',
                description: 'Replied to: Best JavaScript Frameworks',
                createdAt: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000)
            }
        ];

        displayUserActivity(mockActivity);

    } catch (error) {
        console.error('Error loading user activity:', error);
    }
}

// Display user activity
function displayUserActivity(activity) {
    const userActivityList = document.getElementById('userActivityList');
    
    if (activity.length === 0) {
        userActivityList.innerHTML = '<p>No activity yet.</p>';
        return;
    }

    userActivityList.innerHTML = activity.map(item => `
        <div class="activity-item">
            <p>${item.description}</p>
            <p class="text-light">${formatDate(item.createdAt)}</p>
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

// Setup modal handlers
function setupModalHandlers() {
    const editProfileModal = document.getElementById('editProfileModal');
    const settingsModal = document.getElementById('settingsModal');
    const closeEditModal = document.getElementById('closeEditModal');
    const closeSettingsModal = document.getElementById('closeSettingsModal');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const cancelSettingsBtn = document.getElementById('cancelSettingsBtn');
    const editProfileForm = document.getElementById('editProfileForm');
    const saveSettingsBtn = document.getElementById('saveSettingsBtn');

    // Close edit profile modal
    if (closeEditModal) {
        closeEditModal.addEventListener('click', () => editProfileModal.classList.remove('active'));
    }
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', () => editProfileModal.classList.remove('active'));
    }

    // Close settings modal
    if (closeSettingsModal) {
        closeSettingsModal.addEventListener('click', () => settingsModal.classList.remove('active'));
    }
    if (cancelSettingsBtn) {
        cancelSettingsBtn.addEventListener('click', () => settingsModal.classList.remove('active'));
    }

    // Edit profile form submission
    if (editProfileForm) {
        editProfileForm.addEventListener('submit', handleEditProfile);
    }

    // Save settings
    if (saveSettingsBtn) {
        saveSettingsBtn.addEventListener('click', handleSaveSettings);
    }

    // Close modal when clicking outside
    if (editProfileModal) {
        editProfileModal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    }
    if (settingsModal) {
        settingsModal.addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('active');
        });
    }
}

// Open edit profile modal
function openEditProfileModal() {
    const currentUser = AuthManager.getCurrentUser();
    const modal = document.getElementById('editProfileModal');
    
    document.getElementById('editFirstName').value = currentUser.firstName;
    document.getElementById('editLastName').value = currentUser.lastName;
    document.getElementById('editBio').value = currentUser.bio || '';
    document.getElementById('editEmail').value = currentUser.email;
    
    modal.classList.add('active');
}

// Handle edit profile
async function handleEditProfile(e) {
    e.preventDefault();

    const firstName = document.getElementById('editFirstName').value.trim();
    const lastName = document.getElementById('editLastName').value.trim();
    const bio = document.getElementById('editBio').value.trim();
    const email = document.getElementById('editEmail').value.trim();

    try {
        const response = await APIClient.put('/profile', {
            firstName,
            lastName,
            bio,
            email
        });

        AuthManager.setCurrentUser(response.user);
        AlertManager.success('Profile updated successfully!');
        document.getElementById('editProfileModal').classList.remove('active');
        loadUserProfile();

    } catch (error) {
        console.error('Error updating profile:', error);
        AlertManager.error('Failed to update profile. Please try again.');
    }
}

// Open settings modal
function openSettingsModal() {
    document.getElementById('settingsModal').classList.add('active');
}

// Handle save settings
async function handleSaveSettings() {
    try {
        const publicProfile = document.getElementById('publicProfile').checked;
        const showActivity = document.getElementById('showActivity').checked;
        const emailNotifications = document.getElementById('emailNotifications').checked;
        const emailMentions = document.getElementById('emailMentions').checked;

        await APIClient.put('/settings', {
            publicProfile,
            showActivity,
            emailNotifications,
            emailMentions
        });

        AlertManager.success('Settings saved successfully!');
        document.getElementById('settingsModal').classList.remove('active');

    } catch (error) {
        console.error('Error saving settings:', error);
        AlertManager.error('Failed to save settings. Please try again.');
    }
}

// Utility functions
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
