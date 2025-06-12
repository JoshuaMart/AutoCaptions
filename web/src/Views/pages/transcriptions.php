<?php
// Views/pages/transcriptions.php
$pageTitle = "Edit Transcription - AutoCaptions";
$pageDescription =
    "Review and edit your video transcription before generating captions";
?>

<!-- Page Title -->
<div class="text-center mb-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">
        Transcription & Editing
    </h1>
    <p class="text-lg text-gray-600">
        Review and edit your video transcription before generating captions
    </p>
</div>

<!-- Progress Steps -->
<div class="mb-8">
    <div class="flex items-center justify-center space-x-8">
        <!-- Step 1: Upload -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">✓</div>
            <span class="ml-2 text-sm font-medium text-green-600">Upload</span>
        </div>
        <div class="flex-1 h-px bg-green-600"></div>

        <!-- Step 2: Transcription (Current) -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium">2</div>
            <span class="ml-2 text-sm font-medium text-blue-600">Transcription</span>
        </div>
        <div class="flex-1 h-px bg-gray-300"></div>

        <!-- Step 3: Service Choice -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">3</div>
            <span class="ml-2 text-sm font-medium text-gray-500">Service Choice</span>
        </div>
        <div class="flex-1 h-px bg-gray-300"></div>

        <!-- Step 4: Configuration -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">4</div>
            <span class="ml-2 text-sm font-medium text-gray-500">Configuration</span>
        </div>
        <div class="flex-1 h-px bg-gray-300"></div>

        <!-- Step 5: Generate -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">5</div>
            <span class="ml-2 text-sm font-medium text-gray-500">Generate</span>
        </div>
    </div>
</div>

<!-- Video Information Card -->
<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="flex items-center space-x-4">
        <div class="flex-shrink-0">
            <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
        </div>
        <div class="flex-1">
            <h3 class="text-lg font-medium text-gray-900" id="video-filename">
                Loading video info...
            </h3>
            <div class="flex items-center space-x-4 text-sm text-gray-500">
                <span id="video-duration">Duration: --:--</span>
                <span id="caption-count">Captions: --</span>
                <span id="transcription-language">Language: --</span>
                <span id="processing-time">Processing: --ms</span>
            </div>
        </div>

        <!-- Transcription Settings -->
        <div class="flex space-x-2">
            <button type="button"
                    onclick="autoSplitCaptions()"
                    title="Auto-split long captions"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
            </button>

            <button type="button"
                    onclick="validateTimestamps()"
                    title="Validate all timestamps"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </button>

            <button type="button"
                    onclick="exportTranscription()"
                    title="Export transcription as JSON"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </button>

            <button type="button"
                    onclick="window.location.href='/'"
                    title="Upload different video"
                    class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
            </button>
        </div>
    </div>
</div>

<!-- Transcription Generation Card -->
<div id="transcription-generation-card" class="bg-white rounded-lg shadow-lg p-8 mb-8">
    <!-- Initial State: Generate Transcription -->
    <div id="transcription-generate-section">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Generate AI Transcription</h3>
            <p class="text-gray-600 mb-6">
                Our AI will extract the audio and create accurate captions with timestamps for your video.
            </p>
            <button type="button"
                    onclick="app.transcriptionUI.startTranscription()"
                    id="start-transcription-btn"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
                Generate Transcription
            </button>
        </div>
    </div>

    <!-- Processing State -->
    <div id="transcription-processing-section" class="hidden text-center">
        <div class="mb-6">
            <div class="animate-spin mx-auto h-12 w-12 text-blue-600">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Processing Your Video</h3>
        <p class="text-sm text-gray-500 mb-4" id="processing-message">
            Extracting audio and generating transcription...
        </p>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%" id="processing-progress"></div>
        </div>
    </div>
</div>

<!-- Transcription Editor Section (Initially hidden) -->
<div id="transcription-section" class="hidden bg-white rounded-lg shadow-lg p-8 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Edit Transcription</h2>
        <div class="flex space-x-3">
            <button type="button"
                    onclick="app.transcriptionEditorUI.addSegment()"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Segment
            </button>
        </div>
    </div>

    <!-- Transcription Editor Container -->
    <div id="transcription-editor-container" class="space-y-4">
        <!-- Will be populated by transcription-editor-ui.js -->
    </div>

    <!-- Save and Continue Actions -->
    <div class="mt-8 flex justify-between">
        <button type="button"
                onclick="app.transcriptionEditorUI.saveTranscription()"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
            </svg>
            Save Changes
        </button>

        <button type="button"
                onclick="proceedToServiceChoice()"
                id="generate-video-btn"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:bg-gray-400 disabled:cursor-not-allowed">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            Generate Video
        </button>
    </div>
</div>

<!-- Templates for JavaScript -->
<template id="transcription-segment-template">
    <div class="bg-gray-50 rounded-lg p-4 mb-4" data-segment-index="">
        <div class="flex justify-between items-start mb-3">
            <div class="flex space-x-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Start</label>
                    <input type="text" class="segment-start-time w-20 text-xs px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="00:00.000">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">End</label>
                    <input type="text" class="segment-end-time w-20 text-xs px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent" value="00:00.000">
                </div>
            </div>
            <div class="flex space-x-2">
                <button class="button-split-segment text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500">Split</button>
                <button class="button-merge-segment-prev text-xs px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500">Merge</button>
                <button class="button-delete-segment text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500">Delete</button>
            </div>
        </div>
        <div class="segment-text-editor border border-gray-300 rounded-md p-3 min-h-16 focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent" contenteditable="true">
            <!-- Words will be populated here -->
        </div>
    </div>
</template>

<template id="transcription-word-template">
    <span class="word inline-block px-1 py-0.5 mr-1 mb-1 rounded hover:bg-blue-100 cursor-text focus:outline-none focus:ring-2 focus:ring-blue-300" contenteditable="false" data-start-ms="" data-end-ms="">
        <!-- text content -->
    </span>
</template>

<script>
// Page-specific function to proceed to service choice
function proceedToServiceChoice() {
    // Ensure transcription is saved before proceeding
    if (app.transcriptionEditorUI) {
        app.transcriptionEditorUI.saveTranscription();
    }

    // Redirect to service choice page
    window.location.href = '/service-choice';
}

// Functions for video info card buttons
function autoSplitCaptions() {
    if (app.transcriptionEditorUI && app.transcriptionEditorUI.autoSplitSegments) {
        app.transcriptionEditorUI.autoSplitSegments();
        app.showNotification('info', 'Auto Split', 'Long captions have been automatically split');
    } else {
        app.showNotification('warning', 'Not Available', 'Auto-split feature is not available yet');
    }
}

function validateTimestamps() {
    if (app.transcriptionEditorUI && app.transcriptionEditorUI.validateTimestamps) {
        const result = app.transcriptionEditorUI.validateTimestamps();
        if (result.valid) {
            app.showNotification('success', 'Validation Complete', 'All timestamps are valid');
        } else {
            app.showNotification('error', 'Validation Failed', `Found ${result.errors.length} timestamp errors`);
        }
    } else {
        app.showNotification('warning', 'Not Available', 'Timestamp validation feature is not available yet');
    }
}

function exportTranscription() {
    const transcriptionData = app.transcriptionUI.getTranscriptionData();
    if (transcriptionData) {
        const dataStr = JSON.stringify(transcriptionData, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        
        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = 'transcription.json';
        link.click();
        
        app.showNotification('success', 'Export Complete', 'Transcription exported as JSON file');
    } else {
        app.showNotification('error', 'Export Failed', 'No transcription data available to export');
    }
}

// Initialize page when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Load current video information from session
    app.transcriptionUI.loadVideoInfo();

    // Check if transcription already exists
    app.transcriptionUI.checkExistingTranscription();
});
</script>
