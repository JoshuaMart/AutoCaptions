<?php
/**
 * AutoCaptions Web Interface - Main Entry Point
 * Upload page for video files
 */

session_start();
require_once "config/services.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoCaptions - Video Caption Generator</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom styling -->
    <style>
        /* Loading animation */
        .animate-pulse-slow {
            animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        /* File drop zone */
        .file-drop-zone {
            transition: all 0.3s ease;
        }

        .file-drop-zone.drag-over {
            @apply border-blue-500 bg-blue-50;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <?php include "components/header.php"; ?>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                                onclick="clearFile()"
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
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                         style="width: 0%"
                         id="progress-bar"></div>
                </div>
                <p class="text-xs text-gray-400 mt-2" id="progress-text">0%</p>
            </div>

            <!-- Action Buttons -->
            <div id="action-buttons" class="hidden mt-6 flex justify-center space-x-4">
                <button type="button"
                        onclick="startTranscription()"
                        id="transcribe-btn"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                    Generate Transcription
                </button>

                <button type="button"
                        onclick="clearFile()"
                        class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Upload Different Video
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
    </main>

    <!-- Settings Modal -->
    <?php include "components/settings-modal.php"; ?>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/api.js"></script>
    <script>
        // File upload functionality
        let selectedFile = null;

        // Initialize file upload
        document.addEventListener('DOMContentLoaded', function() {
            initializeFileUpload();
        });

        function initializeFileUpload() {
            const dropZone = document.getElementById('file-drop-zone');
            const fileInput = document.getElementById('video-input');

            // Click to upload
            dropZone.addEventListener('click', () => fileInput.click());

            // Drag and drop events
            dropZone.addEventListener('dragover', handleDragOver);
            dropZone.addEventListener('dragleave', handleDragLeave);
            dropZone.addEventListener('drop', handleDrop);

            // File input change
            fileInput.addEventListener('change', handleFileSelect);
        }

        function handleDragOver(event) {
            event.preventDefault();
            event.currentTarget.classList.add('drag-over');
        }

        function handleDragLeave(event) {
            event.preventDefault();
            event.currentTarget.classList.remove('drag-over');
        }

        function handleDrop(event) {
            event.preventDefault();
            event.currentTarget.classList.remove('drag-over');

            const files = event.dataTransfer.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        }

        function handleFileSelect(event) {
            const files = event.target.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        }

        function handleFile(file) {
            try {
                // Validate file
                Utils.validateVideoFile(file);

                selectedFile = file;
                showFileInfo(file);
                showActionButtons();

            } catch (error) {
                showNotification('error', 'Invalid File', error.message);
                clearFile();
            }
        }

        function showFileInfo(file) {
            const fileInfo = document.getElementById('file-info');
            const fileName = document.getElementById('file-name');
            const fileSize = document.getElementById('file-size');
            const fileDuration = document.getElementById('file-duration');

            fileName.textContent = file.name;
            fileSize.textContent = Utils.formatFileSize(file.size);
            fileDuration.textContent = 'Analyzing...';

            fileInfo.classList.remove('hidden');

            // Get video duration
            getVideoDuration(file).then(duration => {
                fileDuration.textContent = Utils.formatDuration(duration);
            }).catch(() => {
                fileDuration.textContent = 'Unknown duration';
            });
        }

        function showActionButtons() {
            document.getElementById('action-buttons').classList.remove('hidden');
        }

        function clearFile() {
            selectedFile = null;
            document.getElementById('video-input').value = '';
            document.getElementById('file-info').classList.add('hidden');
            document.getElementById('action-buttons').classList.add('hidden');
            document.getElementById('processing-section').classList.add('hidden');
            document.getElementById('upload-section').classList.remove('hidden');
        }

        function getVideoDuration(file) {
            return new Promise((resolve, reject) => {
                const video = document.createElement('video');
                video.preload = 'metadata';

                video.onloadedmetadata = function() {
                    window.URL.revokeObjectURL(video.src);
                    resolve(video.duration);
                };

                video.onerror = function() {
                    reject(new Error('Could not load video'));
                };

                video.src = URL.createObjectURL(file);
            });
        }

        async function startTranscription() {
            if (!selectedFile) {
                showNotification('error', 'No File', 'Please select a video file first');
                return;
            }

            // Show processing UI
            document.getElementById('upload-section').classList.add('hidden');
            document.getElementById('action-buttons').classList.add('hidden');
            document.getElementById('processing-section').classList.remove('hidden');

            try {
                // Prepare form data
                const formData = {
                    service: 'whisper-cpp' // Default service
                };

                const files = {
                    file: selectedFile
                };

                // Start transcription
                const result = await API.call('transcriptions', 'api/transcribe', 'POST', formData, files);

                if (result.success) {
                    // Store transcription data in session/localStorage
                    sessionStorage.setItem('transcriptionData', JSON.stringify(result));
                    sessionStorage.setItem('videoFileName', selectedFile.name);

                    showNotification('success', 'Transcription Complete', 'Redirecting to edit page...');

                    // Redirect to transcription edit page
                    setTimeout(() => {
                        window.location.href = 'pages/transcription.php';
                    }, 1500);
                } else {
                    throw new Error(result.error || 'Transcription failed');
                }

            } catch (error) {
                console.error('Transcription failed:', error);
                showNotification('error', 'Transcription Failed', error.message);

                // Reset UI
                clearFile();
                document.getElementById('upload-section').classList.remove('hidden');
            }
        }

        // Progress simulation (you can replace this with real progress tracking)
        function simulateProgress() {
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            let progress = 0;

            const interval = setInterval(() => {
                progress += Math.random() * 15;
                if (progress > 95) progress = 95;

                progressBar.style.width = progress + '%';
                progressText.textContent = Math.round(progress) + '%';

                if (progress >= 95) {
                    clearInterval(interval);
                }
            }, 500);
        }

        // Start progress simulation when processing begins
        document.getElementById('processing-section').addEventListener('DOMSubtreeModified', function() {
            if (!this.classList.contains('hidden')) {
                simulateProgress();
            }
        });
    </script>
</body>
</html>
