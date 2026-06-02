/* BetLedger - Main JavaScript */

// Set active nav item based on current URL
function setActiveNavItem() {
    const currentPath = window.location.pathname;
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
        item.classList.remove('active');
        const href = item.getAttribute('href');
        
        // Match logic for navigation
        if (currentPath === '/' && href === '/') {
            item.classList.add('active');
        } else if (currentPath !== '/' && href !== '/' && currentPath.startsWith(href)) {
            item.classList.add('active');
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    setActiveNavItem();
    initializeEventListeners();
    initializeFormValidation();
});

function initializeEventListeners() {
    // Auto-calculate potential return on bet forms
    const oddsInput = document.getElementById('odds');
    const stakeInput = document.getElementById('stake');
    
    if (oddsInput && stakeInput) {
        [oddsInput, stakeInput].forEach(input => {
            input.addEventListener('change', calculatePotentialReturn);
            input.addEventListener('keyup', calculatePotentialReturn);
        });
    }
    
    // Status filter on bet list
    const statusSelect = document.getElementById('status-filter');
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            document.querySelector('.filters-form').submit();
        });
    }
}

function calculatePotentialReturn() {
    const oddsInput = document.getElementById('odds');
    const stakeInput = document.getElementById('stake');
    const potentialReturnInput = document.getElementById('potential_return');
    
    if (oddsInput && stakeInput && potentialReturnInput) {
        const odds = parseFloat(oddsInput.value) || 0;
        const stake = parseFloat(stakeInput.value) || 0;
        const potentialReturn = (odds * stake).toFixed(2);
        potentialReturnInput.value = potentialReturn;
    }
}

function initializeFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Add custom validation if needed
            const passwordField = form.querySelector('input[type="password"]');
            if (passwordField && passwordField.name === 'password') {
                const passwordConfirmField = form.querySelector('input[name="password_confirm"]');
                if (passwordConfirmField && passwordField.value !== passwordConfirmField.value) {
                    e.preventDefault();
                    showNotification('error', 'Passwords do not match');
                    return false;
                }
            }
        });
    });
}

function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type}`;
    notification.textContent = message;
    
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(notification, mainContent.firstChild);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 4000);
    }
}

// Format currency display
function formatCurrency(amount, currency = 'USD') {
    const formatter = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    return formatter.format(amount);
}

// Delete with confirmation
function confirmDelete() {
    return confirm('Are you sure you want to delete this item? This action cannot be undone.');
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Search functionality
const searchInput = document.querySelector('input[name="search"]');
if (searchInput) {
    searchInput.addEventListener('input', debounce(function() {
        // Submit form after user stops typing for 500ms
        this.form.submit();
    }, 500));
}


