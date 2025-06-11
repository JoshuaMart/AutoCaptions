<?php
// Views/pages/home.php
$pageTitle = 'AutoCaptions - Video Caption Generator';
$pageDescription = 'Generate automatic captions for your 9:16 videos with AI-powered transcription';
?>

<!-- Page Title -->
<div class="text-center mb-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">
        Upload Your Video
    </h1>
    <p class="text-lg text-gray-600">
        Generate automatic captions for your 9:16 videos with AI-powered transcription
    </p>
</div>

<!-- Upload Card -->
<div class="bg-white rounded-lg shadow-lg p-8 mb-8">
    <!-- File Upload Area -->
    <div id="upload-section">
        <div class="file-drop-zone border-2 border-dashed border-gray-300 rounded-lg p-12 text-center cursor-pointer hover:border-gray-400 transition-colors duration-200"
             id="file-drop-zone">

            <!-- Upload Icon -->
            <div class="mb-4">
                <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"/>
                </svg>
            </div>

            <!-- Upload Text -->
            <div class="mb-4">
                <p class="text-xl font-medium text-gray-900 mb-2">
                    Drop your video here or click to browse
                </p>
                <p class="text-sm text-gray-500">
                    Supports MP4, MOV, AVI, MKV, WebM • Max 500MB • 9:16 format recommended
                </p>
            </div>

            <!-- File Input -->
            <input type="file"
                   id="video-input"
                   accept="video/mp4,video/mov,video/avi,video/mkv,video/webm"
                   class="hidden">

            <!-- Upload Button -->
            <button type="button"
                    onclick="document.getElementById('video-input').click()"
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                Choose Video File
            </button>
        </div>

        <!-- File Information (Hidden by default) -->
        <div id="file-info" class="hidden mt-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900" id="file-name"></p>
                        <p class="text-sm text-gray-500">
                            <span id="file-size"></span> •
                            <span id="file-duration"></span>
                        </p>
                    </div>
                </div>
                <button type="button"
                        onclick="app.fileUpload.clearFile()"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Processing Section (Hidden by default) -->
    <div id="processing-section" class="hidden text-center">
        <div class="mb-4">
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
    </div>

    <!-- Action Buttons -->
    <div id="action-buttons" class="hidden mt-6 flex justify-center space-x-4">
        <button type="button"
                onclick="app.transcriptionUI.startTranscription()"
                id="transcribe-btn"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
            </svg>
            Generate Transcription
        </button>

        <button type="button"
                onclick="app.fileUpload.clearFile()"
                class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Upload Different Video
        </button>
    </div>
</div>

<!-- Transcription Section (Initially hidden) -->
<div id="transcription-section" class="hidden bg-white rounded-lg shadow-lg p-8 mb-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Edit Transcription</h2>
    
    <!-- Transcription Editor Container -->
    <div id="transcription-editor-container">
        <!-- Will be populated by transcription-editor-ui.js -->
    </div>
    
    <!-- Transcription Actions -->
    <div class="mt-6 flex justify-between">
        <button type="button"
                onclick="app.transcriptionEditorUI.saveTranscription()"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
            </svg>
            Save Changes
        </button>
        
        <button type="button"
                onclick="app.captionRenderingUI.show()"
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            Generate Video
        </button>
    </div>
</div>

<!-- Caption Rendering Section (Initially hidden) -->
<div id="caption-rendering-section" class="hidden bg-white rounded-lg shadow-lg p-8 mb-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Generate Video with Captions</h2>
    
    <!-- Service Selection -->
    <div class="mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Choose Rendering Service</h3>
        <div class="grid md:grid-cols-2 gap-6">
            <!-- FFmpeg Option -->
            <div class="border-2 border-gray-200 rounded-lg p-6 cursor-pointer hover:border-blue-500 transition-colors" 
                 onclick="app.captionRenderingUI.selectService('ffmpeg')">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-900">FFmpeg</h4>
                    <input type="radio" name="rendering-service" value="ffmpeg" class="text-blue-600">
                </div>
                <p class="text-sm text-gray-600 mb-2">Fast rendering with ASS styling</p>
                <ul class="text-xs text-gray-500 list-disc list-inside">
                    <li>Quick processing</li>
                    <li>Classic subtitle styles</li>
                    <li>Efficient for batch processing</li>
                </ul>
            </div>
            
            <!-- Remotion Option -->
            <div class="border-2 border-gray-200 rounded-lg p-6 cursor-pointer hover:border-blue-500 transition-colors" 
                 onclick="app.captionRenderingUI.selectService('remotion')">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-900">Remotion</h4>
                    <input type="radio" name="rendering-service" value="remotion" class="text-blue-600">
                </div>
                <p class="text-sm text-gray-600 mb-2">Advanced effects and customization</p>
                <ul class="text-xs text-gray-500 list-disc list-inside">
                    <li>Rich animations</li>
                    <li>Custom styling</li>
                    <li>Premium quality</li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Styling Options Container -->
    <div id="caption-styling-container" class="hidden">
        <!-- Will be populated based on selected service -->
    </div>
    
    <!-- Render Actions -->
    <div class="mt-6 flex justify-center space-x-4">
        <button type="button"
                onclick="app.captionRenderingUI.generateVideo()"
                id="generate-video-btn"
                disabled
                class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:bg-gray-400 disabled:cursor-not-allowed">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m2-10a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Generate Video
        </button>
    </div>
</div>

<!-- Features Section -->
<div class="grid md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 text-center">
        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">AI Transcription</h3>
        <p class="text-sm text-gray-600">
            Powered by OpenAI Whisper for accurate speech-to-text conversion
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 text-center">
        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Multiple Styles</h3>
        <p class="text-sm text-gray-600">
            Choose between FFmpeg for speed or Remotion for advanced customization
        </p>
    </div>

    <div class="bg-white rounded-lg shadow p-6 text-center">
        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Mobile Optimized</h3>
        <p class="text-sm text-gray-600">
            Perfect for 9:16 format videos, ideal for TikTok, Instagram, and YouTube Shorts
        </p>
    </div>
</div>

<!-- Templates for JavaScript -->
<template id="transcription-segment-template">
    <div class="bg-gray-50 rounded-lg p-4 mb-4" data-segment-index="">
        <div class="flex justify-between items-start mb-3">
            <div class="flex space-x-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Start</label>
                    <input type="text" class="segment-start-time w-20 text-xs px-2 py-1 border border-gray-300 rounded" value="00:00.000">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">End</label>
                    <input type="text" class="segment-end-time w-20 text-xs px-2 py-1 border border-gray-300 rounded" value="00:00.000">
                </div>
            </div>
            <div class="flex space-x-2">
                <button class="button-split-segment text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">Split</button>
                <button class="button-merge-segment-prev text-xs px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600">Merge</button>
                <button class="button-delete-segment text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
            </div>
        </div>
        <div class="segment-text-editor border border-gray-300 rounded p-3 min-h-16 focus-within:ring-2 focus-within:ring-blue-500" contenteditable="true">
            <!-- Words will be populated here -->
        </div>
    </div>
</template>

<template id="transcription-word-template">
    <span class="word inline-block px-1 py-0.5 mr-1 mb-1 rounded hover:bg-blue-100 cursor-text" contenteditable="false" data-start-ms="" data-end-ms="">
        <!-- text content -->
    </span>
</template>