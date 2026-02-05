// e-forum-frontend/js/main.js
// Main JavaScript file for E-Forum frontend

// API Configuration
const API_BASE_URL = 'http://localhost:3000/api';
const API_TIMEOUT = 5000;

// Utility Functions
class APIClient {
    static async request(endpoint, options = {}) {
        const url = `${API_BASE_URL}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken') || ''}`
            },
            timeout: API_TIMEOUT
        };

        const mergedOptions = { ...defaultOptions, ...options };

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), mergedOptions.timeout);

            const response = await fetch(url, {
                ...mergedOptions,
                signal: controller.signal
            });

            clearTimeout(timeoutId);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    static async get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    }

    static async post(endpoint, data) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    static async put(endpoint, data) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(data)
        });
    }

    static async delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
}

// Alert/Notification System
class AlertManager {
    static show(message, type = 'info', duration = 3000) {
        const container = document.getElementById('alertContainer');
        if (!container) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <div class="alert-content">
                <span>${message}</span>
                <button class="alert-close">&times;</button>
            </div>
        `;

        container.appendChild(alert);

        const closeBtn = alert.querySelector('.alert-close');
        closeBtn.addEventListener('click', () => alert.remove());

        if (duration > 0) {
            setTimeout(() => alert.remove(), duration);
        }
    }

    static success(message) {
        this.show(message, 'success');
    }

    static error(message) {
        this.show(message, 'error', 5000);
    }

    static info(message) {
        this.show(message, 'info');
    }

    static warning(message) {
        this.show(message, 'warning');
    }
}

// Form Validation
class FormValidator {
    static validateEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    static validatePassword(password) {
        return password.length >= 8;
    }

    static validateUsername(username) {
        const usernameRegex = /^[a-zA-Z0-9_]{3,20}$/;
        return usernameRegex.test(username);
    }

    static validateRequired(value) {
        return value && value.trim().length > 0;
    }

    static validateMinLength(value, minLength) {
        return value && value.length >= minLength;
    }

    static validateMaxLength(value, maxLength) {
        return value && value.length <= maxLength;
    }
}

// Local Storage Management
class StorageManager {
    static setItem(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            console.error('Storage Error:', error);
        }
    }

    static getItem(key) {
        try {
            const item = localStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (error) {
            console.error('Storage Error:', error);
            return null;
        }
    }

    static removeItem(key) {
        try {
            localStorage.removeItem(key);
        } catch (error) {
            console.error('Storage Error:', error);
        }
    }

    static clear() {
        try {
            localStorage.clear();
        } catch (error) {
            console.error('Storage Error:', error);
        }
    }
}

// Authentication Management
class AuthManager {
    static isAuthenticated() {
        return !!localStorage.getItem('authToken');
    }

    static getAuthToken() {
        return localStorage.getItem('authToken');
    }

    static setAuthToken(token) {
        localStorage.setItem('authToken', token);
    }

    static getCurrentUser() {
        return StorageManager.getItem('currentUser');
    }

    static setCurrentUser(user) {
        StorageManager.setItem('currentUser', user);
    }

    static logout() {
        localStorage.removeItem('authToken');
        StorageManager.removeItem('currentUser');
        window.location.href = '../index.html';
    }

    static isAdmin() {
        const user = this.getCurrentUser();
        return user && user.role === 'admin';
    }

    static isModerator() {
        const user = this.getCurrentUser();
        return user && (user.role === 'moderator' || user.role === 'admin');
    }
}

// DOM Utilities
class DOMUtils {
    static getElementById(id) {
        return document.getElementById(id);
    }

    static querySelector(selector) {
        return document.querySelector(selector);
    }

    static querySelectorAll(selector) {
        return document.querySelectorAll(selector);
    }

    static addClass(element, className) {
        if (element) element.classList.add(className);
    }

    static removeClass(element, className) {
        if (element) element.classList.remove(className);
    }

    static toggleClass(element, className) {
        if (element) element.classList.toggle(className);
    }

    static hasClass(element, className) {
        return element && element.classList.contains(className);
    }

    static setHTML(element, html) {
        if (element) element.innerHTML = html;
    }

    static setText(element, text) {
        if (element) element.textContent = text;
    }

    static addEventListener(element, event, handler) {
        if (element) element.addEventListener(event, handler);
    }

    static removeEventListener(element, event, handler) {
        if (element) element.removeEventListener(event, handler);
    }
}

// Initialize Global Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Logout button functionality
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            AuthManager.logout();
        });
    }

    // Check authentication on protected pages
    const protectedPages = ['dashboard.html', 'categories.html', 'profile.html', 'admin.html', 'thread.html', 'create-thread.html'];
    const currentPage = window.location.pathname.split('/').pop();
    
    if (protectedPages.includes(currentPage) && !AuthManager.isAuthenticated()) {
        window.location.href = 'login.php';
    }

    // Alert container styles
    const style = document.createElement('style');
    style.textContent = `
        #alertContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            animation: slideIn 0.3s ease-out;
        }

        .alert-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .alert-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .alert-close:hover {
            opacity: 1;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
});

// Export classes for use in other scripts
window.APIClient = APIClient;
window.AlertManager = AlertManager;
window.FormValidator = FormValidator;
window.StorageManager = StorageManager;
window.AuthManager = AuthManager;
window.DOMUtils = DOMUtils;
