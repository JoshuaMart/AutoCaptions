<?php
// Views/pages/transcriptions.php
$pageTitle = 'Edit Transcription - AutoCaptions';
$pageDescription = 'Review and edit your video transcription before generating captions';
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
<div class="bg-white rounded-lg shadow-lg p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Current Video</h2>
    <div id="video-info" class="flex items-center space-x-4">
        <div class="flex-shrink-0">
            <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900" id="current-file-name">Loading...</p>
            <p class="text-sm text-gray-500">
                <span id="current-file-size">-</span> •
                <span id="current-file-duration">-</span>
            </p>
        </div>
        <div class="ml-auto">
            <button type="button"
                    onclick="window.location.href='/'"
                    class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                Upload Different Video
            </button>
        </div>
    </div>
</div>

<!-- Transcription Generation Card -->
<div class="bg-white rounded-lg shadow-lg p-8 mb-8">
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

    <!-- Transcription Complete State -->
    <div id="transcription-complete-section" class="hidden">
        <div class="flex items-center justify-center mb-6">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-lg font-semibold text-gray-900">Transcription Complete!</h3>
                <p class="text-sm text-gray-500">Your video has been successfully transcribed. Review and edit below.</p>
            </div>
        </div>
    </div>
</div>

<!-- Transcription Editor Section (Initially hidden) -->
<div id="transcription-section" class="hidden bg-white rounded-lg shadow-lg p-8 mb-8">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Edit Transcription</h2>
        <div class="flex space-x-3">
            <button type="button"
                    onclick="app.transcriptionEditorUI.regenerateTranscription()"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Regenerate
            </button>
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
    
    <!-- Transcription Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-gray-900">Segments</p>
                    <p class="text-lg font-semibold text-gray-700" id="segment-count">0</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-gray-900">Words</p>
                    <p class="text-lg font-semibold text-gray-700" id="word-count">0</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-gray-900">Duration</p>
                    <p class="text-lg font-semibold text-gray-700" id="transcription-duration">0:00</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Transcription Editor Container -->
    <div id="transcription-editor-container" class="space-y-4">
        <!-- Will be populated by transcription-editor-ui.js -->
    </div>
    
    <!-- Editor Tools -->
    <div class="mt-6 flex flex-wrap gap-3">
        <button type="button"
                onclick="app.transcriptionEditorUI.validateTimestamps()"
                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Validate Timestamps
        </button>
        <button type="button"
                onclick="app.transcriptionEditorUI.autoAdjustTimings()"
                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Auto-Adjust Timings
        </button>
        <button type="button"
                onclick="app.transcriptionEditorUI.optimizeSegments()"
                class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Optimize Segments
        </button>
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

// Initialize page when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Load current video information from session
    app.transcriptionUI.loadVideoInfo();
    
    // Check if transcription already exists
    app.transcriptionUI.checkExistingTranscription();
});
</script>
