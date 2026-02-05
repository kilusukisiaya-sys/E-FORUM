// e-forum-frontend/js/create-thread.js
// Create Thread Page JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initializeCreateThreadPage();
});

function initializeCreateThreadPage() {
    // Initialize character counters
    const titleInput = document.getElementById('title');
    const contentInput = document.getElementById('content');
    
    if (titleInput) {
        titleInput.addEventListener('input', updateTitleCharCount);
        updateTitleCharCount();
    }
    
    if (contentInput) {
        contentInput.addEventListener('input', updateContentCharCount);
        updateContentCharCount();
    }
    
    // Form submission handler
    const createThreadForm = document.getElementById('createThreadForm');
    if (createThreadForm) {
        createThreadForm.addEventListener('submit', handleCreateThread);
    }
    
    // Initialize tag suggestions
    initializeTagSuggestions();
    
    // Add click handler for modal background
    const modal = document.getElementById('previewModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closePreview();
            }
        });
    }
}

// Character count functions
function updateTitleCharCount() {
    const titleInput = document.getElementById('title');
    const charCount = document.getElementById('titleCharCount');
    if (titleInput && charCount) {
        const count = titleInput.value.length;
        charCount.textContent = `${count}/100`;
        
        // Update color based on count
        if (count > 90) {
            charCount.style.color = '#f59e0b'; // warning
        } else if (count > 100) {
            charCount.style.color = '#ef4444'; // danger
        } else {
            charCount.style.color = '#94a3b8'; // medium-gray
        }
    }
}

function updateContentCharCount() {
    const contentInput = document.getElementById('content');
    const charCount = document.getElementById('contentCharCount');
    if (contentInput && charCount) {
        const count = contentInput.value.length;
        charCount.textContent = `${count}/5000`;
        
        // Update color based on count
        if (count < 20) {
            charCount.style.color = '#ef4444'; // danger
        } else if (count > 4000) {
            charCount.style.color = '#f59e0b'; // warning
        } else {
            charCount.style.color = '#94a3b8'; // medium-gray
        }
    }
}

// Tag suggestions
function initializeTagSuggestions() {
    const tagsInput = document.getElementById('tags');
    if (!tagsInput) return;

    const popularTags = [
        'javascript', 'php', 'python', 'html', 'css',
        'react', 'vue', 'angular', 'nodejs', 'express',
        'mysql', 'mongodb', 'database', 'api', 'web',
        'programming', 'help', 'tutorial', 'question', 'discussion'
    ];

    let currentSuggestions = [];
    let activeSuggestionIndex = -1;

    tagsInput.addEventListener('input', function(e) {
        const value = e.target.value.trim();
        if (!value || !value.includes(',')) {
            hideTagSuggestions();
            return;
        }

        const lastPart = value.split(',').pop().trim();
        if (lastPart.length === 0) return;

        const suggestions = popularTags.filter(tag => 
            tag.startsWith(lastPart.toLowerCase()) && 
            !value.toLowerCase().includes(tag)
        );

        if (suggestions.length > 0) {
            currentSuggestions = suggestions;
            showTagSuggestions(suggestions, lastPart);
        } else {
            hideTagSuggestions();
        }
    });

    tagsInput.addEventListener('keydown', function(e) {
        if (!currentSuggestions.length) return;

        const suggestionsContainer = document.getElementById('tagSuggestions');
        if (!suggestionsContainer) return;

        const suggestions = suggestionsContainer.querySelectorAll('.tag-suggestion-item');
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                activeSuggestionIndex = (activeSuggestionIndex + 1) % currentSuggestions.length;
                updateActiveSuggestion(suggestions);
                break;
            case 'ArrowUp':
                e.preventDefault();
                activeSuggestionIndex = (activeSuggestionIndex - 1 + currentSuggestions.length) % currentSuggestions.length;
                updateActiveSuggestion(suggestions);
                break;
            case 'Enter':
                e.preventDefault();
                if (activeSuggestionIndex >= 0) {
                    selectTagSuggestion(currentSuggestions[activeSuggestionIndex]);
                }
                break;
            case 'Escape':
                hideTagSuggestions();
                break;
        }
    });

    function showTagSuggestions(suggestions, lastPart) {
        hideTagSuggestions();
        
        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.id = 'tagSuggestions';
        suggestionsContainer.className = 'tag-suggestions';
        
        suggestions.forEach((tag, index) => {
            const item = document.createElement('div');
            item.className = 'tag-suggestion-item';
            item.textContent = tag;
            item.dataset.index = index;
            
            item.addEventListener('click', function() {
                selectTagSuggestion(tag);
            });
            
            item.addEventListener('mouseover', function() {
                suggestions.forEach(el => el.classList.remove('active'));
                this.classList.add('active');
                activeSuggestionIndex = parseInt(this.dataset.index);
            });
            
            suggestionsContainer.appendChild(item);
        });
        
        tagsInput.parentNode.appendChild(suggestionsContainer);
        activeSuggestionIndex = -1;
        
        // Add CSS for tag suggestions
        if (!document.querySelector('#tagSuggestionsStyle')) {
            const style = document.createElement('style');
            style.id = 'tagSuggestionsStyle';
            style.textContent = `
                .tag-suggestions {
                    position: absolute;
                    background: white;
                    border: 2px solid #2563eb;
                    border-radius: 10px;
                    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
                    z-index: 1000;
                    max-height: 200px;
                    overflow-y: auto;
                    width: calc(100% - 4px);
                    margin-top: 5px;
                }
                
                .tag-suggestion-item {
                    padding: 0.75rem 1rem;
                    cursor: pointer;
                    border-bottom: 1px solid #e2e8f0;
                    transition: all 0.2s ease;
                }
                
                .tag-suggestion-item:hover,
                .tag-suggestion-item.active {
                    background: #eff6ff;
                    color: #2563eb;
                }
                
                .tag-suggestion-item:last-child {
                    border-bottom: none;
                }
            `;
            document.head.appendChild(style);
        }
    }

    function hideTagSuggestions() {
        const existing = document.getElementById('tagSuggestions');
        if (existing) {
            existing.remove();
        }
        currentSuggestions = [];
        activeSuggestionIndex = -1;
    }

    function updateActiveSuggestion(suggestions) {
        suggestions.forEach(item => item.classList.remove('active'));
        if (activeSuggestionIndex >= 0) {
            suggestions[activeSuggestionIndex].classList.add('active');
        }
    }

    function selectTagSuggestion(tag) {
        const currentValue = tagsInput.value.trim();
        const parts = currentValue.split(',');
        parts[parts.length - 1] = tag;
        
        if (parts.length > 1) {
            parts.push('');
        }
        
        tagsInput.value = parts.join(', ') + (parts.length > 1 ? '' : ', ');
        hideTagSuggestions();
        tagsInput.focus();
    }
}

// Text formatting for editor
function formatText(format) {
    const contentInput = document.getElementById('content');
    if (!contentInput) return;

    const start = contentInput.selectionStart;
    const end = contentInput.selectionEnd;
    const selectedText = contentInput.value.substring(start, end);
    let formattedText = '';

    switch(format) {
        case 'bold':
            formattedText = `**${selectedText}**`;
            break;
        case 'italic':
            formattedText = `*${selectedText}*`;
            break;
        case 'link':
            formattedText = `[${selectedText}](url)`;
            break;
        case 'code':
            formattedText = `\`${selectedText}\``;
            break;
        case 'quote':
            formattedText = `> ${selectedText}`;
            break;
        default:
            return;
    }

    const newValue = contentInput.value.substring(0, start) + formattedText + contentInput.value.substring(end);
    contentInput.value = newValue;
    
    // Restore cursor position
    const newPosition = start + formattedText.length;
    contentInput.setSelectionRange(newPosition, newPosition);
    contentInput.focus();
    
    updateContentCharCount();
}

// Preview functionality
function previewThread() {
    const title = document.getElementById('title').value.trim();
    const content = document.getElementById('content').value.trim();
    const category = document.getElementById('category');
    const categoryText = category.options[category.selectedIndex]?.text || 'Uncategorized';
    
    if (!title || !content) {
        showAlert('Please fill in title and content to preview', 'warning');
        return;
    }

    const previewContent = document.getElementById('previewContent');
    previewContent.innerHTML = `
        <div class="preview-header">
            <h2>${escapeHtml(title)}</h2>
            <div class="preview-meta">
                <span><i class="fas fa-folder"></i> ${escapeHtml(categoryText)}</span>
                <span><i class="fas fa-user"></i> ${getCurrentUsername()}</span>
                <span><i class="fas fa-clock"></i> Just now</span>
            </div>
        </div>
        <div class="preview-body">
            ${formatMarkdown(escapeHtml(content))}
        </div>
    `;

    const modal = document.getElementById('previewModal');
    modal.style.display = 'flex';
}

function closePreview() {
    const modal = document.getElementById('previewModal');
    modal.style.display = 'none';
}

// Helper functions
function formatMarkdown(text) {
    return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.*?)\*/g, '<em>$1</em>')
        .replace(/`(.*?)`/g, '<code>$1</code>')
        .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank">$1</a>')
        .replace(/^> (.*$)/gm, '<blockquote>$1</blockquote>')
        .replace(/\n/g, '<br>');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getCurrentUsername() {
    // Try to get username from user-info element
    const userInfo = document.querySelector('.user-info span');
    if (userInfo) {
        const text = userInfo.textContent || userInfo.innerText;
        const match = text.match(/User|TestUser|[\w\s]+/);
        return match ? match[0].trim() : 'User';
    }
    return 'User';
}

function showAlert(message, type = 'info') {
    // Remove existing alerts
    const existingAlert = document.querySelector('.custom-alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alert = document.createElement('div');
    alert.className = `custom-alert alert-${type}`;
    alert.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    document.body.appendChild(alert);
    
    // Add CSS for alert
    if (!document.querySelector('#alertStyle')) {
        const style = document.createElement('style');
        style.id = 'alertStyle';
        style.textContent = `
            .custom-alert {
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
                max-width: 400px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            }
            
            .alert-success { background: #10b981; }
            .alert-error { background: #ef4444; }
            .alert-warning { background: #f59e0b; }
            .alert-info { background: #3b82f6; }
            
            .custom-alert button {
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0;
                line-height: 1;
            }
            
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

// Main form handler
async function handleCreateThread(e) {
    e.preventDefault();

    const title = document.getElementById('title').value.trim();
    const category = document.getElementById('category').value;
    const content = document.getElementById('content').value.trim();
    const tags = document.getElementById('tags').value.trim();
    const isPinned = document.getElementById('isPinned')?.checked || false;
    const isLocked = document.getElementById('isLocked')?.checked || false;

    // Clear previous errors
    clearErrors();

    // Validation
    let isValid = true;

    // Title validation
    if (!title) {
        showError('titleError', 'Title is required');
        isValid = false;
    } else if (title.length < 5) {
        showError('titleError', 'Title must be at least 5 characters long');
        isValid = false;
    } else if (title.length > 100) {
        showError('titleError', 'Title must not exceed 100 characters');
        isValid = false;
    }

    // Category validation
    if (!category) {
        showError('categoryError', 'Please select a category');
        isValid = false;
    }

    // Content validation
    if (!content) {
        showError('contentError', 'Content is required');
        isValid = false;
    } else if (content.length < 20) {
        showError('contentError', 'Content must be at least 20 characters long');
        isValid = false;
    } else if (content.length > 5000) {
        showError('contentError', 'Content must not exceed 5000 characters');
        isValid = false;
    }

    // Tags validation
    if (tags) {
        const tagArray = tags.split(',').map(tag => tag.trim()).filter(tag => tag.length > 0);
        if (tagArray.length > 5) {
            showError('tagsError', 'Maximum 5 tags allowed');
            isValid = false;
        }
        
        for (const tag of tagArray) {
            if (tag.length > 20) {
                showError('tagsError', `Tag "${tag.substring(0, 20)}..." is too long (max 20 characters)`);
                isValid = false;
                break;
            }
        }
    }

    if (!isValid) {
        showAlert('Please fix the errors in the form', 'error');
        return;
    }

    try {
        // Show loading state
        const submitBtn = document.querySelector('#createThreadForm button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Discussion...';

        // Parse tags
        const tagArray = tags ? 
            tags.split(',')
                .map(tag => tag.trim())
                .filter(tag => tag.length > 0 && tag.length <= 20)
                .slice(0, 5) : [];

        // Prepare data for API
        const threadData = {
            title: title,
            category: category,
            content: content,
            tags: tagArray
        };

        // For development - simulate API call
        // In production, replace with actual API call
        await simulateApiCall(threadData);

        showAlert('Discussion created successfully! Redirecting...', 'success');
        
        // Simulate redirect
        setTimeout(() => {
            // In production: window.location.href = `thread.php?id=${response.id}`;
            window.location.href = 'index.php?created=true';
        }, 1500);

    } catch (error) {
        console.error('Error creating thread:', error);
        showAlert('Failed to create discussion. Please try again.', 'error');
        
        // Reset button
        const submitBtn = document.querySelector('#createThreadForm button[type="submit"]');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Create Discussion';
    }
}

// Simulate API call for development
async function simulateApiCall(data) {
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            // Simulate random success/failure for testing
            const shouldFail = Math.random() < 0.1; // 10% chance of failure
            
            if (shouldFail) {
                reject(new Error('Simulated API failure'));
            } else {
                resolve({ id: Math.floor(Math.random() * 1000) + 1 });
            }
        }, 1000);
    });
}

// Helper functions
function showError(elementId, message) {
    const errorElement = document.getElementById(elementId);
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        
        // Scroll to error
        errorElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function clearErrors() {
    const errorElements = document.querySelectorAll('.error-message');
    errorElements.forEach(element => {
        element.textContent = '';
        element.style.display = 'none';
    });
}