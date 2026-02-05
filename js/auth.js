// e-forum-frontend/js/auth.js
// Authentication related JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Login Form Handler
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }

    // Register Form Handler
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }

    // Social Login Handlers
    const googleLogin = document.getElementById('googleLogin');
    const facebookLogin = document.getElementById('facebookLogin');
    const googleSignup = document.getElementById('googleSignup');
    const facebookSignup = document.getElementById('facebookSignup');

    if (googleLogin) googleLogin.addEventListener('click', handleGoogleLogin);
    if (facebookLogin) facebookLogin.addEventListener('click', handleFacebookLogin);
    if (googleSignup) googleSignup.addEventListener('click', handleGoogleSignup);
    if (facebookSignup) facebookSignup.addEventListener('click', handleFacebookSignup);
});

// Login Handler
async function handleLogin(e) {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const rememberMe = document.getElementById('rememberMe').checked;

    // Clear previous errors
    clearErrors();

    // Validation
    if (!FormValidator.validateEmail(email)) {
        showError('emailError', 'Please enter a valid email address');
        return;
    }

    if (!FormValidator.validateRequired(password)) {
        showError('passwordError', 'Password is required');
        return;
    }

    try {
        // Show loading state
        const submitBtn = document.querySelector('#loginForm button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Signing in...';

        // API Call
        const response = await APIClient.post('/auth/login', {
            email,
            password,
            rememberMe
        });

        // Store auth token and user data
        AuthManager.setAuthToken(response.token);
        AuthManager.setCurrentUser(response.user);

        AlertManager.success('Login successful! Redirecting...');
        
        // Redirect to dashboard
        setTimeout(() => {
            window.location.href = 'dashboard.html';
        }, 1500);

    } catch (error) {
        console.error('Login error:', error);
        AlertManager.error('Login failed. Please check your credentials and try again.');
        
        const submitBtn = document.querySelector('#loginForm button[type="submit"]');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Sign In';
    }
}

// Register Handler
async function handleRegister(e) {
    e.preventDefault();

    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const bio = document.getElementById('bio').value.trim();
    const termsAccepted = document.getElementById('termsAccepted').checked;

    // Clear previous errors
    clearErrors();

    // Validation
    let isValid = true;

    if (!FormValidator.validateRequired(firstName)) {
        showError('firstNameError', 'First name is required');
        isValid = false;
    }

    if (!FormValidator.validateRequired(lastName)) {
        showError('lastNameError', 'Last name is required');
        isValid = false;
    }

    if (!FormValidator.validateUsername(username)) {
        showError('usernameError', 'Username must be 3-20 characters (letters, numbers, underscore only)');
        isValid = false;
    }

    if (!FormValidator.validateEmail(email)) {
        showError('emailError', 'Please enter a valid email address');
        isValid = false;
    }

    if (!FormValidator.validatePassword(password)) {
        showError('passwordError', 'Password must be at least 8 characters long');
        isValid = false;
    }

    if (password !== confirmPassword) {
        showError('confirmPasswordError', 'Passwords do not match');
        isValid = false;
    }

    if (!termsAccepted) {
        showError('termsError', 'You must accept the terms and conditions');
        isValid = false;
    }

    if (!isValid) return;

    try {
        // Show loading state
        const submitBtn = document.querySelector('#registerForm button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating Account...';

        // API Call
        const response = await APIClient.post('/auth/register', {
            firstName,
            lastName,
            username,
            email,
            password,
            bio
        });

        // Store auth token and user data
        AuthManager.setAuthToken(response.token);
        AuthManager.setCurrentUser(response.user);

        AlertManager.success('Account created successfully! Redirecting...');
        
        // Redirect to dashboard
        setTimeout(() => {
            window.location.href = 'dashboard.html';
        }, 1500);

    } catch (error) {
        console.error('Registration error:', error);
        
        if (error.message && error.message.includes('Email already exists')) {
            showError('emailError', 'Email already registered');
        } else if (error.message && error.message.includes('Username already exists')) {
            showError('usernameError', 'Username already taken');
        } else {
            AlertManager.error('Registration failed. Please try again.');
        }
        
        const submitBtn = document.querySelector('#registerForm button[type="submit"]');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Account';
    }
}

// Social Login Handlers
function handleGoogleLogin() {
    AlertManager.info('Google login integration coming soon');
    // Integration with Google OAuth would go here
}

function handleFacebookLogin() {
    AlertManager.info('Facebook login integration coming soon');
    // Integration with Facebook OAuth would go here
}

function handleGoogleSignup() {
    AlertManager.info('Google signup integration coming soon');
    // Integration with Google OAuth would go here
}

function handleFacebookSignup() {
    AlertManager.info('Facebook signup integration coming soon');
    // Integration with Facebook OAuth would go here
}

// Helper Functions
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
