// modules/config-manager-ui.js
// Gère l'interface de configuration des services (modal settings)

export class ConfigManagerUI {
    constructor(apiClient) {
        this.apiClient = apiClient;
        this.servicesConfig = {};
        this.isModalOpen = false;

        // DOM elements
        this.modal = null;
        this.configContainer = null;
        this.testButton = null;
        this.closeButton = null;
    }

    async init() {
        console.log('⚙️ ConfigManagerUI - Initializing...');
        
        // Get DOM elements
        this.modal = document.getElementById('settings-modal');
        this.configContainer = document.getElementById('service-config-container');
        
        if (!this.modal || !this.configContainer) {
            console.warn('ConfigManagerUI - Required DOM elements not found');
            return;
        }

        await this.loadServicesConfig();
        this.renderConfigInterface();
        this.setupEventListeners();
        
        console.log('✅ ConfigManagerUI - Ready');
    }

    async loadServicesConfig() {
        try {
            const response = await this.apiClient.getServicesConfig();
            if (response.success) {
                this.servicesConfig = response.data;
                console.log('⚙️ ConfigManagerUI - Services config loaded');
            } else {
                throw new Error(response.error || 'Failed to load services config');
            }
        } catch (error) {
            console.error('⚙️ ConfigManagerUI - Failed to load config:', error);
            window.app.showNotification('error', 'Config Error', 'Failed to load services configuration');
        }
    }

    renderConfigInterface() {
        if (!this.configContainer) return;

        const html = `
            <div class="space-y-4">
                <p class="text-sm text-gray-600 mb-4">
                    Configure the URLs for each microservice. Changes will be saved to your session.
                </p>
                
                ${Object.keys(this.servicesConfig).map(serviceKey => 
                    this.renderServiceConfig(serviceKey, this.servicesConfig[serviceKey])
                ).join('')}
                
                <div class="mt-6 flex justify-between items-center">
                    <button type="button" 
                            onclick="app.configManagerUI.resetToDefaults()"
                            class="text-sm text-red-600 hover:text-red-800">
                        Reset to Defaults
                    </button>
                    
                    <button type="button"
                            onclick="app.configManagerUI.saveConfiguration()"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Save Configuration
                    </button>
                </div>
            </div>
        `;

        this.configContainer.innerHTML = html;
    }

    renderServiceConfig(serviceKey, service) {
        const statusClass = this.getStatusClass(service.status);
        const statusText = this.getStatusText(service.status);

        return `
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900">${service.name}</h4>
                        <p class="text-xs text-gray-500">${service.description || ''}</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 rounded-full ${statusClass}" data-config-status="${serviceKey}"></div>
                        <span class="text-xs text-gray-600">${statusText}</span>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <label class="block text-xs font-medium text-gray-700">Service URL</label>
                    <input type="url" 
                           id="config-url-${serviceKey}"
                           value="${service.effective_url || service.url || ''}"
                           placeholder="${service.url || 'http://localhost:' + service.default_port}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    
                    ${service.source === 'override' ? 
                        `<p class="text-xs text-blue-600">Using custom URL (override active)</p>` :
                        `<p class="text-xs text-gray-500">Using default configuration</p>`
                    }
                </div>
            </div>
        `;
    }

    getStatusClass(status) {
        switch (status) {
            case 'healthy': return 'bg-green-500';
            case 'unhealthy': return 'bg-yellow-500';
            case 'error': return 'bg-red-500';
            default: return 'bg-gray-400';
        }
    }

    getStatusText(status) {
        switch (status) {
            case 'healthy': return 'Online';
            case 'unhealthy': return 'Degraded';
            case 'error': return 'Offline';
            default: return 'Unknown';
        }
    }

    setupEventListeners() {
        // Close modal when clicking outside
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.closeModal();
            }
        });
    }

    openModal() {
        if (this.modal) {
            this.modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            this.isModalOpen = true;

            // Focus first input
            const firstInput = this.modal.querySelector('input[type="url"]');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }

            console.log('⚙️ ConfigManagerUI - Modal opened');
        }
    }

    closeModal() {
        if (this.modal) {
            this.modal.classList.add('hidden');
            document.body.style.overflow = '';
            this.isModalOpen = false;
            console.log('⚙️ ConfigManagerUI - Modal closed');
        }
    }

    async saveConfiguration() {
        try {
            const configData = {};

            // Collect configuration from inputs
            Object.keys(this.servicesConfig).forEach(serviceKey => {
                const input = document.getElementById(`config-url-${serviceKey}`);
                if (input && input.value.trim()) {
                    configData[serviceKey] = {
                        url: input.value.trim()
                    };
                }
            });

            console.log('⚙️ ConfigManagerUI - Saving configuration:', configData);

            const response = await this.apiClient.updateServiceConfig(configData);

            if (response.success) {
                window.app.showNotification('success', 'Configuration Saved', 'Service URLs have been updated successfully');
                this.closeModal();

                // Reload config and refresh service status
                await this.loadServicesConfig();
                this.renderConfigInterface();
                
                // Dispatch event for service status update
                document.dispatchEvent(new CustomEvent('serviceConfigUpdated'));

            } else {
                throw new Error(response.error || 'Failed to save configuration');
            }

        } catch (error) {
            console.error('⚙️ ConfigManagerUI - Save failed:', error);
            window.app.showNotification('error', 'Save Failed', error.message);
        }
    }

    async testAllConnections() {
        const button = event.target;
        const originalHtml = button.innerHTML;

        // Update button state
        button.disabled = true;
        button.innerHTML = `
            <svg class="animate-spin w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Testing...
        `;

        try {
            // Force a health check
            if (window.app.serviceStatus) {
                await window.app.serviceStatus.checkHealth();
            }
            
            window.app.showNotification('success', 'Connection Test', 'All service connections tested');
            
        } catch (error) {
            console.error('⚙️ ConfigManagerUI - Test failed:', error);
            window.app.showNotification('error', 'Connection Test Failed', error.message);
            
        } finally {
            // Restore button state
            button.disabled = false;
            button.innerHTML = originalHtml;
        }
    }

    async resetToDefaults() {
        if (!confirm('Are you sure you want to reset all service URLs to their default values?')) {
            return;
        }

        try {
            const response = await this.apiClient.post('/api/config/services/reset');

            if (response.success) {
                window.app.showNotification('success', 'Configuration Reset', 'Service URLs have been reset to defaults');
                
                // Reload the page to reflect new configuration
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
                
            } else {
                throw new Error(response.error || 'Failed to reset configuration');
            }
            
        } catch (error) {
            console.error('⚙️ ConfigManagerUI - Reset failed:', error);
            window.app.showNotification('error', 'Reset Failed', error.message);
        }
    }

    // Public API
    getServicesConfig() {
        return this.servicesConfig;
    }

    isConfigModalOpen() {
        return this.isModalOpen;
    }

    refreshConfigInterface() {
        this.renderConfigInterface();
    }
}