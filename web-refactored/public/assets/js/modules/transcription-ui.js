// modules/transcription-ui.js
// Gère le processus de transcription et l'interface utilisateur associée

export class TranscriptionUI {
    constructor(apiClient) {
        this.apiClient = apiClient;
        this.transcriptionData = null;
        this.isProcessing = false;

        // DOM elements
        this.transcriptionSection = null;
        this.processingSection = null;
        this.uploadSection = null;
        this.actionButtons = null;
    }

    async init() {
        console.log('📝 TranscriptionUI - Initializing...');
        
        // Get DOM elements
        this.transcriptionSection = document.getElementById('transcription-section');
        this.processingSection = document.getElementById('processing-section');
        this.uploadSection = document.getElementById('upload-section');
        this.actionButtons = document.getElementById('action-buttons');

        this.setupEventListeners();
        console.log('✅ TranscriptionUI - Ready');
    }

    setupEventListeners() {
        // Listen for file upload events
        document.addEventListener('fileUploaded', (event) => {
            console.log('📝 TranscriptionUI - File uploaded, ready for transcription');
        });

        document.addEventListener('fileCleared', () => {
            this.hideTranscriptionSection();
            this.transcriptionData = null;
        });

        // Fix the transcribe button onclick reference
        const transcribeBtn = document.getElementById('transcribe-btn');
        if (transcribeBtn) {
            transcribeBtn.onclick = () => this.startTranscription();
        }
    }

    async startTranscription() {
        if (this.isProcessing) {
            console.warn('📝 TranscriptionUI - Already processing transcription');
            return;
        }

        const fileUpload = window.app.fileUpload;
        const currentFile = fileUpload.getCurrentFile();

        if (!currentFile) {
            window.app.showNotification('error', 'No File', 'Please select a video file first');
            return;
        }

        try {
            this.isProcessing = true;
            console.log('📝 TranscriptionUI - Starting transcription for:', currentFile.name);
            
            // Show processing UI
            this.showProcessingUI();

            // Prepare the request
            const formData = new FormData();
            formData.append('file', currentFile);
            formData.append('service', 'whisper-cpp'); // Default service

            // Start transcription via API proxy
            const response = await this.apiClient.post('/api/transcription/start', formData);

            if (response.success) {
                this.transcriptionData = response.data;
                console.log('✅ TranscriptionUI - Transcription completed');
                
                // Dispatch event for other modules
                document.dispatchEvent(new CustomEvent('transcriptionCompleted', {
                    detail: {
                        data: this.transcriptionData
                    }
                }));

                window.app.showNotification('success', 'Transcription Complete', 'Your video has been transcribed successfully');
                
                // Show transcription section and hide processing
                this.showTranscriptionSection();
                this.hideProcessingUI();

            } else {
                throw new Error(response.error || 'Transcription failed');
            }

        } catch (error) {
            console.error('📝 TranscriptionUI - Transcription failed:', error);
            window.app.showNotification('error', 'Transcription Failed', error.message);
            
            // Reset UI
            this.hideProcessingUI();
            this.showUploadUI();

        } finally {
            this.isProcessing = false;
        }
    }

    showProcessingUI() {
        if (this.uploadSection) this.uploadSection.classList.add('hidden');
        if (this.actionButtons) this.actionButtons.classList.add('hidden');
        if (this.processingSection) {
            this.processingSection.classList.remove('hidden');
            
            // Update processing message
            const messageElement = document.getElementById('processing-message');
            if (messageElement) {
                messageElement.textContent = 'Extracting audio and generating transcription...';
            }
        }
    }

    hideProcessingUI() {
        if (this.processingSection) this.processingSection.classList.add('hidden');
    }

    showUploadUI() {
        if (this.uploadSection) this.uploadSection.classList.remove('hidden');
        if (this.actionButtons) this.actionButtons.classList.remove('hidden');
    }

    showTranscriptionSection() {
        if (this.transcriptionSection) {
            this.transcriptionSection.classList.remove('hidden');
            
            // Initialize transcription editor with data
            if (window.app.transcriptionEditorUI) {
                window.app.transcriptionEditorUI.loadTranscription(this.transcriptionData);
            }
        }
    }

    hideTranscriptionSection() {
        if (this.transcriptionSection) {
            this.transcriptionSection.classList.add('hidden');
        }
    }

    // Public API methods
    getTranscriptionData() {
        return this.transcriptionData;
    }

    hasTranscription() {
        return this.transcriptionData !== null;
    }

    clearTranscription() {
        this.transcriptionData = null;
        this.hideTranscriptionSection();
        
        // Clear transcription data from server session
        this.apiClient.delete('/api/transcription/clear').catch(error => {
            console.warn('📝 TranscriptionUI - Failed to clear transcription from server:', error);
        });

        console.log('📝 TranscriptionUI - Transcription data cleared');
    }

    async retryTranscription() {
        if (this.isProcessing) {
            return;
        }

        console.log('📝 TranscriptionUI - Retrying transcription...');
        await this.startTranscription();
    }
}