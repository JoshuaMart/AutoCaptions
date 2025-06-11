<?php
// Views/pages/home.php

// This view will be rendered within the main.php layout.
// $pageTitle and $pageDescription can be set here to override layout defaults.
$pageTitle = 'Home - AutoCaptions';
$pageDescription = 'Upload your video to automatically generate captions.';

?>

<div class="section">
    <h2 class="section-title">Service Status</h2>
    <div id="serviceStatusContainer">
        <!-- Service statuses will be loaded here by service-status.js -->
        <p class="status-loading">Loading service statuses...</p>
    </div>
</div>

<div class="section">
    <h2 class="section-title">Upload Video</h2>
    <form id="uploadForm" enctype="multipart/form-data">
        <div>
            <label for="videoFile">Choose a video file (MP4, MOV, WebM, etc.):</label>
            <input type="file" id="videoFile" name="videoFile" accept="video/*" required>
        </div>

        <div id="uploadProgressContainer" class="hidden">
            <label for="uploadProgress">Upload Progress:</label>
            <progress id="uploadProgress" value="0" max="100"></progress>
            <!-- Alternative div-based progress bar (if progress element is not styled well across browsers)
            <div id="uploadProgressBar" class="progress-bar-custom">
                <div class="progress-bar-custom-inner">0%</div>
            </div>
            -->
        </div>

        <div id="uploadStatus" class="status-message status-info">
            Please select a video file to upload.
        </div>

        <button type="submit" class="button">Upload and Generate Transcription</button>
    </form>
</div>

<div class="section">
    <h2 class="section-title">Next Steps</h2>
    <div id="transcriptionActions" class="hidden">
        <p>Once your file is uploaded and transcribed, options to edit and generate the final video will appear here.</p>
        <!-- Placeholder for transcription editor UI or links to next steps -->
        <div id="transcriptionEditorContainer">
            <!-- Transcription editor will be loaded here by a future JS module -->
        </div>
        <div id="captionStylingContainer">
            <!-- Caption styling options for FFmpeg or Remotion -->
        </div>
        <button id="generateFfmpegVideoButton" class="button hidden">Generate with FFmpeg</button>
        <button id="generateRemotionVideoButton" class="button hidden">Generate with Remotion</button>
    </div>
     <p id="initialNextStepsMessage">Upload a video to begin the captioning process.</p>
</div>

<div class="section">
    <h2 class="section-title">Configuration</h2>
    <p>Manage service URLs and settings (to be implemented with a UI).</p>
    <div id="serviceConfigContainer">
        <!-- Service configuration UI will be loaded here by a future JS module -->
    </div>
    <button id="showConfigModalButton" class="button hidden">Configure Services</button>
</div>

<template id="serviceConfigRowTemplate">
    <div class="service-config-row">
        <strong class="service-name-config"></strong> (<span class="service-config-source"></span>)
        <input type="url" class="service-url-input" placeholder="http://localhost:port">
        <span class="service-health-indicator"></span>
    </div>
</template>

<template id="transcriptionWordTemplate">
    <span class="word" contenteditable="false" data-start-ms="" data-end-ms="">
        <!-- text content -->
    </span>
</template>

<template id="transcriptionSegmentTemplate">
    <div class="segment" data-segment-index="">
        <div class="segment-timing">
            <input type="text" class="segment-start-time" value="00:00.000">
            <span>-</span>
            <input type="text" class="segment-end-time" value="00:00.000">
        </div>
        <div class="segment-text-editor" contenteditable="true">
            <!-- Words will be populated here -->
        </div>
        <div class="segment-actions">
            <button class="button-split-segment">Split</button>
            <button class="button-merge-segment-prev">Merge Prev</button>
            <button class="button-delete-segment">Delete</button>
        </div>
    </div>
</template>