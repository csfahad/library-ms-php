/* LMS - JavaScript Utilities */

window.LMS = (function () {
    "use strict";

    return {
        // Modal Functions
        openModal: function (modalId) {
            console.log("LMS.openModal called with:", modalId);
            const modal = document.getElementById(modalId);
            console.log("Modal element:", modal);
            if (modal) {
                console.log(
                    "Opening modal, current display:",
                    modal.style.display
                );
                modal.style.display = "flex";
                modal.classList.add("show");
                document.body.style.overflow = "hidden";
                console.log("Modal opened, new display:", modal.style.display);

                // Focus first form input if available
                const firstInput = modal.querySelector(
                    'input[type="text"], input[type="email"], textarea, select'
                );
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 100);
                }
            } else {
                console.error("Modal not found:", modalId);
            }
        },

        closeModal: function (modalId) {
            console.log("LMS.closeModal called with:", modalId);
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = "none";
                modal.classList.remove("show");
                document.body.style.overflow = "auto";

                // Clear form if it exists
                const form = modal.querySelector("form");
                if (form) {
                    form.reset();
                }
            }
        },

        // Confirmation dialog
        confirmAction: function (message) {
            return confirm(message);
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
        showAlert: function (message, type = "info") {
            const alertContainer = document.createElement("div");
            alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
            alertContainer.style.position = "fixed";
            alertContainer.style.top = "20px";
            alertContainer.style.right = "20px";
            alertContainer.style.zIndex = "9999";
            alertContainer.style.minWidth = "300px";
            alertContainer.innerHTML = `
                <i class="fas fa-${
                    type === "success"
                        ? "check-circle"
                        : type === "danger"
                        ? "exclamation-triangle"
                        : "info-circle"
                }"></i>
                ${message}
                <button type="button" class="btn-close" onclick="this.parentElement.remove()">
                    <span>&times;</span>
                </button>
            `;

            document.body.appendChild(alertContainer);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertContainer.parentElement) {
                    alertContainer.remove();
                }
            }, 5000);
        },

        // Initialize common functionality
        init: function () {
            console.log("LMS.init called");

            // Close modals when clicking outside
            document.addEventListener("click", function (e) {
                if (e.target.classList.contains("modal")) {
                    const modalId = e.target.id;
                    if (modalId) {
                        console.log(
                            "Closing modal by clicking outside:",
                            modalId
                        );
                        LMS.closeModal(modalId);
                    }
                }
            });

            // Close modals with Escape key
            document.addEventListener("keydown", function (e) {
                if (e.key === "Escape") {
                    const openModal = document.querySelector(
                        '.modal.show, .modal[style*="display: flex"]'
                    );
                    if (openModal) {
                        console.log(
                            "Closing modal with Escape key:",
                            openModal.id
                        );
                        LMS.closeModal(openModal.id);
                    }
                }
            });

            // Auto-hide alerts
            const alerts = document.querySelectorAll(".alert");
            alerts.forEach((alert) => {
                setTimeout(() => {
                    if (alert.parentElement) {
                        alert.style.opacity = "0";
                        alert.style.transform = "translateX(100%)";
                        setTimeout(() => alert.remove(), 300);
                    }
                }, 5000);
            });
        },
    };
})();

// Initialize when DOM is loaded
document.addEventListener("DOMContentLoaded", function () {
    LMS.init();
});
