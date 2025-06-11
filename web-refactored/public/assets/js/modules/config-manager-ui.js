/**
 * ConfigManagerUI
 * Handles client-side logic for viewing and updating backend service configurations.
 */
export class ConfigManagerUI {
    /**
     * @param {ApiClient} apiClient - An instance of the ApiClient.
     * @param {string} containerSelector - The CSS selector for the container element where the UI will be rendered.
     * @param {string} [templateSelector=null] - The CSS selector for the HTML template element to use for each service row.
     * @param {string} [statusSelector=null] - Optional CSS selector for a dedicated status message area.
     */
    constructor(apiClient, containerSelector, templateSelector = null, statusSelector = null) {
        this.apiClient = apiClient;
        this.containerElement = document.querySelector(containerSelector);
        this.templateElement = templateSelector ? document.querySelector(templateSelector) : null;
        this.statusElement = statusSelector ? document.querySelector(statusSelector) : null;

        this.currentConfigs = null; // To store the configurations fetched from the backend
        this.saveButton = null; // Reference to the save button element

        if (!this.containerElement) {
            console.warn(`ConfigManagerUI: Container element with selector "${containerSelector}" not found.`);
        }
         if (templateSelector && !this.templateElement) {
            console.warn(`ConfigManagerUI: Template element with selector "${templateSelector}" not found.`);
        }
         if (statusSelector && !this.statusElement) {
            console.warn(`ConfigManagerUI: Status element with selector "${statusSelector}" not found.`);
        }
    }

    /**
     * Initializes the ConfigManagerUI by fetching and rendering configurations.
     */
    initialize() {
        if (!this.containerElement) {
            console.error("ConfigManagerUI: Cannot initialize without a container element.");
            return;
        }

        // Initial render state
        this._renderLoading();
        // Fetch configurations asynchronously
        this.fetchConfigurations();

        console.log("ConfigManagerUI initialized.");
    }

    /**
     * Fetches the service configurations from the backend API.
     */
    async fetchConfigurations() {
         this._renderLoading(); // Show loading state before fetch

        try {
            const response = await this.apiClient.get('/config/services');
            if (response && response.success && response.data && response.data.services_configuration) {
                this.currentConfigs = response.data.services_configuration;
                this._renderConfigs(); // Render fetched configs
            } else {
                throw new Error(response?.message || 'Failed to fetch service configurations or invalid format.');
            }
        } catch (error) {
            console.error('ConfigManagerUI: Error fetching configurations:', error);
            this.currentConfigs = null; // Clear previous data on error
             this._renderError(error.message || 'Could not retrieve service configurations.');
        }
    }

    /**
     * Renders a loading message in the container.
     */
    _renderLoading() {
        if (!this.containerElement) return;
        this.containerElement.innerHTML = '<p class="status-loading">Loading service configurations...</p>';
        this._hideSaveButton(); // Hide save button while loading
    }

    /**
     * Renders an error message in the container.
     * @param {string} errorMessage - The error message to display.
     */
    _renderError(errorMessage) {
        if (!this.containerElement) return;
        this.containerElement.innerHTML = `<p class="status-error status-message">Error: ${errorMessage}</p>`;
         this._hideSaveButton(); // Hide save button on error
    }

    /**
     * Renders the fetched service configurations in the container.
     */
    _renderConfigs() {
        if (!this.containerElement) return;

        if (!this.currentConfigs || Object.keys(this.currentConfigs).length === 0) {
             this.containerElement.innerHTML = '<p class="status-info status-message">No service configurations available.</p>';
             this._hideSaveButton();
            return;
        }

        this.containerElement.innerHTML = ''; // Clear previous content

        const configList = document.createElement('div');
        configList.className = 'service-config-list';

        // Sort services alphabetically by name for consistent display
        const sortedServiceNames = Object.keys(this.currentConfigs).sort();

        sortedServiceNames.forEach(serviceName => {
            const config = this.currentConfigs[serviceName];
            const row = this._createConfigRow(serviceName, config);
            if (row) {
                configList.appendChild(row);
            }
        });

        this.containerElement.appendChild(configList);

        // Add or show the Save Button
        this._addSaveButton();
    }

    /**
     * Creates a single HTML row for a service configuration.
     * Attempts to use the provided template first, falls back to basic rendering.
     * @param {string} serviceName - The name of the service.
     * @param {object} config - The service configuration object.
     * @returns {HTMLElement|null} The created row element or null if rendering failed.
     */
    _createConfigRow(serviceName, config) {
        let row = null;

        if (this.templateElement) {
            try {
                const templateContent = this.templateElement.content;
                row = document.importNode(templateContent, true).firstElementChild;

                if (row) {
                     // Find elements within the cloned template row
                    const nameSpan = row.querySelector('.service-name-config');
                    const sourceSpan = row.querySelector('.service-config-source');
                    const urlInput = row.querySelector('.service-url-input');
                    // const healthIndicator = row.querySelector('.service-health-indicator'); // If we add this later

                    if (nameSpan) nameSpan.textContent = serviceName.replace(/-/g, ' ').replace(/\\b\\w/g, l => l.toUpperCase());
                    if (sourceSpan) sourceSpan.textContent = `Source: ${config.source || 'unknown'}`;
                    if (urlInput) {
                        urlInput.value = config.url || '';
                        urlInput.dataset.serviceName = serviceName; // Store service name on input for easy access
                        urlInput.placeholder = config.url || 'Service URL'; // Use current URL as placeholder if exists
                    }
                    // if (healthIndicator) { /* Update health indicator based on status? */ } // Need to integrate ServiceStatus maybe?

                    // Add data attribute to the row for easy identification if needed
                    row.dataset.serviceName = serviceName;

                    return row; // Return the successfully created row from template
                } else {
                     console.warn("ConfigManagerUI: Template content is empty or invalid. Falling back to basic rendering.");
                }
            } catch (e) {
                console.error("ConfigManagerUI: Error using config template:", e);
                 console.warn("ConfigManagerUI: Falling back to basic config row rendering.");
            }
        }

        // Fallback to basic rendering if template is not provided or failed
        return this._createBasicConfigRow(serviceName, config);
    }

    /**
     * Creates a simple HTML row for a service configuration without using a template.
     * @param {string} serviceName - The name of the service.
     * @param {object} config - The service configuration object.
     * @returns {HTMLElement} The created row element.
     */
     _createBasicConfigRow(serviceName, config) {
        const div = document.createElement('div');
        div.className = 'service-config-row basic';
        div.dataset.serviceName = serviceName; // Add data attribute

        const serviceTitle = serviceName.replace(/-/g, ' ').replace(/\\b\\w/g, l => l.toUpperCase());
        const sourceText = `Source: ${config.source || 'unknown'}`;
        const currentUrl = config.url || '';
        const placeholderUrl = config.url || 'Service URL';

        div.innerHTML = `
            <p>
                <strong>${serviceTitle}</strong> <span>(${sourceText})</span><br>
                <input type="url" value="${currentUrl}" data-service-name="${serviceName}" placeholder="${placeholderUrl}" style="width: calc(100% - 100px); padding: 5px; margin-top: 5px;">
            </p>
        `;
         div.style.marginBottom = '15px';
         div.style.padding = '10px';
         div.style.border = '1px solid #ccc';
         div.style.borderRadius = '4px';

        return div;
     }


    /**
     * Adds the save configuration button to the container if it doesn't exist.
     */
    _addSaveButton() {
        if (!this.containerElement) return;

        // Check if the button already exists in the container
        this.saveButton = this.containerElement.querySelector('.button-save-config');

        if (this.saveButton) {
             this._showSaveButton();
            return; // Button already exists
        }

        const saveButton = document.createElement('button');
        saveButton.textContent = 'Save Service Configurations';
        saveButton.className = 'button button-save-config'; // Use existing button class + a specific one
        saveButton.addEventListener('click', this._handleSaveClick.bind(this));

        this.containerElement.appendChild(saveButton);
        this.saveButton = saveButton;
    }

    /**
     * Shows the save button.
     */
     _showSaveButton() {
        if (this.saveButton) {
            this.saveButton.classList.remove('hidden');
        }
     }

    /**
     * Hides the save button.
     */
    _hideSaveButton() {
        if (this.saveButton) {
            this.saveButton.classList.add('hidden');
        }
    }

    /**
     * Handles the click event on the save configurations button.
     * Gathers data from input fields and sends updates to the backend API.
     * @param {Event} event - The click event.
     */
    async _handleSaveClick(event) {
        event.preventDefault();
        if (!this.currentConfigs || !this.saveButton || this.saveButton.disabled) return;

        this.saveButton.disabled = true;
        this.saveButton.textContent = 'Saving...';
        this._updateStatus('Saving configurations...', 'info');

        const updatedConfigsPayload = { services: {} };
        const inputs = this.containerElement.querySelectorAll('.service-config-list input[type="url"]');

        inputs.forEach(input => {
            const serviceName = input.dataset.serviceName;
            const newUrl = input.value.trim();
            if (serviceName) {
                 // Always include the service in the payload with its current input value.
                 // The backend determines if it's a change or removal based on empty/null URL.
                 updatedConfigsPayload.services[serviceName] = { url: newUrl };
            }
        });

        // Check if the payload is empty (e.g., no input fields found, though unlikely with render)
        if (Object.keys(updatedConfigsPayload.services).length === 0) {
            this._updateStatus('No services found to update in the UI.', 'error');
             this.saveButton.disabled = false;
             this.saveButton.textContent = 'Save Service Configurations';
             return;
        }


        try {
            const response = await this.apiClient.post('/config/services', updatedConfigsPayload);

            if (response && response.success) {
                this._updateStatus(response.message || 'Configurations saved successfully.', 'success');
                console.log('Config save successful:', response);
                // Re-fetch configurations to update the UI with the new source (should be 'session')
                 this.fetchConfigurations();
            } else {
                // ApiClient should throw for non-ok, but handle server-side logical errors too
                const errorMessage = response?.error?.message || response?.message || 'Failed to save configurations due to a server error.';
                 const errorDetails = response?.error?.details?.errors ? Object.values(response.error.details.errors).join(', ') : ''; // Check nested errors from backend
                this._updateStatus(`Save failed: ${errorMessage} ${errorDetails}`, 'error');
                console.error('Config save failed:', response);
            }
        } catch (error) {
            let errorMessage = 'An unexpected error occurred while saving configurations.';
             if (error.data && error.data.error && error.data.error.message) {
                errorMessage = error.data.error.message;
             } else if (error.message) {
                 errorMessage = error.message;
             }
            this._updateStatus(`Error: ${errorMessage}`, 'error');
            console.error('Config save error:', error);
        } finally {
            this.saveButton.disabled = false;
            this.saveButton.textContent = 'Save Service Configurations';
             // No re-render here, fetchConfigurations() handles the refresh on success.
             // On error, it might be good to re-fetch or at least clear any unsaved changes indicators if implemented.
        }
    }

    /**
     * Updates a status message area, using a dedicated element or creating a temporary one.
     * @param {string} message - The message text.
     * @param {'info'|'success'|'error'} [type='info'] - The type of message for styling.
     */
     _updateStatus(message, type = 'info') {
        console.log(`Config Status [${type.toUpperCase()}]: ${message}`);

        if (this.statusElement) {
            this.statusElement.textContent = message;
            this.statusElement.className = `status-message status-${type}`;
            // Optionally add a timer to clear the message
            // setTimeout(() => { this.statusElement.textContent = ''; this.statusElement.className = ''; }, 5000);
        } else {
            // Fallback: create a temporary message element near the container
            const existingMessage = this.containerElement.querySelector('.config-status-message');
            if (existingMessage) existingMessage.remove();

            const messageElement = document.createElement('p');
            messageElement.className = `config-status-message status-message status-${type}`; // Reuse status classes
            messageElement.textContent = message;
             // Insert before the save button if it exists, otherwise at the end of the container
            this.containerElement.insertBefore(messageElement, this.saveButton || null);

            // Remove message after a few seconds
            setTimeout(() => {
                messageElement.remove();
            }, type === 'success' ? 5000 : 10000); // Keep errors longer
        }
     }

     /**
      * Get the current input value for a specific service's URL.
      * Useful if another module needs to know the user-entered URL before saving.
      * @param {string} serviceName
      * @returns {string|null} The current URL string from the input, or null if the input is not found.
      */
     getServiceUrlInput(serviceName) {
         const input = this.containerElement.querySelector(`.service-config-list input[data-service-name="${serviceName}"]`);
         return input ? input.value.trim() : null;
     }

     /**
      * Get all service URL input values.
      * @returns {object} An object mapping service names to their current URL input values.
      */
     getAllServiceUrlInputs() {
         const urls = {};
         const inputs = this.containerElement.querySelectorAll('.service-config-list input[type="url"]');
         inputs.forEach(input => {
             const serviceName = input.dataset.serviceName;
             if (serviceName) {
                 urls[serviceName] = input.value.trim();
             }
         });
         return urls;
     }
}