/**
 * CaptionRenderingUI
 * Manages the UI flow and interaction for caption styling and video rendering.
 */
export class CaptionRenderingUI {
    /**
     * @param {ApiClient} apiClient - An instance of the ApiClient.
     * @param {string} renderingSectionSelector - Selector for the main section containing rendering options and buttons.
     * @param {string} ffmpegButtonSelector - Selector for the FFmpeg generate button.
     * @param {string} remotionButtonSelector - Selector for the Remotion generate button.
     * @param {string} [stylingOptionsContainerSelector=null] - Optional selector for a container where styling options will be loaded.
     */
    constructor(
        apiClient,
        renderingSectionSelector,
        ffmpegButtonSelector,
        remotionButtonSelector,
        stylingOptionsContainerSelector = null
    ) {
        this.apiClient = apiClient;
        this.renderingSection = document.querySelector(renderingSectionSelector);
        this.ffmpegButton = document.querySelector(ffmpegButtonSelector);
        this.remotionButton = document.querySelector(remotionButtonSelector);
        this.stylingOptionsContainer = stylingOptionsContainerSelector ? document.querySelector(stylingOptionsContainerSelector) : null;

        // Ensure essential elements exist for basic functionality
        if (!this.renderingSection) {
            console.error(`CaptionRenderingUI: Missing required rendering section element with selector "${renderingSectionSelector}".`);
            return; // Cannot proceed
        }
        if (!this.ffmpegButton || !this.remotionButton) {
             console.warn("CaptionRenderingUI: Missing one or both generate buttons. Rendering functionality will be limited.");
        }
         if (stylingOptionsContainerSelector && !this.stylingOptionsContainer) {
             console.warn(`CaptionRenderingUI: Styling options container element with selector "${stylingOptionsContainerSelector}" not found.`);
         }


        console.log("CaptionRenderingUI initialized.");
    }

    /**
     * Initializes the CaptionRenderingUI module by setting up event listeners and initial states.
     */
    initialize() {
        if (!this.renderingSection) {
            return; // Cannot initialize if essential element is missing
        }

        // Listen for the event dispatched after transcription is completed
        document.addEventListener('transcriptionCompleted', this._handleTranscriptionCompleted.bind(this));

        // Set initial UI state (hide the rendering section)
        this.renderingSection.classList.add('hidden');

        // TODO: Add event listeners for generate buttons later
        // if (this.ffmpegButton) this.ffmpegButton.addEventListener('click', this._handleFfmpegButtonClick.bind(this));
        // if (this.remotionButton) this.remotionButton.addEventListener('click', this._handleRemotionButtonClick.bind(this));


        console.log("CaptionRenderingUI event listener set up.");
    }

    /**
     * Handles the custom transcriptionCompleted event.
     * Makes the caption editing/rendering section visible.
     * @param {CustomEvent} event - The transcriptionCompleted event.
     */
    _handleTranscriptionCompleted(event) {
        console.log('CaptionRenderingUI: Transcription completed event received. Making rendering section visible.', event.detail);

        if (!this.renderingSection) return; // Cannot proceed if essential element is missing

        // Make the rendering section visible
        this.renderingSection.classList.remove('hidden');

        // Make the generate buttons visible
        if (this.ffmpegButton) this.ffmpegButton.classList.remove('hidden');
        if (this.remotionButton) this.remotionButton.classList.remove('hidden');

        // TODO: Potentially fetch styling options here if the container exists
        // if (this.stylingOptionsContainer) {
        //     this.fetchStylingOptions();
        // }
    }

    // TODO: Add methods for:
    // - fetchStylingOptions(): Calls API to get styling options (presets, fonts)
    // - renderStylingOptions(options): Renders forms/controls for styling
    // - gatherStylingConfig(): Collects user's chosen styles from the UI
    // - handleFfmpegButtonClick(): Calls backend API to generate video with FFmpeg
    // - handleRemotionButtonClick(): Calls backend API to generate video with Remotion
    // - updateGenerationStatus(message, type): Updates UI during generation

}