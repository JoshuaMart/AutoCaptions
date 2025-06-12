<?php
// Views/pages/ffmpeg-config.php

$pageTitle = $pageTitle ?? "FFmpeg Configuration - AutoCaptions";
$pageDescription =
    $pageDescription ?? "Customize your caption style and preview the result";
?>

<div class="text-center mb-10">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">FFmpeg Caption Configuration</h1>
    <p class="text-lg text-gray-600">Customize your caption style and preview the result</p>
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

        <!-- Step 2: Transcription -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">✓</div>
            <span class="ml-2 text-sm font-medium text-green-600">Transcription</span>
        </div>
        <div class="flex-1 h-px bg-green-600"></div>

        <!-- Step 3: Service Choice -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">✓</div>
            <span class="ml-2 text-sm font-medium text-green-600">Service Choice</span>
        </div>
        <div class="flex-1 h-px bg-green-600"></div>

        <!-- Step 4: Configuration (Current) -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium">4</div>
            <span class="ml-2 text-sm font-medium text-blue-600">Configuration</span>
        </div>
        <div class="flex-1 h-px bg-gray-300"></div>

        <!-- Step 5: Generate -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">5</div>
            <span class="ml-2 text-sm font-medium text-gray-500">Generate</span>
        </div>
    </div>
</div>

<!-- Main Layout -->
<div class="grid lg:grid-cols-3 gap-8">
    <!-- Configuration Panel -->
    <div class="lg:col-span-2 space-y-6">

        <!-- Preset Selection -->
        <div class="config-section bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Choose a Preset</h3>
                <button type="button" onclick="refreshPresets()" class="text-blue-600 hover:text-blue-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </button>
            </div>

            <div id="presets-container">
                <!-- Loading state -->
                <div id="presets-loading" class="text-center py-8">
                    <div class="animate-spin mx-auto h-8 w-8 text-blue-600 mb-4">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <p class="text-gray-500">Loading presets...</p>
                </div>

                <!-- Presets will be loaded here -->
                <div id="presets-list" class="hidden grid gap-4"></div>
            </div>
        </div>

        <!-- Dynamic Configuration Container -->
        <div id="dynamic-config-container">
            <!-- Configuration sections will be dynamically generated here -->
            <div id="no-preset-message" class="bg-white rounded-lg shadow p-8 text-center">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Select a Preset</h3>
                <p class="text-gray-600">Choose a preset above to configure your caption style options.</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex space-x-4">
            <button type="button"
                    onclick="window.history.back()"
                    class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back
            </button>

            <button type="button"
                    onclick="generateFinalVideo()"
                    id="generate-btn"
                    class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    disabled>
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 002 2v8a2 2 0 002 2z"/>
                </svg>
                Generate Video
            </button>
        </div>
    </div>

    <!-- Preview Panel -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg shadow p-6 sticky top-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Preview</h3>
                <button type="button"
                        onclick="generatePreview()"
                        id="preview-btn"
                        class="ml-4 inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        disabled>
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 002 2v8a2 2 0 002 2z"/>
                    </svg>
                    Generate Preview
                </button>
            </div>

            <!-- Preview Container -->
            <div class="preview-container rounded-lg overflow-hidden" style="aspect-ratio: 9/16;">
                <!-- Placeholder -->
                <div id="preview-placeholder" class="w-full h-full bg-gray-100 flex items-center justify-center">
                    <div class="text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 002 2v8a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm">Select a preset and click<br>"Generate Preview"</p>
                    </div>
                </div>

                <!-- Preview Image -->
                <img id="preview-image" class="w-full h-full object-cover hidden" alt="Caption Preview">

                <!-- Loading State -->
                <div id="preview-loading" class="hidden w-full h-full bg-gray-100 flex items-center justify-center">
                    <div class="text-center">
                        <div class="animate-spin mx-auto h-8 w-8 text-blue-600 mb-4">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600">Generating preview...</p>
                    </div>
                </div>

                <!-- Error State -->
                <div id="preview-error" class="hidden w-full h-full bg-red-50 flex items-center justify-center">
                    <div class="text-center text-red-600">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm">Preview generation failed</p>
                        <button onclick="generatePreview()" class="mt-2 text-xs underline">Try again</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notifications Container -->
<div id="notifications-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

<!-- Custom Styles -->
<style>
    .config-section {
        transition: all 0.3s ease;
    }

    .config-section.collapsed .section-content {
        max-height: 0;
        overflow: hidden;
    }

    .config-section:not(.collapsed) .section-content {
        max-height: 1000px;
    }

    .preview-container {
        background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%),
                    linear-gradient(-45deg, #f0f0f0 25%, transparent 25%),
                    linear-gradient(45deg, transparent 75%, #f0f0f0 75%),
                    linear-gradient(-45deg, transparent 75%, #f0f0f0 75%);
        background-size: 20px 20px;
        background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
    }

    .color-input {
        width: 60px;
        height: 40px;
        padding: 3px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
    }

    .font-preview {
        transition: all 0.2s ease;
    }

    .font-preview:hover {
        transform: scale(1.02);
    }

    .field-group {
        transition: all 0.3s ease;
    }

    .field-group.disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    .field-group.hidden {
        display: none !important;
    }

    .preset-card {
        transition: all 0.2s ease;
    }

    .preset-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .preset-card.selected {
        border-color: #3B82F6 !important;
        background-color: #EFF6FF !important;
    }

    .range-field input[type="range"] {
        -webkit-appearance: none;
        appearance: none;
        height: 4px;
        background: #E5E7EB;
        border-radius: 2px;
        outline: none;
    }

    .range-field input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 16px;
        height: 16px;
        background: #3B82F6;
        border-radius: 50%;
        cursor: pointer;
    }

    .range-field input[type="range"]::-moz-range-thumb {
        width: 16px;
        height: 16px;
        background: #3B82F6;
        border-radius: 50%;
        cursor: pointer;
        border: none;
    }

    /* Color input styling */
    .color-input-group {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .color-input-modern {
        width: 60px;
        height: 40px;
        padding: 3px;
        border: 1px solid #D1D5DB;
        border-radius: 6px;
        cursor: pointer;
        background: transparent;
    }

    .color-input-modern::-webkit-color-swatch-wrapper {
        padding: 0;
    }

    .color-input-modern::-webkit-color-swatch {
        border: none;
        border-radius: 4px;
    }

    .hex-input-modern {
        flex: 1;
        font-family: 'Courier New', monospace;
        text-transform: uppercase;
    }

    .hex-input-modern::placeholder {
        text-transform: uppercase;
        color: #9CA3AF;
    }
</style>

<!-- JavaScript -->
<script>
// Pass transcription data to JavaScript
<?php if (isset($transcriptionData) && $transcriptionData): ?>
    window.transcriptionData = <?= json_encode($transcriptionData) ?>;
<?php endif; ?>
</script>
<script src="/assets/js/ffmpeg-config.js"></script>
