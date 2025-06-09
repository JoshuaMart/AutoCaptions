/**
 * Main JavaScript for AutoCaptions Web Interface
 * Handles service status, modal interactions, and API communication
 */

// Application state
const AppState = {
  services: {},
  healthCheckInterval: null,
  lastHealthCheck: null,
};

// Configuration
const CONFIG = {
  healthCheckInterval: 30000, // 30 seconds
  apiTimeout: 5000,
  retryAttempts: 3,
};

/**
 * Initialize the application
 */
document.addEventListener("DOMContentLoaded", function () {
  initializeApp();
});

/**
 * Main initialization function
 */
function initializeApp() {
  console.log("🚀 AutoCaptions Web Interface - Initializing...");

  // Initialize components
  initializeServiceStatus();
  initializeSettingsModal();
  initializeTooltips();

  // Start health monitoring
  startHealthMonitoring();

  console.log("✅ AutoCaptions Web Interface - Ready");
}

/**
 * Service Status Management
 */
function initializeServiceStatus() {
  // Immediately check service health
  checkServicesHealth();

  // Add click handlers for service status indicators
  document.querySelectorAll("[data-service]").forEach((element) => {
    element.addEventListener("click", function () {
      const serviceName = this.dataset.service;
      showServiceDetails(serviceName);
    });
  });
}

/**
 * Check health of all services
 */
async function checkServicesHealth() {
  try {
    console.log("🔍 Checking services health...");

    const response = await fetch("/api/health.php", {
      method: "GET",
      headers: {
        "Content-Type": "application/json",
      },
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();

    if (data.success) {
      updateServiceStatusUI(data);
      AppState.services = data.services;
      AppState.lastHealthCheck = new Date();

      console.log(
        `✅ Health check completed - ${data.healthy_services}/${data.total_services} services healthy`,
      );
    } else {
      throw new Error(data.error || "Health check failed");
    }
  } catch (error) {
    console.error("❌ Health check failed:", error);
    showAllServicesDown();
    showNotification(
      "error",
      "Connection Error",
      "Unable to check service status",
    );
  }
}

/**
 * Update service status indicators in the UI
 */
function updateServiceStatusUI(healthData) {
  const { services, overall_status } = healthData;

  // Update individual service indicators
  Object.keys(services).forEach((serviceKey) => {
    const service = services[serviceKey];
    const statusDot = document.querySelector(
      `[data-service-dot="${serviceKey}"]`,
    );
    const configStatusDot = document.querySelector(
      `[data-config-status="${serviceKey}"]`,
    );

    if (statusDot) {
      updateStatusDot(statusDot, service.status);
    }

    if (configStatusDot) {
      updateStatusDot(configStatusDot, service.status);
    }
  });

  // Update overall status
  updateOverallStatus(
    overall_status,
    healthData.healthy_services,
    healthData.total_services,
  );
}

/**
 * Update a single status dot
 */
function updateStatusDot(element, status) {
  // Remove all status classes
  element.classList.remove(
    "bg-green-500",
    "bg-yellow-500",
    "bg-red-500",
    "bg-gray-400",
  );

  // Add appropriate status class
  switch (status) {
    case "healthy":
      element.classList.add("bg-green-500");
      break;
    case "unhealthy":
      element.classList.add("bg-yellow-500");
      break;
    case "error":
      element.classList.add("bg-red-500");
      break;
    default:
      element.classList.add("bg-gray-400");
  }
}

/**
 * Update overall system status
 */
function updateOverallStatus(status, healthyCount, totalCount) {
  const statusDot = document.getElementById("overall-status-dot");
  const statusText = document.getElementById("overall-status-text");
  const statusContainer = document.getElementById("overall-status");

  if (!statusDot || !statusText || !statusContainer) return;

  // Remove all status classes
  statusContainer.classList.remove(
    "bg-green-100",
    "bg-yellow-100",
    "bg-red-100",
    "bg-gray-100",
  );
  statusDot.classList.remove(
    "bg-green-500",
    "bg-yellow-500",
    "bg-red-500",
    "bg-gray-400",
  );

  switch (status) {
    case "healthy":
      statusContainer.classList.add("bg-green-100");
      statusDot.classList.add("bg-green-500");
      statusText.textContent = "All Systems Operational";
      break;
    case "degraded":
      statusContainer.classList.add("bg-yellow-100");
      statusDot.classList.add("bg-yellow-500");
      statusText.textContent = `${healthyCount}/${totalCount} Services Online`;
      break;
    case "critical":
      statusContainer.classList.add("bg-red-100");
      statusDot.classList.add("bg-red-500");
      statusText.textContent = "Services Unavailable";
      break;
    default:
      statusContainer.classList.add("bg-gray-100");
      statusDot.classList.add("bg-gray-400");
      statusText.textContent = "Checking Services...";
  }
}

/**
 * Show all services as down (fallback when health check fails)
 */
function showAllServicesDown() {
  document.querySelectorAll("[data-service-dot]").forEach((dot) => {
    updateStatusDot(dot, "error");
  });

  document.querySelectorAll("[data-config-status]").forEach((dot) => {
    updateStatusDot(dot, "error");
  });

  updateOverallStatus("critical", 0, 3);
}

/**
 * Start health monitoring interval
 */
function startHealthMonitoring() {
  // Clear any existing interval
  if (AppState.healthCheckInterval) {
    clearInterval(AppState.healthCheckInterval);
  }

  // Set up new interval
  AppState.healthCheckInterval = setInterval(() => {
    checkServicesHealth();
  }, CONFIG.healthCheckInterval);

  console.log(
    `🔄 Health monitoring started (${CONFIG.healthCheckInterval / 1000}s interval)`,
  );
}

/**
 * Settings Modal Management
 */
function initializeSettingsModal() {
  const form = document.getElementById("services-config-form");
  if (form) {
    form.addEventListener("submit", handleConfigSave);
  }
}

/**
 * Open settings modal
 */
function openSettingsModal() {
  const modal = document.getElementById("settings-modal");
  if (modal) {
    modal.classList.remove("hidden");
    document.body.style.overflow = "hidden";

    // Focus first input
    const firstInput = modal.querySelector('input[type="url"]');
    if (firstInput) {
      setTimeout(() => firstInput.focus(), 100);
    }
  }
}

/**
 * Close settings modal
 */
function closeSettingsModal() {
  const modal = document.getElementById("settings-modal");
  if (modal) {
    modal.classList.add("hidden");
    document.body.style.overflow = "";
  }
}

/**
 * Handle configuration form save
 */
async function handleConfigSave(event) {
  event.preventDefault();

  const formData = new FormData(event.target);
  const configData = {};

  // Parse form data into service configuration
  for (let [key, value] of formData.entries()) {
    const match = key.match(/services\[([^\]]+)\]\[([^\]]+)\]/);
    if (match) {
      const serviceKey = match[1];
      const property = match[2];

      if (!configData[serviceKey]) {
        configData[serviceKey] = {};
      }
      configData[serviceKey][property] = value;
    }
  }

  try {
    const response = await fetch("/api/update-config.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ services: configData }),
    });

    const result = await response.json();

    if (result.success) {
      showNotification(
        "success",
        "Configuration Saved",
        "Service URLs have been updated successfully",
      );
      closeSettingsModal();

      // Re-check health with new configuration
      setTimeout(() => {
        checkServicesHealth();
      }, 1000);
    } else {
      throw new Error(result.error || "Failed to save configuration");
    }
  } catch (error) {
    console.error("❌ Config save failed:", error);
    showNotification("error", "Save Failed", error.message);
  }
}

/**
 * Test all service connections
 */
async function testAllConnections() {
  const button = event.target;

  // Update button state
  button.disabled = true;
  button.innerHTML = `
        <svg class="animate-spin w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        Testing...
    `;

  try {
    await checkServicesHealth();
    showNotification(
      "success",
      "Connection Test",
      "All service connections tested",
    );
  } catch (error) {
    showNotification("error", "Connection Test Failed", error.message);
  } finally {
    // Restore button state
    button.disabled = false;
    button.innerHTML = `
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Test All Connections
        `;
  }
}

/**
 * Reset configuration to defaults
 */
async function resetToDefaults() {
  if (
    !confirm(
      "Are you sure you want to reset all service URLs to their default values?",
    )
  ) {
    return;
  }

  try {
    const response = await fetch("/api/reset-config.php", {
      method: "POST",
    });

    const result = await response.json();

    if (result.success) {
      showNotification(
        "success",
        "Configuration Reset",
        "Service URLs have been reset to defaults",
      );

      // Reload the page to reflect new configuration
      setTimeout(() => {
        window.location.reload();
      }, 1500);
    } else {
      throw new Error(result.error || "Failed to reset configuration");
    }
  } catch (error) {
    console.error("❌ Config reset failed:", error);
    showNotification("error", "Reset Failed", error.message);
  }
}

/**
 * Tooltip Management
 */
function initializeTooltips() {
  const tooltip = document.getElementById("service-status-tooltip");
  if (!tooltip) return;

  document.querySelectorAll("[data-service]").forEach((element) => {
    element.addEventListener("mouseenter", function (event) {
      showTooltip(event, this.dataset.service);
    });

    element.addEventListener("mouseleave", function () {
      hideTooltip();
    });
  });
}

/**
 * Show service status tooltip
 */
function showTooltip(event, serviceKey) {
  const tooltip = document.getElementById("service-status-tooltip");
  const content = document.getElementById("tooltip-content");

  if (!tooltip || !content || !AppState.services[serviceKey]) return;

  const service = AppState.services[serviceKey];

  content.innerHTML = `
        <div class="font-semibold">${service.name}</div>
        <div class="text-gray-300">Status: ${service.status}</div>
        <div class="text-gray-300">URL: ${service.url}</div>
        ${service.response_time ? `<div class="text-gray-300">Response: ${service.response_time}ms</div>` : ""}
        <div class="text-gray-400 text-xs mt-1">Last checked: ${AppState.lastHealthCheck ? AppState.lastHealthCheck.toLocaleTimeString() : "Never"}</div>
    `;

  // Position tooltip
  const rect = event.target.getBoundingClientRect();
  tooltip.style.left = `${rect.left}px`;
  tooltip.style.top = `${rect.bottom + 8}px`;

  // Show tooltip
  tooltip.style.display = "block";
  setTimeout(() => {
    tooltip.classList.remove("opacity-0");
  }, 10);
}

/**
 * Hide service status tooltip
 */
function hideTooltip() {
  const tooltip = document.getElementById("service-status-tooltip");
  if (!tooltip) return;

  tooltip.classList.add("opacity-0");
  setTimeout(() => {
    tooltip.style.display = "none";
  }, 200);
}

/**
 * Show service details (when clicking on service indicator)
 */
function showServiceDetails(serviceKey) {
  if (!AppState.services[serviceKey]) {
    showNotification(
      "warning",
      "Service Info",
      "No information available for this service",
    );
    return;
  }

  const service = AppState.services[serviceKey];
  const details = `
        Service: ${service.name}
        Status: ${service.status}
        URL: ${service.url}
        Response Time: ${service.response_time || "N/A"}ms
        Last Check: ${AppState.lastHealthCheck ? AppState.lastHealthCheck.toLocaleString() : "Never"}
        Message: ${service.message || "No additional information"}
    `;

  alert(details);
}

/**
 * Notification System
 */
function showNotification(type, title, message) {
  const notification = document.getElementById("notification");
  const iconContainer = document.getElementById("notification-icon");
  const titleElement = document.getElementById("notification-title");
  const messageElement = document.getElementById("notification-message");

  if (!notification || !iconContainer || !titleElement || !messageElement) {
    console.warn("Notification elements not found");
    return;
  }

  // Set notification content
  titleElement.textContent = title;
  messageElement.textContent = message;

  // Set appropriate icon and colors
  let iconSvg;
  let borderColor;

  switch (type) {
    case "success":
      iconSvg = `
                <div class="w-6 h-6 text-green-600">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
            `;
      borderColor = "border-green-200";
      break;
    case "error":
      iconSvg = `
                <div class="w-6 h-6 text-red-600">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
            `;
      borderColor = "border-red-200";
      break;
    case "warning":
      iconSvg = `
                <div class="w-6 h-6 text-yellow-600">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
            `;
      borderColor = "border-yellow-200";
      break;
    default:
      iconSvg = `
                <div class="w-6 h-6 text-blue-600">
                    <svg fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
            `;
      borderColor = "border-blue-200";
  }

  iconContainer.innerHTML = iconSvg;

  // Update notification styling
  notification.className = `fixed top-4 right-4 z-50 transform transition-all duration-300 translate-x-0`;
  notification.querySelector(".bg-white").className =
    `bg-white rounded-lg shadow-lg border ${borderColor} p-4 max-w-sm`;

  // Show notification
  notification.classList.remove("hidden");

  // Auto-hide after 5 seconds
  setTimeout(() => {
    hideNotification();
  }, 5000);
}

/**
 * Hide notification
 */
function hideNotification() {
  const notification = document.getElementById("notification");
  if (notification) {
    notification.style.transform = "translateX(100%)";
    setTimeout(() => {
      notification.classList.add("hidden");
      notification.style.transform = "translateX(0)";
    }, 300);
  }
}

/**
 * API Helper Functions
 */
const API = {
  async call(service, endpoint, method = "GET", data = null, files = null) {
    const url = new URL("/api/proxy.php", window.location.origin);
    url.searchParams.set("service", service);
    url.searchParams.set("endpoint", endpoint);

    const options = {
      method: method,
      headers: {},
    };

    if (data && !files) {
      if (typeof data === "object") {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(data);
      } else {
        options.body = data;
      }
    } else if (
      files ||
      (data && typeof data === "object" && data instanceof FormData)
    ) {
      const formData = data instanceof FormData ? data : new FormData();

      if (files) {
        Object.keys(files).forEach((key) => {
          formData.append(key, files[key]);
        });
      }

      options.body = formData;
    }

    try {
      const response = await fetch(url.toString(), options);
      const contentType = response.headers.get("Content-Type") || "";

      if (contentType.includes("application/json")) {
        return await response.json();
      } else if (
        contentType.includes("video/") ||
        contentType.includes("image/")
      ) {
        return {
          success: response.ok,
          blob: await response.blob(),
          contentType: contentType,
        };
      } else {
        return {
          success: response.ok,
          text: await response.text(),
          contentType: contentType,
        };
      }
    } catch (error) {
      console.error("API call failed:", error);
      throw new Error(`API call failed: ${error.message}`);
    }
  },
};

/**
 * Utility Functions
 */
const Utils = {
  formatFileSize(bytes) {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
  },

  formatDuration(seconds) {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = Math.floor(seconds % 60);

    if (hours > 0) {
      return `${hours}:${minutes.toString().padStart(2, "0")}:${secs.toString().padStart(2, "0")}`;
    } else {
      return `${minutes}:${secs.toString().padStart(2, "0")}`;
    }
  },

  validateVideoFile(file) {
    const allowedTypes = [
      "video/mp4",
      "video/mov",
      "video/avi",
      "video/mkv",
      "video/webm",
    ];
    const maxSize = 500 * 1024 * 1024; // 500MB

    if (!allowedTypes.includes(file.type)) {
      throw new Error(
        "Unsupported file type. Please upload MP4, MOV, AVI, MKV, or WebM files.",
      );
    }

    if (file.size > maxSize) {
      throw new Error(
        `File too large. Maximum size is ${Utils.formatFileSize(maxSize)}.`,
      );
    }

    return true;
  },

  debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  },
};

// Keyboard shortcuts
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeSettingsModal();
    hideNotification();
  }

  if ((event.ctrlKey || event.metaKey) && event.key === "k") {
    event.preventDefault();
    openSettingsModal();
  }
});

// Handle page visibility changes
document.addEventListener("visibilitychange", function () {
  if (document.hidden) {
    console.log("📱 Tab hidden - pausing health checks");
    if (AppState.healthCheckInterval) {
      clearInterval(AppState.healthCheckInterval);
      AppState.healthCheckInterval = null;
    }
  } else {
    console.log("📱 Tab visible - resuming health checks");
    checkServicesHealth();
    startHealthMonitoring();
  }
});

// Export functions for global access
window.AppState = AppState;
window.API = API;
window.Utils = Utils;
window.openSettingsModal = openSettingsModal;
window.closeSettingsModal = closeSettingsModal;
window.testAllConnections = testAllConnections;
window.resetToDefaults = resetToDefaults;
window.showNotification = showNotification;
window.hideNotification = hideNotification;
