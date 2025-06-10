<?php
/**
 * Result Page
 * Shows video generation progress and download link
 */

session_start();
require_once "../config/services.php";

// Check if we have the necessary data
$finalConfig = null;
$selectedService = $_SESSION["selected_service"] ?? "ffmpeg";

// Try to get config from session storage via JavaScript
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Generation - AutoCaptions</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        .progress-circle {
            stroke-dasharray: 283;
            stroke-dashoffset: 283;
            transition: stroke-dashoffset 2s ease-in-out;
        }
        
        .progress-circle.animate {
            stroke-dashoffset: 0;
            animation: progress 3s ease-in-out infinite;
        }

        @keyframes progress {
            0% { stroke-dashoffset: 283; }
            50% { stroke-dashoffset: 141; }
            100% { stroke-dashoffset: 283; }
        }

        .bounce-in {
            animation: bounceIn 0.6s ease-out;
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <?php include "../components/header.php"; ?>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Page Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Video Generation
            </h1>
            <p class="text-lg text-gray-600">
                Your captioned video is being generated
            </p>
        </div>

        <!-- Progress Steps -->
        <div class="flex items-center justify-center space-x-4 mb-12">
            <div class="flex items-center text-sm">
                <div class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center mr-2">✓</div>
                <span class="text-green-600">Upload</span>
            </div>
            <div class="w-8 h-px bg-green-600"></div>
            <div class="flex items-center text-sm">
                <div class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center mr-2">✓</div>
                <span class="text-green-600">Transcription</span>
            </div>
            <div class="w-8 h-px bg-green-600"></div>
            <div class="flex items-center text-sm">
                <div class="w-6 h-6 bg-green-600 text-white rounded-full flex items-center justify-center mr-2">✓</div>
                <span class="text-green-600">Configuration</span>
            </div>
            <div class="w-8 h-px bg-blue-600"></div>
            <div class="flex items-center text-sm">
                <div id="generation-step" class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center mr-2">
                    <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <span id="generation-text" class="text-blue-600 font-medium">Generating</span>
            </div>
        </div>

        <!-- Status Card -->
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            
            <!-- Loading State -->
            <div id="loading-state">
                <!-- Progress Circle -->
                <div class="relative mx-auto w-32 h-32 mb-6">
                    <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            stroke="#e5e7eb"
                            stroke-width="8"
                            fill="none"
                        />
                        <circle
                            cx="50"
                            cy="50"
                            r="45"
                            stroke="#3b82f6"
                            stroke-width="8"
                            fill="none"
                            class="progress-circle animate"
                        />
                    </svg>
                    
                    <!-- Icon in center -->
                    <div class="absolute inset-0 flex items-center justify-center">
                        <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 002 2v8a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>

                <h2 class="text-xl font-semibold text-gray-900 mb-2">Generating Your Video</h2>
                <p id="status-message" class="text-gray-600 mb-4">Preparing video generation...</p>
                
                <!-- Service Info -->
                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 mb-4">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Using <span id="service-name" class="font-medium">FFmpeg</span>
                </div>

                <!-- Estimated Time -->
                <p class="text-sm text-gray-500">
                    ⏱️ Estimated time: <span id="estimated-time">2-5 minutes</span>
                </p>
            </div>

            <!-- Success State -->
            <div id="success-state" class="hidden">
                <!-- Success Icon -->
                <div class="mx-auto w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6 bounce-in">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>

                <h2 class="text-xl font-semibold text-gray-900 mb-2">Video Generated Successfully!</h2>
                <p class="text-gray-600 mb-6">Your captioned video is ready for download</p>

                <!-- Download Button -->
                <a id="download-button" href="#" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200 mb-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Download Video
                </a>

                <!-- Generation Info -->
                <div class="text-sm text-gray-500 space-y-1">
                    <p>🎬 Generated with <span id="success-service" class="font-medium">FFmpeg</span></p>
                    <p id="generation-time" class="hidden">⏱️ Generation time: <span class="font-medium">--</span></p>
                    <p id="video-size" class="hidden">📦 File size: <span class="font-medium">--</span></p>
                </div>
            </div>

            <!-- Error State -->
            <div id="error-state" class="hidden">
                <!-- Error Icon -->
                <div class="mx-auto w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </div>

                <h2 class="text-xl font-semibold text-gray-900 mb-2">Generation Failed</h2>
                <p id="error-message" class="text-gray-600 mb-6">Something went wrong during video generation</p>

                <!-- Retry Button -->
                <button onclick="retryGeneration()" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200 mr-4">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Retry
                </button>

                <!-- Back Button -->
                <button onclick="goBack()" class="inline-flex items-center px-6 py-3 border border-gray-300 text-gray-700 bg-white font-medium rounded-lg hover:bg-gray-50 transition-colors duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Configuration
                </button>
            </div>
        </div>

        <!-- Additional Actions -->
        <div class="mt-8 text-center">
            <div class="space-y-2">
                <button onclick="startNew()" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Create Another Video
                </button>
            </div>
        </div>
    </main>

    <!-- JavaScript -->
    <script src="../assets/js/main.js"></script>
    <script>
        let generationInProgress = false;
        let currentDownloadId = null;

        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎬 Result page loaded');
            initializeGeneration();
        });

        function initializeGeneration() {
            // Get data from session storage
            const finalConfig = sessionStorage.getItem('finalConfig');
            const selectedService = sessionStorage.getItem('selectedService') || 'ffmpeg';
            const uploadId = sessionStorage.getItem('uploadId');

            if (!finalConfig || !uploadId) {
                showError('Missing configuration data. Please start over.');
                return;
            }

            // Update service name in UI
            const serviceName = selectedService === 'remotion' ? 'Remotion' : 'FFmpeg';
            document.getElementById('service-name').textContent = serviceName;
            document.getElementById('estimated-time').textContent = 
                selectedService === 'remotion' ? '5-10 minutes' : '2-5 minutes';

            // Start generation
            startVideoGeneration(uploadId, selectedService, JSON.parse(finalConfig));
        }

        async function startVideoGeneration(uploadId, service, config) {
            if (generationInProgress) return;
            
            generationInProgress = true;
            
            try {
                updateStatus('Connecting to ' + service + ' service...');
                
                const response = await fetch('/api/generate-video.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        uploadId: uploadId,
                        service: service,
                        config: config
                    })
                });

                console.log('📡 Generation response status:', response.status);
                console.log('📡 Generation response content type:', response.headers.get('Content-Type'));

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Generation failed');
                }

                const result = await response.json();
                console.log('✅ Generation response:', result);

                if (result.success) {
                    updateStatus('Processing video with ' + service + '...');
                    
                    // Show success state
                    showSuccess(result);
                } else {
                    throw new Error(result.error || 'Generation failed');
                }

            } catch (error) {
                console.error('❌ Generation failed:', error);
                showError(error.message);
            } finally {
                generationInProgress = false;
            }
        }

        function updateStatus(message) {
            const statusElement = document.getElementById('status-message');
            if (statusElement) {
                statusElement.textContent = message;
            }
        }

        function showSuccess(result) {
            // Hide loading state
            document.getElementById('loading-state').classList.add('hidden');
            
            // Update step indicator
            const stepIcon = document.getElementById('generation-step');
            const stepText = document.getElementById('generation-text');
            
            stepIcon.innerHTML = '✓';
            stepIcon.classList.remove('bg-blue-600');
            stepIcon.classList.add('bg-green-600');
            stepText.textContent = 'Complete';
            stepText.classList.remove('text-blue-600');
            stepText.classList.add('text-green-600');

            // Set download link
            const downloadButton = document.getElementById('download-button');
            downloadButton.href = result.downloadUrl;
            currentDownloadId = result.downloadId;

            // Update service name
            const serviceName = result.service === 'remotion' ? 'Remotion' : 'FFmpeg';
            document.getElementById('success-service').textContent = serviceName;

            // Show additional info if available
            if (result.renderTime) {
                const timeElement = document.getElementById('generation-time');
                timeElement.querySelector('.font-medium').textContent = (result.renderTime / 1000).toFixed(1) + 's';
                timeElement.classList.remove('hidden');
            }

            if (result.videoSize) {
                const sizeElement = document.getElementById('video-size');
                sizeElement.querySelector('.font-medium').textContent = formatFileSize(result.videoSize);
                sizeElement.classList.remove('hidden');
            }

            // Show success state
            document.getElementById('success-state').classList.remove('hidden');
            
            console.log('✅ Generation completed successfully');
        }

        function showError(message) {
            // Hide loading state
            document.getElementById('loading-state').classList.add('hidden');
            
            // Update step indicator
            const stepIcon = document.getElementById('generation-step');
            const stepText = document.getElementById('generation-text');
            
            stepIcon.innerHTML = '✕';
            stepIcon.classList.remove('bg-blue-600');
            stepIcon.classList.add('bg-red-600');
            stepText.textContent = 'Failed';
            stepText.classList.remove('text-blue-600');
            stepText.classList.add('text-red-600');

            // Show error message
            document.getElementById('error-message').textContent = message;
            
            // Show error state
            document.getElementById('error-state').classList.remove('hidden');
            
            console.error('❌ Generation failed:', message);
        }

        function retryGeneration() {
            // Reset UI
            document.getElementById('error-state').classList.add('hidden');
            document.getElementById('loading-state').classList.remove('hidden');
            
            // Reset step indicator
            const stepIcon = document.getElementById('generation-step');
            const stepText = document.getElementById('generation-text');
            
            stepIcon.innerHTML = `
                <svg class="animate-spin w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            `;
            stepIcon.classList.remove('bg-red-600');
            stepIcon.classList.add('bg-blue-600');
            stepText.textContent = 'Generating';
            stepText.classList.remove('text-red-600');
            stepText.classList.add('text-blue-600');

            // Restart generation
            generationInProgress = false;
            initializeGeneration();
        }

        function goBack() {
            window.history.back();
        }

        function startNew() {
            // Clear session storage
            sessionStorage.clear();
            
            // Redirect to home
            window.location.href = '../index.php';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Prevent page refresh during generation
        window.addEventListener('beforeunload', function(e) {
            if (generationInProgress) {
                e.preventDefault();
                e.returnValue = 'Video generation is in progress. Are you sure you want to leave?';
                return e.returnValue;
            }
        });
    </script>
</body>
</html>
