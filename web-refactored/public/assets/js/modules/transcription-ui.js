/**
 * TranscriptionUI
 * Manages the UI flow and interaction for the transcription process.
 */
export class TranscriptionUI {
    /**
     * @param {ApiClient} apiClient - An instance of the ApiClient.
     * @param {string} uploadSectionSelector - Selector for the upload section element.
     * @param {string} transcriptionSectionSelector - Selector for the transcription processing section.
     * @param {string} captionEditingSectionSelector - Selector for the caption editing and rendering section.
     * @param {string} startButtonSelector - Selector for the start transcription button.
     * @param {string} readyMessageSelector - Selector for the message indicating transcription is ready to start.
     * @param {string} statusSelector - Selector for the transcription status message area.
     * @param {string} progressContainerSelector - Selector for the transcription progress container.
     * @param {string} progressSelector - Selector for the transcription progress bar (<progress> element).
     * @param {string} progressMessageSelector - Selector for a text message next to the progress bar.
     */
    constructor(
        apiClient,
        uploadSectionSelector,
        transcriptionSectionSelector,
        captionEditingSectionSelector,
        startButtonSelector,
        readyMessageSelector,
        statusSelector,
        progressContainerSelector,
        progressSelector,
        progressMessageSelector
    ) {
        this.apiClient = apiClient;
        this.uploadSection = document.querySelector(uploadSectionSelector);
        this.transcriptionSection = document.querySelector(transcriptionSectionSelector);
        this.captionEditingSection = document.querySelector(captionEditingSectionSelector);

        this.startButton = document.querySelector(startButtonSelector);
        this.readyMessageElement = document.querySelector(readyMessageSelector);
        this.statusElement = document.querySelector(statusSelector);
        this.progressContainer = document.querySelector(progressContainerSelector);
        this.progressBar = document.querySelector(progressSelector);
        this.progressMessageElement = document.querySelector(progressMessageSelector);

        this.transcriptionData = null; // To store the transcription results

        // Ensure essential elements exist
        if (!this.uploadSection || !this.transcriptionSection || !this.captionEditingSection) {
            console.error("TranscriptionUI: Missing required section elements.");
            return; // Cannot proceed without main sections
        }
         if (!this.startButton || !this.readyMessageElement || !this.statusElement) {
            console.error("TranscriptionUI: Missing required transcription UI elements.");
             // Can still manage sections, but core transcription start flow won't work
        }

        console.log("TranscriptionUI initialized.");
    }

    /**
     * Initializes the TranscriptionUI module, setting up event listeners and initial states.
     */
    initialize() {
        // Listen for the custom event dispatched after a successful file upload
        document.addEventListener('fileUploaded', this._handleFileUploaded.bind(this));

        // Set up button click listener
        if (this.startButton) {
            this.startButton.addEventListener('click', this._handleStartButtonClick.bind(this));
        }

        // Set initial UI state (hide transcription/editing sections)
        this.transcriptionSection.classList.add('hidden');
        this.captionEditingSection.classList.add('hidden');
        // Ensure upload section is visible initially
        this.uploadSection.classList.remove('hidden');

        // Hide transcription specific elements initially within its section
         if (this.startButton) this.startButton.classList.add('hidden');
         if (this.readyMessageElement) this.readyMessageElement.classList.add('hidden');
         if (this.statusElement) this.statusElement.classList.add('hidden');
         if (this.progressContainer) this.progressContainer.classList.add('hidden');

        console.log("TranscriptionUI event listeners set up.");
    }

    /**
     * Handles the custom fileUploaded event.
     * @param {CustomEvent} event - The fileUploaded event.
     */
    _handleFileUploaded(event) {
        console.log('TranscriptionUI: File uploaded event received.', event.detail);

        // Transition UI: Hide upload section, show transcription section
        this.uploadSection.classList.add('hidden');
        this.transcriptionSection.classList.remove('hidden');
        this.captionEditingSection.classList.add('hidden'); // Ensure editing section is hidden

        // Update transcription section UI to indicate readiness
        this._updateStatus(''); // Clear previous status
        this._updateProgress(0); // Reset progress

        if (this.readyMessageElement) this.readyMessageElement.classList.remove('hidden');
        if (this.startButton) {
             this.startButton.classList.remove('hidden');
             this.startButton.disabled = false; // Enable the button
             this.startButton.textContent = 'Start Transcription';
        }

        // You might want to store some info from event.detail if needed for transcription API call
        // e.g., file identifier if backend provides one that isn't session based
    }

    /**
     * Handles the click event on the "Start Transcription" button.
     * @param {Event} event - The click event.
     */
    async _handleStartButtonClick(event) {
        event.preventDefault();

        if (!this.startButton || this.startButton.disabled) {
            return; // Button is disabled or not found
        }

        // Disable the button and update UI state
        this.startButton.disabled = true;
        this.startButton.textContent = 'Processing...';
        if (this.readyMessageElement) this.readyMessageElement.classList.add('hidden');

        this._updateStatus('Starting transcription...', 'info');
        if (this.statusElement) this.statusElement.classList.remove('hidden');
        if (this.progressContainer) this.progressContainer.classList.remove('hidden');


        // TODO: Gather transcription options from UI elements if any are added (e.g., language dropdown)
        const transcriptionOptions = {
            // service: 'whisper-cpp', // Default from backend config
            // language: 'en',
            // translateToEnglish: 'false'
        };

        try {
            // Call the backend API to start transcription
            // The backend (/api/transcription/start) knows which file to process from the session.
            const response = await this.apiClient.post('/transcription/start', transcriptionOptions);

            if (response && response.success && response.data && response.data.transcription) {
                this.transcriptionData = response.data.transcription;
                this._updateStatus('Transcription completed successfully!', 'success');
                this._updateProgress(100, 'success');
                console.log('Transcription successful:', response.data.transcription);

                // Dispatch a custom event with the transcription data
                document.dispatchEvent(new CustomEvent('transcriptionCompleted', {
                    detail: {
                        transcription: this.transcriptionData,
                        backendResponse: response
                    }
                }));

                // Transition UI: Hide transcription section, show caption editing section
                this.transcriptionSection.classList.add('hidden');
                this.captionEditingSection.classList.remove('hidden');

            } else {
                 // ApiClient should throw for non-ok, but handle server-side logical errors
                const errorMessage = response?.error?.message || response?.message || 'Transcription failed due to an unexpected server response.';
                 const errorDetails = response?.error?.details?.details ? Object.values(response.error.details.details).join(', ') : (response?.error?.details ? JSON.stringify(response?.error?.details) : ''); // Check nested errors from backend
                this._updateStatus(`Transcription failed: ${errorMessage} ${errorDetails}`, 'error');
                console.error('Transcription failed:', response);
                 this._updateProgress(0, 'error');
            }
        } catch (error) {
            let errorMessage = 'An unexpected error occurred during transcription.';
            if (error.data && error.data.error && error.data.error.message) {
                errorMessage = error.data.error.message;
            } else if (error.message) {
                 errorMessage = error.message;
            }
            this._updateStatus(`Error: ${errorMessage}`, 'error');
            console.error('Transcription API error:', error);
            this._updateProgress(0, 'error');

        } finally {
            // Restore button state on failure, or if needed after success transition
             if (this.startButton && this.transcriptionSection && !this.transcriptionSection.classList.contains('hidden')) {
                this.startButton.disabled = false;
                this.startButton.textContent = 'Start Transcription';
             }
             // Progress bar might stay visible with error state, or hide depending on UI design
             // if (this.progressContainer) this.progressContainer.classList.add('hidden'); // Optional: hide progress on completion/error
        }
    }

     /**
      * Placeholder for updating transcription progress UI.
      * The current backend API is synchronous, so real-time progress isn't feasible without polling or websockets.
      * This method can be used for basic state updates (e.g., 0% -> 50% -> 100%).
      * @param {number} percentage - Progress percentage (0-100).
      * @param {'processing'|'success'|'error'} [state='processing'] - State for visual feedback.
      * @param {string} [message=''] - Optional message to display.
      */
    _updateProgress(percentage, state = 'processing', message = '') {
        if (this.progressBar) {
            if (this.progressBar.tagName === 'PROGRESS') {
                this.progressBar.value = percentage;
            }
             // Add state classes for styling if needed
             this.progressBar.classList.remove('progress-processing', 'progress-success', 'progress-error');
             this.progressBar.classList.add(`progress-${state}`);
        }
        if (this.progressMessageElement) {
            this.progressMessageElement.textContent = message || `${percentage}%`;
             // Add state classes for styling if needed
             this.progressMessageElement.classList.remove('status-info', 'status-success', 'status-error');
             this.progressMessageElement.classList.add(`status-${state === 'processing' ? 'info' : state}`); // Map processing to info style
        }
    }

    /**
     * Updates a status message area within the transcription section.
     * @param {string} message - The message text.
     * @param {'info'|'success'|'error'} [type='info'] - The type of message for styling.
     */
     _updateStatus(message, type = 'info') {
        console.log(`Transcription Status [${type.toUpperCase()}]: ${message}`);
        if (this.statusElement) {
            this.statusElement.textContent = message;
            this.statusElement.className = `status-message status-${type}`;
        }
     }

    // You might add methods here for:
    // - Handling a "Cancel Transcription" button
    // - Fetching transcription data if session is reloaded (_handleFileUploaded might trigger this)
    // - Integrating with a future TranscriptionEditor module (e.g., passing transcriptionData)
}