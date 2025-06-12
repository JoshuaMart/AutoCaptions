// modules/service-status.js
// Gère l'affichage et la surveillance du statut des services backend

export class ServiceStatus {
    constructor(apiClient) {
        this.apiClient = apiClient;
        this.services = {};
        this.healthCheckInterval = null;
        this.lastHealthCheck = null;
        this.monitoringEnabled = true;
        
        // Configuration
        this.config = {
            healthCheckInterval: 30000, // 30 seconds
            retryAttempts: 3,
            timeout: 5000
        };

        // DOM elements
        this.serviceIndicators = null;
        this.overallStatus = null;
        this.overallStatusDot = null;
        this.overallStatusText = null;
        this.tooltip = null;
        this.tooltipContent = null;
    }

    async init() {
        console.log('🔍 ServiceStatus - Initializing...');
        
        // Get DOM elements
        this.serviceIndicators = document.getElementById('service-indicators');
        this.overallStatus = document.getElementById('overall-status');
        this.overallStatusDot = document.getElementById('overall-status-dot');
        this.overallStatusText = document.getElementById('overall-status-text');
        this.tooltip = document.getElementById('service-status-tooltip');
        this.tooltipContent = document.getElementById('tooltip-content');

        if (!this.serviceIndicators) {
            console.warn('ServiceStatus - Service indicators container not found');
            return;
        }

        // Initial health check
        await this.checkHealth();
        
        console.log('✅ ServiceStatus - Ready');
    }

    async checkHealth() {
        try {
            console.log('🔍 ServiceStatus - Checking services health...');
            
            const response = await this.apiClient.get('/api/config/services/status');
            
            if (response.success) {
                this.services = response.data.services || {};
                this.lastHealthCheck = new Date();
                
                this.updateUI(response.data);
                
                const healthyCount = Object.values(this.services).filter(s => s.status === 'healthy').length;
                const totalCount = Object.keys(this.services).length;
                
                console.log(`✅ ServiceStatus - Health check completed - ${healthyCount}/${totalCount} services healthy`);
                
            } else {
                throw new Error(response.error || 'Health check failed');
            }
            
        } catch (error) {
            console.error('❌ ServiceStatus - Health check failed:', error);
            this.showAllServicesDown();
            window.app.showNotification('error', 'Connection Error', 'Unable to check service status');
        }
    }

    updateUI(healthData) {
        this.updateServiceIndicators(healthData.services);
        this.updateOverallStatus(healthData.overall_status, healthData.healthy_services, healthData.total_services);
    }

    updateServiceIndicators(services) {
        if (!this.serviceIndicators) return;

        // Clear existing indicators
        this.serviceIndicators.innerHTML = '';

        Object.keys(services).forEach(serviceKey => {
            const service = services[serviceKey];
            const indicator = this.createServiceIndicator(serviceKey, service);
            this.serviceIndicators.appendChild(indicator);
        });
    }

    createServiceIndicator(serviceKey, service) {
        const container = document.createElement('div');
        container.className = 'flex items-center space-x-1';
        container.setAttribute('data-service', serviceKey);
        container.title = service.description || service.name;

        // Status dot
        const dot = document.createElement('div');
        dot.className = 'w-2 h-2 rounded-full transition-colors duration-200';
        dot.setAttribute('data-service-dot', serviceKey);
        this.updateStatusDot(dot, service.status);

        // Service name (hidden on small screens)
        const name = document.createElement('span');
        name.className = 'text-xs text-gray-600 hidden sm:inline';
        name.textContent = service.name;

        container.appendChild(dot);
        container.appendChild(name);

        // Add event listeners for tooltips and clicks
        container.addEventListener('mouseenter', (e) => this.showTooltip(e, serviceKey));
        container.addEventListener('mouseleave', () => this.hideTooltip());
        container.addEventListener('click', () => this.showServiceDetails(serviceKey));

        return container;
    }

    updateStatusDot(element, status) {
        // Remove all status classes
        element.classList.remove('bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-gray-400');

        // Add appropriate status class
        switch (status) {
            case 'healthy':
                element.classList.add('bg-green-500');
                break;
            case 'unhealthy':
                element.classList.add('bg-yellow-500');
                break;
            case 'error':
                element.classList.add('bg-red-500');
                break;
            default:
                element.classList.add('bg-gray-400');
        }
    }

    updateOverallStatus(status, healthyCount, totalCount) {
        if (!this.overallStatus || !this.overallStatusDot || !this.overallStatusText) return;

        // Remove all status classes
        this.overallStatus.classList.remove('bg-green-100', 'bg-yellow-100', 'bg-red-100', 'bg-gray-100');
        this.overallStatusDot.classList.remove('bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-gray-400');

        switch (status) {
            case 'healthy':
                this.overallStatus.classList.add('bg-green-100');
                this.overallStatusDot.classList.add('bg-green-500');
                this.overallStatusText.textContent = 'All Systems Operational';
                break;
            case 'degraded':
                this.overallStatus.classList.add('bg-yellow-100');
                this.overallStatusDot.classList.add('bg-yellow-500');
                this.overallStatusText.textContent = `${healthyCount}/${totalCount} Services Online`;
                break;
            case 'critical':
                this.overallStatus.classList.add('bg-red-100');
                this.overallStatusDot.classList.add('bg-red-500');
                this.overallStatusText.textContent = 'Services Unavailable';
                break;
            default:
                this.overallStatus.classList.add('bg-gray-100');
                this.overallStatusDot.classList.add('bg-gray-400');
                this.overallStatusText.textContent = 'Checking Services...';
        }
    }

    showAllServicesDown() {
        // Update existing indicators to show error state
        document.querySelectorAll('[data-service-dot]').forEach(dot => {
            this.updateStatusDot(dot, 'error');
        });

        this.updateOverallStatus('critical', 0, 3);
    }

    showTooltip(event, serviceKey) {
        if (!this.tooltip || !this.tooltipContent || !this.services[serviceKey]) return;

        const service = this.services[serviceKey];

        this.tooltipContent.innerHTML = `
            <div class="font-semibold">${service.name}</div>
            <div class="text-gray-300">Status: ${service.status}</div>
            <div class="text-gray-300">URL: ${service.url}</div>
            ${service.response_time ? `<div class="text-gray-300">Response: ${service.response_time}ms</div>` : ''}
            <div class="text-gray-400 text-xs mt-1">Last checked: ${this.lastHealthCheck ? this.lastHealthCheck.toLocaleTimeString() : 'Never'}</div>
        `;

        // Position tooltip
        const rect = event.target.getBoundingClientRect();
        this.tooltip.style.left = `${rect.left}px`;
        this.tooltip.style.top = `${rect.bottom + 8}px`;

        // Show tooltip
        this.tooltip.style.display = 'block';
        setTimeout(() => {
            this.tooltip.classList.remove('opacity-0');
        }, 10);
    }

    hideTooltip() {
        if (!this.tooltip) return;

        this.tooltip.classList.add('opacity-0');
        setTimeout(() => {
            this.tooltip.style.display = 'none';
        }, 200);
    }

    showServiceDetails(serviceKey) {
        if (!this.services[serviceKey]) {
            window.app.showNotification('warning', 'Service Info', 'No information available for this service');
            return;
        }

        const service = this.services[serviceKey];
        const details = `
Service: ${service.name}
Status: ${service.status}
URL: ${service.url}
Response Time: ${service.response_time || 'N/A'}ms
Last Check: ${this.lastHealthCheck ? this.lastHealthCheck.toLocaleString() : 'Never'}
Message: ${service.message || 'No additional information'}
        `;

        alert(details);
    }

    startMonitoring() {
        if (this.healthCheckInterval) {
            clearInterval(this.healthCheckInterval);
        }

        this.monitoringEnabled = true;
        this.healthCheckInterval = setInterval(() => {
            if (this.monitoringEnabled) {
                this.checkHealth();
            }
        }, this.config.healthCheckInterval);

        console.log(`🔄 ServiceStatus - Monitoring started (${this.config.healthCheckInterval / 1000}s interval)`);
    }

    pauseMonitoring() {
        this.monitoringEnabled = false;
        console.log('⏸️ ServiceStatus - Monitoring paused');
    }

    resumeMonitoring() {
        this.monitoringEnabled = true;
        this.checkHealth(); // Immediate check on resume
        console.log('▶️ ServiceStatus - Monitoring resumed');
    }

    stopMonitoring() {
        if (this.healthCheckInterval) {
            clearInterval(this.healthCheckInterval);
            this.healthCheckInterval = null;
        }
        this.monitoringEnabled = false;
        console.log('⏹️ ServiceStatus - Monitoring stopped');
    }

    // Public API
    getServicesStatus() {
        return this.services;
    }

    getLastHealthCheck() {
        return this.lastHealthCheck;
    }

    isServiceHealthy(serviceKey) {
        return this.services[serviceKey]?.status === 'healthy';
    }

    areAllServicesHealthy() {
        return Object.values(this.services).every(service => service.status === 'healthy');
    }
}