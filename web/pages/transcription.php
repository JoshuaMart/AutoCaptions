<?php
/**
 * Transcription Edit Page
 * Edit transcription text and timestamps before processing
 */

session_start();
require_once "../config/services.php";

// Check if we have transcription data - allow demo access for testing
if (!isset($_SESSION["transcription_data"]) && !isset($_GET["demo"])) {
    // Allow access for testing purposes
    $_GET["demo"] = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Transcription - AutoCaptions</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom styling -->
    <style>
        .caption-item {
            transition: all 0.2s ease;
        }

        .caption-item:hover {
            @apply bg-gray-50;
        }

        .caption-item.editing {
            @apply bg-blue-50 ring-2 ring-blue-500;
        }

        .timestamp-input {
            width: 80px;
        }

        .word-count {
            font-size: 0.75rem;
            opacity: 0.7;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <?php include "../components/header.php"; ?>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        Edit Transcription
                    </h1>
                    <p class="text-lg text-gray-600">
                        Review and edit your transcription before generating captions
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="flex space-x-3">
                    <button type="button"
                            onclick="saveAndContinue()"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Continue to Service Selection
                    </button>

                    <button type="button"
                            onclick="window.history.back()"
                            class="inline-flex items-center px-4 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back
                    </button>
                </div>
            </div>
        </div>

        <!-- Video Info Card -->
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
                </div>
            </div>
        </div>

        <!-- Transcription Editor -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Editor Header -->
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Caption Editor
                        </h3>

                        <!-- Search -->
                        <div class="relative">
                            <input type="text"
                                   id="search-input"
                                   placeholder="Search text..."
                                   class="block w-48 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Editor Controls -->
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-500">
                            <span id="visible-captions">0</span> of <span id="total-captions">0</span> captions
                        </span>

                        <button type="button"
                                onclick="addNewCaption()"
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Caption
                        </button>
                    </div>
                </div>
            </div>

            <!-- Caption List -->
            <div class="max-h-96 overflow-y-auto" id="captions-container">
                <div id="captions-list" class="divide-y divide-gray-200">
                    <!-- Captions will be loaded here dynamically -->
                </div>

                <!-- Loading State -->
                <div id="loading-captions" class="p-8 text-center">
                    <div class="animate-spin mx-auto h-8 w-8 text-blue-600 mb-4">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <p class="text-gray-500">Loading transcription...</p>
                </div>

                <!-- Empty State -->
                <div id="empty-captions" class="hidden p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No captions found</h3>
                    <p class="text-gray-500">Upload a video to generate transcription.</p>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Total Duration</p>
                        <p class="text-lg font-semibold text-gray-900" id="stats-duration">--:--</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Word Count</p>
                        <p class="text-lg font-semibold text-gray-900" id="stats-words">0</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Avg. Caption Length</p>
                        <p class="text-lg font-semibold text-gray-900" id="stats-avg-length">0s</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500">Confidence</p>
                        <p class="text-lg font-semibold text-gray-900" id="stats-confidence">--</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Settings Modal -->
    <?php include "../components/settings-modal.php"; ?>

    <!-- JavaScript -->
    <script src="../assets/js/main.js"></script>
    <script>
        // Global variables
        let transcriptionData = null;
        let editedCaptions = [];
        let originalCaptions = [];

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            loadTranscriptionData();
            initializeEditor();
        });

        function loadTranscriptionData() {
            // Try to get data from sessionStorage first
            const sessionData = sessionStorage.getItem('transcriptionData');
            const videoFileName = sessionStorage.getItem('videoFileName') || 'Unknown Video';

            if (sessionData) {
                try {
                    transcriptionData = JSON.parse(sessionData);
                    displayVideoInfo(videoFileName);
                    loadCaptions();
                } catch (error) {
                    console.error('Failed to parse transcription data:', error);
                    showDemoData();
                }
            } else {
                // Show demo data for testing
                showDemoData();
            }
        }

        function showDemoData() {
            // Demo data for testing
            transcriptionData = {
                success: true,
                transcription: {
                    captions: [
                        { text: "Hello", startMs: 0, endMs: 500, confidence: 0.95 },
                        { text: "world", startMs: 500, endMs: 1000, confidence: 0.98 },
                        { text: "this", startMs: 1000, endMs: 1300, confidence: 0.92 },
                        { text: "is", startMs: 1300, endMs: 1500, confidence: 0.96 },
                        { text: "a", startMs: 1500, endMs: 1600, confidence: 0.99 },
                        { text: "test", startMs: 1600, endMs: 2000, confidence: 0.94 },
                        { text: "transcription", startMs: 2000, endMs: 2800, confidence: 0.91 }
                    ],
                    duration: 3.0,
                    language: "en",
                    metadata: {
                        service: "whisper-cpp",
                        model: "medium",
                        timestamp: new Date().toISOString()
                    }
                },
                processingTime: 5000
            };

            displayVideoInfo('demo-video.mp4');
            loadCaptions();
        }

        function displayVideoInfo(filename) {
            if (!transcriptionData) return;

            const { transcription, processingTime } = transcriptionData;

            document.getElementById('video-filename').textContent = filename;
            document.getElementById('video-duration').textContent = `Duration: ${Utils.formatDuration(transcription.duration)}`;
            document.getElementById('caption-count').textContent = `Captions: ${transcription.captions.length}`;
            document.getElementById('transcription-language').textContent = `Language: ${transcription.language.toUpperCase()}`;
            document.getElementById('processing-time').textContent = `Processing: ${processingTime}ms`;
        }

        function loadCaptions() {
            if (!transcriptionData) return;

            originalCaptions = [...transcriptionData.transcription.captions];
            editedCaptions = [...transcriptionData.transcription.captions];

            renderCaptions();
            updateStats();

            // Hide loading, show content
            document.getElementById('loading-captions').classList.add('hidden');
        }

        function renderCaptions() {
            const container = document.getElementById('captions-list');
            const searchTerm = document.getElementById('search-input').value.toLowerCase();

            let visibleCount = 0;

            container.innerHTML = editedCaptions.map((caption, index) => {
                const isVisible = !searchTerm || caption.text.toLowerCase().includes(searchTerm);
                if (isVisible) visibleCount++;

                return `
                    <div class="caption-item ${isVisible ? '' : 'hidden'} p-4" data-index="${index}">
                        <div class="flex items-start space-x-4">
                            <!-- Caption Number -->
                            <div class="flex-shrink-0 w-12 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 text-xs font-medium text-gray-500 bg-gray-100 rounded-full">
                                    ${index + 1}
                                </span>
                            </div>

                            <!-- Timestamps -->
                            <div class="flex-shrink-0 space-y-2">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Start (ms)</label>
                                    <input type="number"
                                           class="timestamp-input px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                           value="${caption.startMs}"
                                           onchange="updateCaption(${index}, 'startMs', parseInt(this.value))"
                                           min="0">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">End (ms)</label>
                                    <input type="number"
                                           class="timestamp-input px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                           value="${caption.endMs}"
                                           onchange="updateCaption(${index}, 'endMs', parseInt(this.value))"
                                           min="${caption.startMs}">
                                </div>
                            </div>

                            <!-- Caption Text -->
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-1">
                                    Caption Text
                                    ${caption.confidence ? `<span class="text-yellow-600">(${Math.round(caption.confidence * 100)}% confidence)</span>` : ''}
                                </label>
                                <textarea class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                          rows="2"
                                          onchange="updateCaption(${index}, 'text', this.value)"
                                          placeholder="Enter caption text...">${caption.text}</textarea>
                                <div class="mt-1 flex items-center justify-between">
                                    <span class="word-count text-gray-400">
                                        ${caption.text.split(' ').length} words • ${Utils.formatDuration((caption.endMs - caption.startMs) / 1000)} duration
                                    </span>
                                    <div class="flex space-x-1">
                                        <button type="button"
                                                onclick="splitCaption(${index})"
                                                title="Split caption"
                                                class="p-1 text-gray-400 hover:text-blue-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                            </svg>
                                        </button>
                                        <button type="button"
                                                onclick="deleteCaption(${index})"
                                                title="Delete caption"
                                                class="p-1 text-gray-400 hover:text-red-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');

            // Update counters
            document.getElementById('visible-captions').textContent = visibleCount;
            document.getElementById('total-captions').textContent = editedCaptions.length;
        }

        function updateCaption(index, field, value) {
            if (editedCaptions[index]) {
                editedCaptions[index][field] = value;

                // Re-render if needed to update word count
                if (field === 'text') {
                    renderCaptions();
                }

                updateStats();
            }
        }

        function updateStats() {
            if (!editedCaptions.length) return;

            const totalWords = editedCaptions.reduce((sum, caption) => {
                return sum + caption.text.split(' ').length;
            }, 0);

            const totalDuration = editedCaptions.reduce((sum, caption) => {
                return sum + (caption.endMs - caption.startMs);
            }, 0) / 1000;

            const avgLength = totalDuration / editedCaptions.length;

            const avgConfidence = editedCaptions.reduce((sum, caption) => {
                return sum + (caption.confidence || 0);
            }, 0) / editedCaptions.length;

            document.getElementById('stats-duration').textContent = Utils.formatDuration(totalDuration);
            document.getElementById('stats-words').textContent = totalWords;
            document.getElementById('stats-avg-length').textContent = avgLength.toFixed(1) + 's';
            document.getElementById('stats-confidence').textContent = Math.round(avgConfidence * 100) + '%';
        }

        function initializeEditor() {
            // Search functionality
            document.getElementById('search-input').addEventListener('input', function() {
                renderCaptions();
            });
        }

        function splitCaption(index) {
            const caption = editedCaptions[index];
            const words = caption.text.split(' ');

            if (words.length < 2) {
                showNotification('warning', 'Cannot Split', 'Caption must have at least 2 words to split');
                return;
            }

            const midPoint = Math.floor(words.length / 2);
            const duration = caption.endMs - caption.startMs;
            const splitTime = caption.startMs + Math.floor(duration / 2);

            const firstPart = {
                text: words.slice(0, midPoint).join(' '),
                startMs: caption.startMs,
                endMs: splitTime,
                confidence: caption.confidence
            };

            const secondPart = {
                text: words.slice(midPoint).join(' '),
                startMs: splitTime,
                endMs: caption.endMs,
                confidence: caption.confidence
            };

            editedCaptions.splice(index, 1, firstPart, secondPart);
            renderCaptions();
            updateStats();

            showNotification('success', 'Caption Split', 'Caption has been split into two parts');
        }

        function deleteCaption(index) {
            if (editedCaptions.length <= 1) {
                showNotification('warning', 'Cannot Delete', 'Cannot delete the last remaining caption');
                return;
            }

            if (confirm('Are you sure you want to delete this caption?')) {
                editedCaptions.splice(index, 1);
                renderCaptions();
                updateStats();
                showNotification('success', 'Caption Deleted', 'Caption has been removed');
            }
        }

        function addNewCaption() {
            const lastCaption = editedCaptions[editedCaptions.length - 1];
            const newStartTime = lastCaption ? lastCaption.endMs : 0;

            const newCaption = {
                text: 'New caption text',
                startMs: newStartTime,
                endMs: newStartTime + 1000,
                confidence: 1.0
            };

            editedCaptions.push(newCaption);
            renderCaptions();
            updateStats();

            showNotification('success', 'Caption Added', 'New caption has been added at the end');
        }

        function autoSplitCaptions() {
            let splitCount = 0;

            // Split captions longer than 5 seconds or more than 10 words
            for (let i = editedCaptions.length - 1; i >= 0; i--) {
                const caption = editedCaptions[i];
                const duration = (caption.endMs - caption.startMs) / 1000;
                const wordCount = caption.text.split(' ').length;

                if (duration > 5 || wordCount > 10) {
                    splitCaption(i);
                    splitCount++;
                }
            }

            if (splitCount > 0) {
                showNotification('success', 'Auto Split Complete', `Split ${splitCount} long captions`);
            } else {
                showNotification('info', 'No Changes Needed', 'All captions are already optimal length');
            }
        }

        function validateTimestamps() {
           let issues = 0;

           for (let i = 0; i < editedCaptions.length; i++) {
               const caption = editedCaptions[i];

               // Check if end time is after start time
               if (caption.endMs <= caption.startMs) {
                   caption.endMs = caption.startMs + 500; // Fix with 500ms minimum
                   issues++;
               }

               // Check overlap with next caption
               if (i < editedCaptions.length - 1) {
                   const nextCaption = editedCaptions[i + 1];
                   if (caption.endMs > nextCaption.startMs) {
                       caption.endMs = nextCaption.startMs;
                       issues++;
                   }
               }

               // Check minimum duration (200ms)
               if (caption.endMs - caption.startMs < 200) {
                   caption.endMs = caption.startMs + 200;
                   issues++;
               }
           }

           if (issues > 0) {
               renderCaptions();
               updateStats();
               showNotification('success', 'Timestamps Fixed', `Fixed ${issues} timestamp issues`);
           } else {
               showNotification('info', 'All Good', 'All timestamps are valid');
           }
       }

       function exportTranscription() {
           const exportData = {
               success: true,
               transcription: {
                   captions: editedCaptions,
                   duration: transcriptionData.transcription.duration,
                   language: transcriptionData.transcription.language,
                   metadata: {
                       ...transcriptionData.transcription.metadata,
                       edited: true,
                       editedAt: new Date().toISOString()
                   }
               },
               processingTime: transcriptionData.processingTime
           };

           const blob = new Blob([JSON.stringify(exportData, null, 2)], {
               type: 'application/json'
           });

           const url = URL.createObjectURL(blob);
           const a = document.createElement('a');
           a.href = url;
           a.download = 'transcription-edited.json';
           document.body.appendChild(a);
           a.click();
           document.body.removeChild(a);
           URL.revokeObjectURL(url);

           showNotification('success', 'Export Complete', 'Transcription exported successfully');
       }

       function saveAndContinue() {
           // Validate before continuing
           validateTimestamps();

           // Update the transcription data with edited captions
           const updatedData = {
               ...transcriptionData,
               transcription: {
                   ...transcriptionData.transcription,
                   captions: editedCaptions
               }
           };

           // Save to session storage
           sessionStorage.setItem('transcriptionData', JSON.stringify(updatedData));

           showNotification('success', 'Transcription Saved', 'Redirecting to service selection...');

           // Redirect to service choice page
           setTimeout(() => {
               window.location.href = 'service-choice.php';
           }, 1500);
       }

       // Keyboard shortcuts
       document.addEventListener('keydown', function(event) {
           // Ctrl/Cmd + S to save and continue
           if ((event.ctrlKey || event.metaKey) && event.key === 's') {
               event.preventDefault();
               saveAndContinue();
           }

           // Ctrl/Cmd + E to export
           if ((event.ctrlKey || event.metaKey) && event.key === 'e') {
               event.preventDefault();
               exportTranscription();
           }
       });
   </script>
</body>
</html>
