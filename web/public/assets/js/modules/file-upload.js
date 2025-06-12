// modules/file-upload.js
// Gère l'upload de fichiers avec drag & drop, validation et affichage des informations

export class FileUpload {
    constructor(apiClient) {
        this.apiClient = apiClient;
        this.selectedFile = null;
        this.uploadId = null;
        
        // DOM elements
        this.dropZone = null;
        this.fileInput = null;
        this.fileInfo = null;
        this.uploadSection = null;
        this.actionButtons = null;
        this.processingSection = null;
    }

    async init() {
        console.log('📁 FileUpload - Initializing...');
        
        // Get DOM elements
        this.dropZone = document.getElementById('file-drop-zone');
        this.fileInput = document.getElementById('video-input');
        this.fileInfo = document.getElementById('file-info');
        this.uploadSection = document.getElementById('upload-section');
        this.actionButtons = document.getElementById('action-buttons');
        this.processingSection = document.getElementById('processing-section');

        if (!this.dropZone || !this.fileInput) {
            console.warn('FileUpload - Required DOM elements not found');
            return;
        }

        this.setupEventListeners();
        console.log('✅ FileUpload - Ready');
    }

    setupEventListeners() {
        // Click to upload
        this.dropZone.addEventListener('click', () => this.fileInput.click());

        // Drag and drop events
        this.dropZone.addEventListener('dragover', (e) => this.handleDragOver(e));
        this.dropZone.addEventListener('dragleave', (e) => this.handleDragLeave(e));
        this.dropZone.addEventListener('drop', (e) => this.handleDrop(e));

        // File input change
        this.fileInput.addEventListener('change', (e) => this.handleFileSelect(e));

        // Clear file functionality
        const clearButton = document.querySelector('#file-info button[onclick*="clearFile"]');
        if (clearButton) {
            clearButton.onclick = () => this.clearFile();
        }
    }

    handleDragOver(event) {
        event.preventDefault();
        event.currentTarget.classList.add('drag-over');
    }

    handleDragLeave(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('drag-over');
    }

    handleDrop(event) {
        event.preventDefault();
        event.currentTarget.classList.remove('drag-over');

        const files = event.dataTransfer.files;
        if (files.length > 0) {
            this.handleFile(files[0]);
        }
    }

    handleFileSelect(event) {
        const files = event.target.files;
        if (files.length > 0) {
            this.handleFile(files[0]);
        }
    }

    async handleFile(file) {
        try {
            console.log('📁 FileUpload - Processing file:', file.name);
            
            // Validate file
            this.validateFile(file);

            this.selectedFile = file;
            this.showFileInfo(file);
            this.showActionButtons();

            // Upload file to server
            await this.uploadFile(file);

        } catch (error) {
            console.error('📁 FileUpload - Error:', error);
            window.app.showNotification('error', 'Invalid File', error.message);
            this.clearFile();
        }
    }

    validateFile(file) {
        const allowedTypes = [
            'video/mp4',
            'video/mov',
            'video/avi',
            'video/mkv',
            'video/webm'
        ];
        const maxSize = 500 * 1024 * 1024; // 500MB

        if (!allowedTypes.includes(file.type)) {
            throw new Error('Unsupported file type. Please upload MP4, MOV, AVI, MKV, or WebM files.');
        }

        if (file.size > maxSize) {
            throw new Error(`File too large. Maximum size is ${this.formatFileSize(maxSize)}.`);
        }

        return true;
    }

    async uploadFile(file) {
        try {
            console.log('📁 FileUpload - Uploading file to server...');
            
            // Get video duration before uploading
            let videoDuration = null;
            try {
                videoDuration = await this.getVideoDuration(file);
                console.log('📁 FileUpload - Video duration:', videoDuration);
            } catch (error) {
                console.warn('📁 FileUpload - Could not get video duration:', error);
            }
            
            const formData = new FormData();
            formData.append('videoFile', file);
            
            // Add video metadata
            if (videoDuration !== null) {
                formData.append('duration', videoDuration.toString());
            }
            formData.append('fileSize', file.size.toString());
            formData.append('originalName', file.name);

            const response = await this.apiClient.post('/api/upload', formData);

            if (response.success) {
                this.uploadId = response.data.uploadId;
                console.log('✅ FileUpload - File uploaded with ID:', this.uploadId);
                
                // Store metadata locally
                this.selectedFile.duration = videoDuration;
                
                // Store file info in sessionStorage for cross-page access
                const fileInfo = {
                    name: file.name,
                    originalName: file.name,
                    size: file.size,
                    duration: videoDuration,
                    uploadId: this.uploadId,
                    uploadTimestamp: Date.now()
                };
                sessionStorage.setItem('uploaded_file_info', JSON.stringify(fileInfo));
                
                // Dispatch event for other modules
                document.dispatchEvent(new CustomEvent('fileUploaded', {
                    detail: {
                        file: file,
                        uploadId: this.uploadId,
                        serverPath: response.data.filePath,
                        duration: videoDuration
                    }
                }));

                window.app.showNotification('success', 'File Uploaded', 'Video file has been uploaded successfully');
            } else {
                throw new Error(response.error || 'Upload failed');
            }

        } catch (error) {
            console.error('📁 FileUpload - Upload failed:', error);
            throw new Error(`Upload failed: ${error.message}`);
        }
    }

    showFileInfo(file) {
        if (!this.fileInfo) return;

        const fileName = document.getElementById('file-name');
        const fileSize = document.getElementById('file-size');
        const fileDuration = document.getElementById('file-duration');

        if (fileName) fileName.textContent = file.name;
        if (fileSize) fileSize.textContent = this.formatFileSize(file.size);
        if (fileDuration) fileDuration.textContent = 'Analyzing...';

        this.fileInfo.classList.remove('hidden');

        // Get video duration
        this.getVideoDuration(file).then(duration => {
            if (fileDuration) {
                fileDuration.textContent = this.formatDuration(duration);
            }
        }).catch(() => {
            if (fileDuration) {
                fileDuration.textContent = 'Unknown duration';
            }
        });
    }

    showActionButtons() {
        if (this.actionButtons) {
            this.actionButtons.classList.remove('hidden');
        }
    }

    clearFile() {
        console.log('📁 FileUpload - Clearing file');
        
        this.selectedFile = null;
        this.uploadId = null;
        
        // Clear sessionStorage
        sessionStorage.removeItem('uploaded_file_info');
        
        if (this.fileInput) this.fileInput.value = '';
        if (this.fileInfo) this.fileInfo.classList.add('hidden');
        if (this.actionButtons) this.actionButtons.classList.add('hidden');
        if (this.processingSection) this.processingSection.classList.add('hidden');
        if (this.uploadSection) this.uploadSection.classList.remove('hidden');

        // Hide transcription and rendering sections
        const transcriptionSection = document.getElementById('transcription-section');
        const renderingSection = document.getElementById('caption-rendering-section');
        
        if (transcriptionSection) transcriptionSection.classList.add('hidden');
        if (renderingSection) renderingSection.classList.add('hidden');

        // Delete uploaded file from server
        if (this.uploadId) {
            this.deleteUploadedFile();
        }

        // Dispatch event
        document.dispatchEvent(new CustomEvent('fileCleared'));
    }

    async deleteUploadedFile() {
        try {
            await this.apiClient.delete('/api/upload');
            console.log('🗑️ FileUpload - Uploaded file deleted from server');
        } catch (error) {
            console.error('🗑️ FileUpload - Failed to delete uploaded file:', error);
        }
    }

    getVideoDuration(file) {
        return new Promise((resolve, reject) => {
            const video = document.createElement('video');
            video.preload = 'metadata';

            video.onloadedmetadata = function() {
                window.URL.revokeObjectURL(video.src);
                resolve(video.duration);
            };

            video.onerror = function() {
                reject(new Error('Could not load video'));
            };

            video.src = URL.createObjectURL(file);
        });
    }

    showProcessing(message = 'Processing your video...') {
        if (this.uploadSection) this.uploadSection.classList.add('hidden');
        if (this.actionButtons) this.actionButtons.classList.add('hidden');
        if (this.processingSection) {
            this.processingSection.classList.remove('hidden');
            const messageElement = document.getElementById('processing-message');
            if (messageElement) messageElement.textContent = message;
        }
    }

    hideProcessing() {
        if (this.processingSection) this.processingSection.classList.add('hidden');
        if (this.uploadSection) this.uploadSection.classList.remove('hidden');
    }

    // Utility functions
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    formatDuration(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);

        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        } else {
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }
    }

    // Getters for other modules
    getCurrentFile() {
        return this.selectedFile;
    }

    getUploadId() {
        return this.uploadId;
    }
}