// AutoCaptions Web Refactored - Main Application
// Point d'entrée principal avec structure modulaire

import { ApiClient } from './modules/api-client.js';
import { ServiceStatus } from './modules/service-status.js';
import { FileUpload } from './modules/file-upload.js';
import { ConfigManagerUI } from './modules/config-manager-ui.js';
import { TranscriptionUI } from './modules/transcription-ui.js';
import { TranscriptionEditorUI } from './modules/transcription-editor-ui.js';
import { CaptionRenderingUI } from './modules/caption-rendering-ui.js';

class AutoCaptionsApp {
    constructor() {
        // Initialize API client first
        this.apiClient = new ApiClient();
        
        // Initialize all modules
        this.serviceStatus = new ServiceStatus(this.apiClient);
        this.fileUpload = new FileUpload(this.apiClient);
        this.configManagerUI = new ConfigManagerUI(this.apiClient);
        this.transcriptionUI = new TranscriptionUI(this.apiClient);
        this.transcriptionEditorUI = new TranscriptionEditorUI(this.apiClient);
        this.captionRenderingUI = new CaptionRenderingUI(this.apiClient);
        
        // Application state
        this.state = {
            currentFile: null,
            transcriptionData: null,
            uploadId: null
        };
        
        console.log('🚀 AutoCaptions App - Initialized');
    }

    async init() {
        try {
            console.log('🔄 AutoCaptions App - Starting initialization...');
            
            // Initialize all modules
            await this.serviceStatus.init();
            await this.fileUpload.init();
            await this.configManagerUI.init();
            await this.transcriptionUI.init();
            await this.transcriptionEditorUI.init();
            await this.captionRenderingUI.init();
            
            // Set up global event listeners
            this.setupGlobalEventListeners();
            
            // Start service monitoring
            this.serviceStatus.startMonitoring();
            
            console.log('✅ AutoCaptions App - Ready');
            
        } catch (error) {
            console.error('❌ AutoCaptions App - Initialization failed:', error);
            this.showNotification('error', 'Initialization Error', 'Failed to start the application');
        }
    }

    setupGlobalEventListeners() {
        // Keyboard shortcuts
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.configManagerUI.closeModal();
                this.hideNotification();
            }

            if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
                event.preventDefault();
                this.configManagerUI.openModal();
            }
        });

        // Handle page visibility changes for service monitoring
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('📱 Tab hidden - pausing service monitoring');
                this.serviceStatus.pauseMonitoring();
            } else {
                console.log('📱 Tab visible - resuming service monitoring');
                this.serviceStatus.resumeMonitoring();
            }
        });

        // Listen for custom events between modules
        document.addEventListener('fileUploaded', (event) => {
            this.state.currentFile = event.detail.file;
            this.state.uploadId = event.detail.uploadId;
            console.log('📁 File uploaded:', event.detail);
        });

        document.addEventListener('transcriptionCompleted', (event) => {
            this.state.transcriptionData = event.detail.data;
            console.log('📝 Transcription completed:', event.detail);
        });

        document.addEventListener('serviceConfigUpdated', () => {
            // Refresh service status after config update
            this.serviceStatus.checkHealth();
        });
    }

    // Notification system
    showNotification(type, title, message) {
        const notification = document.getElementById('notification');
        const iconContainer = document.getElementById('notification-icon');
        const titleElement = document.getElementById('notification-title');
        const messageElement = document.getElementById('notification-message');

        if (!notification || !iconContainer || !titleElement || !messageElement) {
            console.warn('Notification elements not found');
            return;
        }

        // Set notification content
        titleElement.textContent = title;
        messageElement.textContent = message;

        // Set appropriate icon and colors
        let iconSvg;
        let borderColor;

        switch (type) {
            case 'success':
                iconSvg = `
                    <div class="w-6 h-6 text-green-600">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                `;
                borderColor = 'border-green-200';
                break;
            case 'error':
                iconSvg = `
                    <div class="w-6 h-6 text-red-600">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                `;
                borderColor = 'border-red-200';
                break;
            case 'warning':
                iconSvg = `
                    <div class="w-6 h-6 text-yellow-600">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                `;
                borderColor = 'border-yellow-200';
                break;
            default:
                iconSvg = `
                    <div class="w-6 h-6 text-blue-600">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                `;
                borderColor = 'border-blue-200';
        }

        iconContainer.innerHTML = iconSvg;

        // Update notification styling
        const notificationContent = notification.querySelector('.bg-white');
        notificationContent.className = `bg-white rounded-lg shadow-lg border ${borderColor} p-4 max-w-sm`;

        // Show notification
        notification.classList.remove('hidden');

        // Auto-hide after 5 seconds
        setTimeout(() => {
            this.hideNotification();
        }, 5000);
    }

    hideNotification() {
        const notification = document.getElementById('notification');
        if (notification) {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                notification.classList.add('hidden');
                notification.style.transform = 'translateX(0)';
            }, 300);
        }
    }

    // Utility functions
    static formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    static formatDuration(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = Math.floor(seconds % 60);

        if (hours > 0) {
            return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
        } else {
            return `${minutes}:${secs.toString().padStart(2, '0')}`;
        }
    }

    static validateVideoFile(file) {
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
            throw new Error(`File too large. Maximum size is ${AutoCaptionsApp.formatFileSize(maxSize)}.`);
        }

        return true;
    }

    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

// Initialize the application when DOM is ready
document.addEventListener('DOMContentLoaded', async () => {
    window.app = new AutoCaptionsApp();
    await window.app.init();
});

// Export for global access
export default AutoCaptionsApp;