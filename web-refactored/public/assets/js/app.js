import { ApiClient } from './modules/api-client.js';
import { FileUpload } from './modules/file-upload.js';
import { ServiceStatus } from './modules/service-status.js';
// Import other modules as they are created, e.g., for transcription editing, config UI, etc.
// import { TranscriptionEditor } from './modules/transcription-editor.js';
// import { ConfigManagerUI } from './modules/config-manager-ui.js';

class AutoCaptionsApp {
    constructor() {
        this.apiClient = new ApiClient('/api'); // Base path for all API calls
        this.fileUpload = new FileUpload(this.apiClient, '#uploadForm', '#videoFile', '#uploadProgress', '#uploadStatus');
        this.serviceStatus = new ServiceStatus(this.apiClient, '#serviceStatusContainer');
        // this.transcriptionEditor = new TranscriptionEditor(this.apiClient, ...);
        // this.configManagerUI = new ConfigManagerUI(this.apiClient, ...);

        // Global event bus or state manager could be useful for larger apps
        // For now, modules can be relatively independent or accept other modules as dependencies if needed.
        console.log("AutoCaptionsApp initialized");
    }

    init() {
        console.log("Initializing app components...");

        // Initialize core components
        if (document.querySelector('#serviceStatusContainer')) {
            this.serviceStatus.initialize();
            this.serviceStatus.startMonitoring(); // Or call fetchStatus manually when needed
        }

        if (document.querySelector('#uploadForm')) {
            this.fileUpload.initialize();
        }

        // Initialize other components as they are added
        // if (document.querySelector('#transcriptionEditor')) {
        //     this.transcriptionEditor.initialize();
        // }
        // if (document.querySelector('#configModal')) {
        //     this.configManagerUI.initialize();
        // }

        this.setupGlobalEventHandlers();
        console.log("AutoCaptionsApp components initialized.");
    }

    setupGlobalEventHandlers() {
        // Example: Listen for custom events if modules need to communicate indirectly
        // document.addEventListener('fileUploaded', (event) => {
        //     console.log('File uploaded:', event.detail);
        //     // Notify other components, e.g., enable transcription button
        // });

        // Example: Handle global UI elements like theme toggles, modals, etc.
    }
}

// Initialize the application once the DOM is fully loaded
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.autoCaptionsApp === 'undefined') {
        window.autoCaptionsApp = new AutoCaptionsApp();
        window.autoCaptionsApp.init();
    } else {
        console.warn("AutoCaptionsApp already initialized.");
    }
});