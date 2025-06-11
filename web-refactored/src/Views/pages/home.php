<?php
// Views/pages/home.php

// This view will be rendered within the main.php layout.
// $pageTitle and $pageDescription can be set here to override layout defaults.
$pageTitle = 'Home - AutoCaptions';
$pageDescription = 'Upload your video to automatically generate captions.';

?>

<div class="section" id="service-status-section">
    <h2 class="section-title">Service Status</h2>
    <div id="serviceStatusContainer">
        <!-- Service statuses will be loaded here by service-status.js -->
        <p class="status-loading">Loading service statuses...</p>
    </div>
</div>

<div class="section" id="upload-section">
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

        <button type="submit" class="button">Upload Video</button>
    </form>
</div>

<div class="section hidden" id="transcription-processing-section">
    <h2 class="section-title">Transcription</h2>
    <div id="transcriptionProcessing">
        <p id="transcriptionReadyMessage" class="hidden">File uploaded. Ready to start transcription.</p>
        <button id="startTranscriptionButton" class="button hidden">Start Transcription</button>

        <div id="transcriptionStatus" class="status-message status-info hidden">
            <!-- Transcription status messages will be displayed here -->
        </div>

        <div id="transcriptionProgressContainer" class="hidden">
            <label for="transcriptionProgress">Transcription Progress:</label>
            <!-- Note: Progress for transcription is often not a simple percentage from the service -->
            <!-- This might just be a visual indicator or updated manually based on steps -->
             <progress id="transcriptionProgress" value="0" max="100"></progress>
             <!-- Or just a spinning indicator -->
             <p id="transcriptionProgressMessage"></p>
        </div>

    </div>
</div>

<div class="section hidden" id="caption-editing-and-rendering-section">
     <h2 class="section-title">Edit Captions & Generate Video</h2>
     <div id="captionEditingAndRendering">
        <p>Edit your captions below and choose a rendering method.</p>
        <!-- Placeholder for transcription editor UI -->
        <div id="transcriptionEditorContainer">
            <!-- Transcription editor will be loaded here by a future JS module -->
        </div>
        <div id="captionStylingContainer">
            <!-- Caption styling options for FFmpeg or Remotion -->
        </div>
        <button id="generateFfmpegVideoButton" class="button hidden">Generate with FFmpeg</button>
        <button id="generateRemotionVideoButton" class="button hidden">Generate with Remotion</button>
     </div>
</div>


<div class="section" id="configuration-section">
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