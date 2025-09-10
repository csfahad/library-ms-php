/*
 * Main JavaScript
 * Handles client-side functionality and interactions
 */

// DOM Content Loaded Event
document.addEventListener("DOMContentLoaded", function () {
    initializeComponents();
});

/* Initialize all JavaScript components */
function initializeComponents() {
    initializeFormValidation();

    initializeModals();

    initializeSearch();

    initializeDataTables();

    initializeUIComponents();

    initializeSidebarToggle();
}

/* Form Validation */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[data-validate="true"]');

    forms.forEach((form) => {
        form.addEventListener("submit", function (e) {
            if (!validateForm(this)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });

        // Real-time validation
        const inputs = form.querySelectorAll("input, select, textarea");
        inputs.forEach((input) => {
            input.addEventListener("blur", function () {
                validateField(this);
            });

            input.addEventListener("input", function () {
                clearFieldError(this);
            });
        });
    });
}

/**
 * Validate entire form
 * @param {HTMLFormElement} form
 * @returns {boolean}
 */
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll(
        "input[required], select[required], textarea[required]"
    );

    inputs.forEach((input) => {
        if (!validateField(input)) {
            isValid = false;
        }
    });

    return isValid;
}

/**
 * Validate individual field
 * @param {HTMLElement} field
 * @returns {boolean}
 */
function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute("required");

    clearFieldError(field);

    if (required && !value) {
        showFieldError(field, "This field is required");
        return false;
    }

    if (!value && !required) {
        return true;
    }

    // Email validation
    if (type === "email" && !isValidEmail(value)) {
        showFieldError(field, "Please enter a valid email address");
        return false;
    }

    // Password validation
    if (type === "password" && field.name === "password") {
        const passwordValidation = validatePassword(value);
        if (!passwordValidation.valid) {
            showFieldError(field, passwordValidation.errors[0]);
            return false;
        }
    }

    // Confirm password validation
    if (field.name === "confirm_password") {
        const passwordField = document.querySelector('input[name="password"]');
        if (passwordField && value !== passwordField.value) {
            showFieldError(field, "Passwords do not match");
            return false;
        }
    }

    // Phone number validation
    if (field.name === "phone" && !isValidPhoneNumber(value)) {
        showFieldError(field, "Please enter a valid phone number");
        return false;
    }

    // ISBN validation
    if (field.name === "isbn" && value && !isValidISBN(value)) {
        showFieldError(field, "Please enter a valid ISBN");
        return false;
    }

    // Number validation
    if (type === "number" && isNaN(value)) {
        showFieldError(field, "Please enter a valid number");
        return false;
    }

    // Positive number validation
    if (
        (field.name === "quantity" || field.name === "price") &&
        parseFloat(value) < 0
    ) {
        showFieldError(field, "Please enter a positive number");
        return false;
    }

    // Show success
    showFieldSuccess(field);
    return true;
}

/**
 * Show field error
 * @param {HTMLElement} field
 * @param {string} message
 */
function showFieldError(field, message) {
    field.classList.add("is-invalid");
    field.classList.remove("is-valid");

    // Remove existing error message
    const existingError = field.parentNode.querySelector(".invalid-feedback");
    if (existingError) {
        existingError.remove();
    }

    // Add error message
    const errorDiv = document.createElement("div");
    errorDiv.className = "invalid-feedback";
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

/**
 * Show field success
 * @param {HTMLElement} field
 */
function showFieldSuccess(field) {
    field.classList.add("is-valid");
    field.classList.remove("is-invalid");
}

/**
 * Clear field error
 * @param {HTMLElement} field
 */
function clearFieldError(field) {
    field.classList.remove("is-invalid", "is-valid");

    const errorDiv = field.parentNode.querySelector(".invalid-feedback");
    if (errorDiv) {
        errorDiv.remove();
    }
}

/* Modal functionality */
function initializeModals() {
    // Open modal buttons
    document.querySelectorAll("[data-modal-target]").forEach((button) => {
        button.addEventListener("click", function (e) {
            e.preventDefault();
            const modalId = this.getAttribute("data-modal-target");
            openModal(modalId);
        });
    });

    // Close modal buttons
    document.querySelectorAll("[data-modal-close]").forEach((button) => {
        button.addEventListener("click", function () {
            const modal = this.closest(".modal");
            closeModal(modal);
        });
    });

    // Close modal when clicking outside
    document.querySelectorAll(".modal").forEach((modal) => {
        modal.addEventListener("click", function (e) {
            if (e.target === this) {
                closeModal(this);
            }
        });
    });
}

/**
 * Open modal
 * @param {string} modalId
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = "block";
        document.body.style.overflow = "hidden";

        // Focus on first input
        const firstInput = modal.querySelector("input, select, textarea");
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

/**
 * Close modal
 * @param {HTMLElement} modal
 */
function closeModal(modal) {
    modal.style.display = "none";
    document.body.style.overflow = "auto";

    // Reset form if exists
    const form = modal.querySelector("form");
    if (form) {
        resetForm(form);
    }
}

/**
 * Reset form
 * @param {HTMLFormElement} form
 */
function resetForm(form) {
    form.reset();

    // Clear validation classes
    form.querySelectorAll(".is-invalid, .is-valid").forEach((field) => {
        field.classList.remove("is-invalid", "is-valid");
    });

    // Remove error messages
    form.querySelectorAll(".invalid-feedback").forEach((error) => {
        error.remove();
    });
}

/**
 * Search functionality
 */
function initializeSearch() {
    const searchInputs = document.querySelectorAll("[data-search]");

    searchInputs.forEach((input) => {
        let searchTimeout;

        input.addEventListener("input", function () {
            clearTimeout(searchTimeout);
            const searchTerm = this.value;
            const target = this.getAttribute("data-search");

            searchTimeout = setTimeout(() => {
                performSearch(searchTerm, target);
            }, 300);
        });
    });
}

/**
 * Perform search
 * @param {string} searchTerm
 * @param {string} target
 */
function performSearch(searchTerm, target) {
    if (target === "books") {
        searchBooks(searchTerm);
    } else if (target === "users") {
        searchUsers(searchTerm);
    }
}

/**
 * Search books
 * @param {string} searchTerm
 */
function searchBooks(searchTerm) {
    const rows = document.querySelectorAll("#booksTable tbody tr");

    rows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        const shouldShow =
            searchTerm === "" || text.includes(searchTerm.toLowerCase());
        row.style.display = shouldShow ? "" : "none";
    });
}

/**
 * Search users
 * @param {string} searchTerm
 */
function searchUsers(searchTerm) {
    const rows = document.querySelectorAll("#usersTable tbody tr");

    rows.forEach((row) => {
        const text = row.textContent.toLowerCase();
        const shouldShow =
            searchTerm === "" || text.includes(searchTerm.toLowerCase());
        row.style.display = shouldShow ? "" : "none";
    });
}

/* Data tables functionality */
function initializeDataTables() {
    // Add sorting functionality
    document.querySelectorAll("th[data-sort]").forEach((header) => {
        header.addEventListener("click", function () {
            const table = this.closest("table");
            const column = this.getAttribute("data-sort");
            const currentOrder = this.getAttribute("data-order") || "asc";
            const newOrder = currentOrder === "asc" ? "desc" : "asc";

            sortTable(table, column, newOrder);

            // Update header attributes
            table.querySelectorAll("th[data-sort]").forEach((th) => {
                th.removeAttribute("data-order");
                th.classList.remove("sort-asc", "sort-desc");
            });

            this.setAttribute("data-order", newOrder);
            this.classList.add(`sort-${newOrder}`);
        });

        header.style.cursor = "pointer";
    });
}

/**
 * Sort table
 * @param {HTMLTableElement} table
 * @param {string} column
 * @param {string} order
 */
function sortTable(table, column, order) {
    const tbody = table.querySelector("tbody");
    const rows = Array.from(tbody.querySelectorAll("tr"));
    const columnIndex = Array.from(table.querySelectorAll("th")).findIndex(
        (th) => th.getAttribute("data-sort") === column
    );

    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();

        // Try to parse as numbers
        const aNum = parseFloat(aValue);
        const bNum = parseFloat(bValue);

        if (!isNaN(aNum) && !isNaN(bNum)) {
            return order === "asc" ? aNum - bNum : bNum - aNum;
        }

        // String comparison
        return order === "asc"
            ? aValue.localeCompare(bValue)
            : bValue.localeCompare(aValue);
    });

    // Re-append sorted rows
    rows.forEach((row) => tbody.appendChild(row));
}

/**
 * UI Components
 */
function initializeUIComponents() {
    initializeTooltips();

    initializeAlerts();

    initializeLoadingStates();
}

/**
 * Initialize tooltips
 */
function initializeTooltips() {
    // Simple tooltip implementation
    document.querySelectorAll("[data-tooltip]").forEach((element) => {
        element.addEventListener("mouseenter", function () {
            showTooltip(this);
        });

        element.addEventListener("mouseleave", function () {
            hideTooltip();
        });
    });
}

/**
 * Initialize alerts
 */
function initializeAlerts() {
    // Auto-hide success alerts after 5 seconds
    document.querySelectorAll(".alert-success").forEach((alert) => {
        setTimeout(() => {
            fadeOut(alert);
        }, 5000);
    });

    // Add close functionality to alerts
    document.querySelectorAll(".alert .close").forEach((closeBtn) => {
        closeBtn.addEventListener("click", function () {
            fadeOut(this.closest(".alert"));
        });
    });
}

/**
 * Initialize loading states
 */
function initializeLoadingStates() {
    document.querySelectorAll("form").forEach((form) => {
        form.addEventListener("submit", function () {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                showLoading(submitBtn);
            }
        });
    });
}

/* Sidebar toggle for mobile */
function initializeSidebarToggle() {
    const sidebarToggle = document.getElementById("sidebarToggle");
    const sidebar = document.querySelector(".sidebar");

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener("click", function () {
            sidebar.classList.toggle("show");
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener("click", function (e) {
            if (window.innerWidth <= 768) {
                if (
                    !sidebar.contains(e.target) &&
                    !sidebarToggle.contains(e.target)
                ) {
                    sidebar.classList.remove("show");
                }
            }
        });
    }
}

/* Utility Functions */

/**
 * Validate email format
 * @param {string} email
 * @returns {boolean}
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * Validate password
 * @param {string} password
 * @returns {object}
 */
function validatePassword(password) {
    const errors = [];

    if (password.length < 6) {
        errors.push("Password must be at least 6 characters long");
    }

    if (!/[A-Za-z]/.test(password)) {
        errors.push("Password must contain at least one letter");
    }

    if (!/[0-9]/.test(password)) {
        errors.push("Password must contain at least one number");
    }

    return {
        valid: errors.length === 0,
        errors: errors,
    };
}

/**
 * Validate phone number
 * @param {string} phone
 * @returns {boolean}
 */
function isValidPhoneNumber(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ""));
}

/**
 * Validate ISBN
 * @param {string} isbn
 * @returns {boolean}
 */
function isValidISBN(isbn) {
    const isbn10Regex = /^(?:\d{9}X|\d{10})$/;
    const isbn13Regex = /^\d{13}$/;
    const cleanIsbn = isbn.replace(/[\s\-]/g, "");

    return isbn10Regex.test(cleanIsbn) || isbn13Regex.test(cleanIsbn);
}

/**
 * Show loading state on button
 * @param {HTMLButtonElement} button
 */
function showLoading(button) {
    button.disabled = true;
    button.originalText = button.innerHTML;
    button.innerHTML = '<span class="spinner"></span> Loading...';
}

/**
 * Hide loading state on button
 * @param {HTMLButtonElement} button
 */
function hideLoading(button) {
    button.disabled = false;
    if (button.originalText) {
        button.innerHTML = button.originalText;
    }
}

/**
 * Fade out element
 * @param {HTMLElement} element
 */
function fadeOut(element) {
    element.style.transition = "opacity 0.3s ease";
    element.style.opacity = "0";

    setTimeout(() => {
        element.style.display = "none";
    }, 300);
}

/**
 * Show tooltip
 * @param {HTMLElement} element
 */
function showTooltip(element) {
    const tooltipText = element.getAttribute("data-tooltip");

    const tooltip = document.createElement("div");
    tooltip.className = "tooltip";
    tooltip.textContent = tooltipText;
    tooltip.style.cssText = `
        position: absolute;
        background: #333;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 1000;
        pointer-events: none;
    `;

    document.body.appendChild(tooltip);

    const rect = element.getBoundingClientRect();
    tooltip.style.left =
        rect.left + rect.width / 2 - tooltip.offsetWidth / 2 + "px";
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + "px";

    tooltip.id = "current-tooltip";
}

/* Hide tooltip */
function hideTooltip() {
    const tooltip = document.getElementById("current-tooltip");
    if (tooltip) {
        tooltip.remove();
    }
}

/**
 * Confirm dialog
 * @param {string} message
 * @returns {boolean}
 */
function confirmAction(message) {
    return confirm(message || "Are you sure you want to perform this action?");
}

/**
 * Show notification
 * @param {string} message
 * @param {string} type
 */
function showNotification(message, type = "info") {
    const notification = document.createElement("div");
    notification.className = `alert alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        min-width: 300px;
        animation: slideIn 0.3s ease;
    `;
    notification.innerHTML = `
        ${message}
        <button type="button" class="close" onclick="this.parentElement.remove()">
            <span>&times;</span>
        </button>
    `;

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            fadeOut(notification);
        }
    }, 5000);
}

/**
 * Format date for display
 * @param {string} dateString
 * @returns {string}
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

/**
 * Calculate days difference
 * @param {string} date1
 * @param {string} date2
 * @returns {number}
 */
function calculateDaysDifference(date1, date2) {
    const d1 = new Date(date1);
    const d2 = new Date(date2);
    const timeDiff = Math.abs(d2.getTime() - d1.getTime());
    return Math.ceil(timeDiff / (1000 * 3600 * 24));
}

// Export functions for global access
window.LMS = {
    openModal,
    closeModal,
    showNotification,
    confirmAction,
    showLoading,
    hideLoading,
    validateForm,
    formatDate,
    calculateDaysDifference,
};
