/**
 * ServiceStatus
 * Fetches and displays the health status of backend microservices.
 */
export class ServiceStatus {
    constructor(apiClient, containerSelector) {
        this.apiClient = apiClient;
        this.containerElement = document.querySelector(containerSelector);
        this.statusData = null;
        this.isLoading = false;

        if (!this.containerElement) {
            console.warn(`ServiceStatus: Container element with selector "${containerSelector}" not found.`);
        }
    }

    initialize() {
        if (!this.containerElement) {
            console.error("ServiceStatus: Cannot initialize without a container element.");
            return;
        }
        this._renderLoading(); // Initial loading state
        console.log("ServiceStatus initialized.");
    }

    async fetchStatus() {
        if (this.isLoading) {
            console.log("ServiceStatus: Already fetching status.");
            return;
        }

        this.isLoading = true;
        this._renderLoading();

        try {
            const response = await this.apiClient.get('/config/services/status');
            if (response && response.success && response.data && response.data.services_status) {
                this.statusData = response.data.services_status;
                this._renderStatus();
            } else {
                throw new Error(response?.message || 'Failed to fetch service statuses or invalid format.');
            }
        } catch (error) {
            console.error('ServiceStatus: Error fetching service statuses:', error);
            this.statusData = null; // Clear previous data on error
            this._renderError(error.message || 'Could not retrieve service statuses.');
        } finally {
            this.isLoading = false;
        }
    }

    _renderLoading() {
        if (!this.containerElement) return;
        this.containerElement.innerHTML = '<p class="status-loading">Loading service statuses...</p>';
    }

    _renderError(errorMessage) {
        if (!this.containerElement) return;
        this.containerElement.innerHTML = `<p class="status-error">Error: ${errorMessage}</p>`;
        // Optionally add a retry button
        const retryButton = document.createElement('button');
        retryButton.textContent = 'Retry';
        retryButton.className = 'button-retry-status';
        retryButton.addEventListener('click', () => this.fetchStatus());
        this.containerElement.appendChild(retryButton);
    }

    _renderStatus() {
        if (!this.containerElement) return;
        if (!this.statusData || Object.keys(this.statusData).length === 0) {
            this.containerElement.innerHTML = '<p class="status-info">No service statuses available.</p>';
            return;
        }

        this.containerElement.innerHTML = ''; // Clear previous content

        const ul = document.createElement('ul');
        ul.className = 'service-status-list';

        for (const serviceName in this.statusData) {
            if (Object.hasOwnProperty.call(this.statusData, serviceName)) {
                const service = this.statusData[serviceName];
                const li = document.createElement('li');
                li.className = `service-status-item status-${service.status || 'unknown'}`; // e.g., status-healthy, status-unhealthy

                let statusText = service.status ? service.status.charAt(0).toUpperCase() + service.status.slice(1) : 'Unknown';
                if(service.statusCode) {
                    statusText += ` (HTTP ${service.statusCode})`;
                }

                const serviceNameSpan = document.createElement('span');
                serviceNameSpan.className = 'service-name';
                serviceNameSpan.textContent = serviceName.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                const serviceStatusSpan = document.createElement('span');
                serviceStatusSpan.className = 'service-health';
                serviceStatusSpan.textContent = statusText;
                
                li.appendChild(serviceNameSpan);
                li.appendChild(serviceStatusSpan);

                // Optionally display more details from service.message or service.details
                if (service.message && (service.status !== 'healthy' || service.statusCode >= 300)) {
                    const detailsP = document.createElement('p');
                    detailsP.className = 'service-details';
                    detailsP.textContent = service.message;
                    li.appendChild(detailsP);
                }
                
                // Example of showing specific detail if present (e.g., from transcription health check)
                if (service.details && service.details.uptime) {
                     const uptimeP = document.createElement('p');
                     uptimeP.className = 'service-uptime';
                     uptimeP.textContent = `Uptime: ${service.details.uptime}s`; // Assuming uptime is in seconds
                     li.appendChild(uptimeP);
                }


                ul.appendChild(li);
            }
        }
        this.containerElement.appendChild(ul);
        
        // Add a refresh button
        const refreshButton = document.createElement('button');
        refreshButton.textContent = 'Refresh Statuses';
        refreshButton.className = 'button-refresh-status';
        refreshButton.addEventListener('click', () => this.fetchStatus());
        this.containerElement.appendChild(refreshButton);
    }

    startMonitoring(intervalMs = null) {
        this.fetchStatus(); // Initial fetch

        if (intervalMs && intervalMs > 0) {
            if (this.monitoringInterval) {
                clearInterval(this.monitoringInterval);
            }
            this.monitoringInterval = setInterval(() => {
                this.fetchStatus();
            }, intervalMs);
            console.log(`ServiceStatus: Monitoring started with interval ${intervalMs}ms.`);
        }
    }

    stopMonitoring() {
        if (this.monitoringInterval) {
            clearInterval(this.monitoringInterval);
            this.monitoringInterval = null;
            console.log("ServiceStatus: Monitoring stopped.");
        }
    }
}