import { ApiClient } from './modules/api-client.js';
import { FileUpload } from './modules/file-upload.js';
import { ServiceStatus } from './modules/service-status.js';
// Import other modules as they are created, e.g., for transcription editing, config UI, etc.
// import { TranscriptionEditor } from './modules/transcription-editor.js';
import { ConfigManagerUI } from './modules/config-manager-ui.js';
import { TranscriptionEditorUI } from './modules/transcription-editor-ui.js'; // Import the TranscriptionEditorUI module
import { TranscriptionUI } from './modules/transcription-ui.js'; // Import the TranscriptionUI module
import { CaptionRenderingUI } from './modules/caption-rendering-ui.js'; // Import the CaptionRenderingUI module

class AutoCaptionsApp {
    constructor() {
        this.apiClient = new ApiClient('/api'); // Base path for all API calls
        this.fileUpload = new FileUpload(this.apiClient, '#uploadForm', '#videoFile', '#uploadProgress', '#uploadStatus');
        this.serviceStatus = new ServiceStatus(this.apiClient, '#serviceStatusContainer');
        // Selectors from home.php for config UI
        this.configManagerUI = new ConfigManagerUI(this.apiClient, '#serviceConfigContainer', '#serviceConfigRowTemplate');

        // Selectors from home.php for transcription UI
        this.transcriptionUI = new TranscriptionUI(
            this.apiClient,
            '#upload-section', // upload section
            '#transcription-processing-section', // transcription processing section
            '#caption-editing-and-rendering-section', // caption editing/rendering section
            '#startTranscriptionButton', // start button
            '#transcriptionReadyMessage', // ready message
            '#transcriptionStatus', // status message area
            '#transcriptionProgressContainer', // progress container
            '#transcriptionProgress', // progress bar
            '#transcriptionProgressMessage' // progress text message
        );

        // Selectors from home.php for transcription editor UI and templates
        this.transcriptionEditorUI = new TranscriptionEditorUI(
             this.apiClient,
            '#transcriptionEditorContainer', // editor container
            '#transcriptionSegmentTemplate', // segment template
            '#transcriptionWordTemplate' // word template
        );

        // Selectors from home.php for caption rendering UI
        this.captionRenderingUI = new CaptionRenderingUI(
            this.apiClient,
            '#caption-editing-and-rendering-section', // main section for rendering
            '#generateFfmpegVideoButton', // ffmpeg button
            '#generateRemotionVideoButton', // remotion button
            '#captionStylingContainer' // container for styling options
        );

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
        // Initialize ConfigManagerUI if its container exists
        if (document.querySelector('#serviceConfigContainer')) {
             this.configManagerUI.initialize();
        }
        // Initialize TranscriptionUI if its main sections exist
        if (document.querySelector('#upload-section') && document.querySelector('#transcription-processing-section') && document.querySelector('#caption-editing-and-rendering-section')) {
             this.transcriptionUI.initialize();
        }
         // Initialize TranscriptionEditorUI if its container exists
        if (document.querySelector('#transcriptionEditorContainer')) {
             this.transcriptionEditorUI.initialize();
        }
        // Initialize CaptionRenderingUI if its main section exists
        if (document.querySelector('#caption-editing-and-rendering-section')) {
            this.captionRenderingUI.initialize();
        }
        // if (document.querySelector('#configModal')) { // This was a placeholder from original code
        //     // this.configManagerUI.initialize(); // The config UI is now directly in the page, not a modal trigger
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