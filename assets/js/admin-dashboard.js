/* Admin Dashboard JavaScript */

// Initialize dashboard
document.addEventListener("DOMContentLoaded", function () {
    console.log("Admin Dashboard initialized");

    const alerts = document.querySelectorAll(".alert");
    alerts.forEach((alert) => {
        setTimeout(() => {
            alert.style.opacity = "0";
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // Add hover effects to stats cards
    const statsCards = document.querySelectorAll(".stats-card");
    statsCards.forEach((card) => {
        card.addEventListener("mouseenter", function () {
            this.style.transform = "translateY(-4px)";
        });

        card.addEventListener("mouseleave", function () {
            this.style.transform = "translateY(-2px)";
        });
    });

    initializeTooltips();
    initializeFormValidation();
});

// Initialize tooltips
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll("[data-tooltip]");
    tooltipElements.forEach((element) => {
        element.addEventListener("mouseenter", showTooltip);
        element.addEventListener("mouseleave", hideTooltip);
    });
}

// Show tooltip
function showTooltip(event) {
    const tooltip = document.createElement("div");
    tooltip.className = "tooltip";
    tooltip.textContent = event.target.dataset.tooltip;
    tooltip.style.cssText = `
        position: absolute;
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 14px;
        pointer-events: none;
        z-index: 1000;
        top: ${event.pageY - 35}px;
        left: ${event.pageX - 50}px;
    `;
    document.body.appendChild(tooltip);
}

// Hide tooltip
function hideTooltip() {
    const tooltip = document.querySelector(".tooltip");
    if (tooltip) {
        tooltip.remove();
    }
}

// Initialize form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll("form[data-validate]");
    forms.forEach((form) => {
        form.addEventListener("submit", function (event) {
            if (!validateForm(this)) {
                event.preventDefault();
            }
        });
    });
}

// Validate form
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll("[required]");

    requiredFields.forEach((field) => {
        if (!field.value.trim()) {
            showFieldError(field, "This field is required");
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });

    return isValid;
}

// Show field error
function showFieldError(field, message) {
    clearFieldError(field);
    const error = document.createElement("div");
    error.className = "field-error";
    error.textContent = message;
    error.style.cssText =
        "color: var(--error-color); font-size: 14px; margin-top: 4px;";
    field.parentNode.appendChild(error);
    field.style.borderColor = "var(--error-color)";
}

// Clear field error
function clearFieldError(field) {
    const error = field.parentNode.querySelector(".field-error");
    if (error) {
        error.remove();
    }
    field.style.borderColor = "";
}

// Utility functions
const AdminUtils = {
    // Show loading spinner
    showLoading: function (element) {
        element.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        element.disabled = true;
    },

    // Hide loading spinner
    hideLoading: function (element, originalText) {
        element.innerHTML = originalText;
        element.disabled = false;
    },

    // Show success message
    showSuccess: function (message) {
        this.showAlert(message, "success");
    },

    // Show error message
    showError: function (message) {
        this.showAlert(message, "danger");
    },

    // Show alert
    showAlert: function (message, type) {
        const alertContainer =
            document.querySelector(".alert-container") ||
            document.querySelector(".main-content");
        const alert = document.createElement("div");
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <i class="fas fa-${
                type === "success" ? "check-circle" : "exclamation-triangle"
            }"></i>
            ${message}
        `;
        alertContainer.insertBefore(alert, alertContainer.firstChild);

        // Auto-hide after 5 seconds
        setTimeout(() => {
            alert.style.opacity = "0";
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    },

    // Confirm action
    confirmAction: function (message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    // Format number
    formatNumber: function (num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    },

    // Format date
    formatDate: function (date) {
        return new Date(date).toLocaleDateString();
    },

    // Format currency
    formatCurrency: function (amount) {
        return "$" + parseFloat(amount).toFixed(2);
    },
};

// Export for global use
window.AdminUtils = AdminUtils;
