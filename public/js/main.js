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
/* BetLedger - Main JavaScript */

document.addEventListener(`DOMContentLoaded`, function() {
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

// Convert decimal odds to fractional
function decimalToFractional(decimal) {
    const whole = Math.floor(decimal);
    const fraction = decimal - whole;
    
    let numerator = Math.round(fraction * 1000);
    let denominator = 1000;
    
    const gcd = (a, b) => b === 0 ? a : gcd(b, a % b);
    const divisor = gcd(numerator, denominator);
    numerator /= divisor;
    denominator /= divisor;
    
    if (whole > 0) {
        return `${whole} ${numerator}/${denominator}`;
    }
    return `${numerator}/${denominator}`;
}

// Convert decimal odds to American
function decimalToAmerican(decimal) {
    if (decimal >= 2) {
        return `+${Math.round((decimal - 1) * 100)}`;
    } else {
        return `${Math.round(-100 / (decimal - 1))}`;
    }
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        let csvRow = [];
        cols.forEach(col => {
            csvRow.push('"' + col.innerText.replace(/"/g, '""') + '"');
        });
        csv.push(csvRow.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(csv, filename) {
    const link = document.createElement('a');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    link.href = URL.createObjectURL(blob);
    link.download = filename || 'export.csv';
    link.click();
}

// Delete with confirmation
function confirmDelete() {
    return confirm('Are you sure you want to delete this item? This action cannot be undone.');
}

// Loading state on form submit
function setLoadingState(button, isLoading) {
    if (!button) return;
    
    if (isLoading) {
        button.disabled = true;
        button.dataset.originalText = button.textContent;
        button.textContent = 'Loading...';
    } else {
        button.disabled = false;
        button.textContent = button.dataset.originalText || 'Submit';
    }
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

// Tooltip support
document.querySelectorAll('[data-tooltip]').forEach(element => {
    element.addEventListener('mouseenter', function() {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = this.getAttribute('data-tooltip');
        document.body.appendChild(tooltip);
        
        const rect = this.getBoundingClientRect();
        tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
        tooltip.style.left = (rect.left + (rect.width - tooltip.offsetWidth) / 2) + 'px';
    });
    
    element.addEventListener('mouseleave', function() {
        const tooltip = document.querySelector('.tooltip');
        if (tooltip) tooltip.remove();
    });
});


