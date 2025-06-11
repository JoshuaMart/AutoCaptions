/**
 * FileUpload
 * Handles client-side logic for file uploads, including progress and status updates.
 */
export class FileUpload {
    constructor(apiClient, formSelector, fileInputSelector, progressSelector, statusSelector) {
        this.apiClient = apiClient;
        this.formElement = document.querySelector(formSelector);
        this.fileInputElement = document.querySelector(fileInputSelector);
        this.progressElement = document.querySelector(progressSelector); // Could be a <progress> bar or a div
        this.statusElement = document.querySelector(statusSelector);

        if (!this.formElement) {
            console.warn(`FileUpload: Form element with selector "${formSelector}" not found.`);
        }
        if (!this.fileInputElement) {
            console.warn(`FileUpload: File input element with selector "${fileInputSelector}" not found.`);
        }
        // Progress and status elements are optional for basic functionality
    }

    initialize() {
        if (!this.formElement || !this.fileInputElement) {
            console.error("FileUpload: Cannot initialize without form and file input elements.");
            return;
        }

        this.formElement.addEventListener('submit', this._handleFormSubmit.bind(this));
        this.fileInputElement.addEventListener('change', this._handleFileSelection.bind(this));

        this._setInitialStatus();
        console.log("FileUpload initialized.");
    }

    _setInitialStatus() {
        if (this.statusElement) {
            this.statusElement.textContent = 'Please select a video file to upload.';
            this.statusElement.className = 'status-info'; // Use classes for styling
        }
        if (this.progressElement) {
            if (this.progressElement.tagName === 'PROGRESS') {
                this.progressElement.value = 0;
                this.progressElement.max = 100;
            } else { // Assuming it's a div for custom progress bar
                this.progressElement.style.width = '0%';
            }
            this.progressElement.classList.add('hidden'); // Hide until upload starts
        }
    }

    _handleFileSelection(event) {
        if (this.statusElement) {
            const files = event.target.files;
            if (files.length > 0) {
                this.statusElement.textContent = `Selected file: ${files[0].name}`;
                this.statusElement.className = 'status-info';
            } else {
                this.statusElement.textContent = 'No file selected.';
                 this.statusElement.className = 'status-info';
            }
        }
    }

    async _handleFormSubmit(event) {
        event.preventDefault();

        const file = this.fileInputElement.files[0];

        if (!file) {
            this._updateStatus('No file selected. Please choose a file to upload.', 'error');
            return;
        }

        this._updateStatus(`Uploading ${file.name}...`, 'info');
        if (this.progressElement) this.progressElement.classList.remove('hidden');
        // For actual progress, XMLHttpRequest would be needed.
        // With fetch, we can only show "in progress" and then success/failure.
        // Simulating a bit of progress visually for demo:
        this._updateProgress(10); // Initial small progress

        const formData = new FormData();
        formData.append('videoFile', file); // 'videoFile' should match UploadController expected field

        try {
            this._updateProgress(50); // Simulate mid-progress
            const response = await this.apiClient.post('/upload', formData); // ApiClient handles FormData
            this._updateProgress(100);

            if (response && response.success) {
                this._updateStatus(`Successfully uploaded: ${response.data?.uploadedFile?.original_name || file.name}`, 'success');
                console.log('Upload successful:', response);
                // Dispatch a custom event for other components to listen to
                document.dispatchEvent(new CustomEvent('fileUploaded', {
                    detail: {
                        fileName: response.data?.uploadedFile?.name,
                        originalName: response.data?.uploadedFile?.original_name,
                        // sessionIdentifier: response.data?.uploadedFile?.identifier, // If available
                        backendResponse: response
                    }
                }));
                 // Optionally, reset the form or file input
                // this.formElement.reset(); // Resets all form fields
                // this.fileInputElement.value = ''; // Clears file input specifically
                setTimeout(() => { // Hide progress bar after a short delay
                    if (this.progressElement) this.progressElement.classList.add('hidden');
                }, 2000);

            } else {
                // The ApiClient should throw an error for non-ok responses,
                // which will be caught by the catch block.
                // This part handles cases where response.ok is true but server indicates logical failure.
                const errorMessage = response?.error?.message || response?.message || 'Upload failed due to an unexpected server response.';
                this._updateStatus(`Upload failed: ${errorMessage}`, 'error');
                console.error('Upload failed with success false:', response);
                this._updateProgress(0, 'error'); // Reset progress or show error state in progress bar
            }
        } catch (error) {
            let errorMessage = 'Upload failed.';
            if (error.data && error.data.error && error.data.error.message) {
                errorMessage = error.data.error.message;
                if (error.data.error.details && error.data.error.details.details && Array.isArray(error.data.error.details.details)) {
                    errorMessage += ` Details: ${error.data.error.details.details.join(', ')}`;
                } else if (error.data.error.details && typeof error.data.error.details === 'string') {
                     errorMessage += ` Details: ${error.data.error.details}`;
                }
            } else if (error.message) {
                errorMessage = error.message;
            }
            this._updateStatus(`Error: ${errorMessage}`, 'error');
            console.error('Upload error:', error);
            this._updateProgress(0, 'error');
        }
    }

    _updateStatus(message, type = 'info') { // type can be 'info', 'success', 'error'
        if (this.statusElement) {
            this.statusElement.textContent = message;
            this.statusElement.className = `status-${type}`; // e.g., status-info, status-success, status-error
        }
    }

    _updateProgress(percentage, state = 'uploading') { // state: 'uploading', 'success', 'error'
        if (this.progressElement) {
            if (this.progressElement.tagName === 'PROGRESS') {
                this.progressElement.value = percentage;
            } else { // Assuming it's a div for custom progress bar
                this.progressElement.style.width = `${percentage}%`;
            }
            // You can add classes based on state for styling
            this.progressElement.classList.remove('progress-success', 'progress-error');
            if (state === 'success') {
                this.progressElement.classList.add('progress-success');
            } else if (state === 'error') {
                 this.progressElement.classList.add('progress-error');
            }
        }
    }
}