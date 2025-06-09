<?php
/**
 * Service Choice Page
 * Choose between FFmpeg and Remotion caption services
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
    <title>Choose Caption Service - AutoCaptions</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom styling -->
    <style>
        .service-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .service-card.selected {
            @apply ring-2 ring-blue-500 bg-blue-50;
        }

        .tooltip {
            visibility: hidden;
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 50;
        }

        .tooltip.show {
            visibility: visible;
            opacity: 1;
        }

        .feature-check {
            @apply inline-flex items-center text-sm text-green-600;
        }

        .feature-cross {
            @apply inline-flex items-center text-sm text-gray-400;
        }

        .service-card.selected {
            border: 3px solid #2563eb; /* Couleur Tailwind blue-600 */
            box-shadow: 0 0 0 4px #bfdbfe40; /* Légère lueur bleue */
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <?php include "../components/header.php"; ?>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Choose Your Caption Service
            </h1>
            <p class="text-lg text-gray-600">
                Select the rendering engine that best fits your needs
            </p>
        </div>

        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-center space-x-8">
                <!-- Step 1: Upload -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">
                        ✓
                    </div>
                    <span class="ml-2 text-sm font-medium text-green-600">Upload</span>
                </div>

                <!-- Connector -->
                <div class="flex-1 h-px bg-green-600"></div>

                <!-- Step 2: Transcription -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">
                        ✓
                    </div>
                    <span class="ml-2 text-sm font-medium text-green-600">Transcription</span>
                </div>

                <!-- Connector -->
                <div class="flex-1 h-px bg-green-600"></div>

                <!-- Step 3: Service Choice (Current) -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium">
                        3
                    </div>
                    <span class="ml-2 text-sm font-medium text-blue-600">Service Choice</span>
                </div>

                <!-- Connector -->
                <div class="flex-1 h-px bg-gray-300"></div>

                <!-- Step 4: Configuration -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">
                        4
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-500">Configuration</span>
                </div>

                <!-- Connector -->
                <div class="flex-1 h-px bg-gray-300"></div>

                <!-- Step 5: Generate -->
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">
                        5
                    </div>
                    <span class="ml-2 text-sm font-medium text-gray-500">Generate</span>
                </div>
            </div>
        </div>

        <!-- Service Cards -->
        <div class="grid md:grid-cols-2 gap-8 mb-8">
            <!-- FFmpeg Service Card -->
            <div class="service-card bg-white rounded-xl shadow-lg p-8 relative border-2 border-transparent transition-all"
                 onclick="selectService(this, 'ffmpeg')">

                <!-- Speed Badge -->
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Fast
                    </span>
                </div>

                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center mb-3">
                        <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-bold text-gray-900">FFmpeg Captions</h3>
                            <p class="text-sm text-gray-500">Fast & Efficient</p>
                        </div>

                        <!-- Info Button -->
                        <button type="button"
                                class="ml-auto p-2 text-gray-400 hover:text-gray-600 rounded-lg"
                                onmouseenter="showTooltip(event, 'ffmpeg-tooltip')"
                                onmouseleave="hideTooltip('ffmpeg-tooltip')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>
                    </div>

                    <p class="text-gray-600">
                        Quick subtitle generation using FFmpeg with ASS styling. Perfect for simple, clean captions.
                    </p>
                </div>

                <!-- Features -->
                <div class="space-y-3 mb-6">
                    <div class="feature-check flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Lightning fast processing (30s - 2min)
                    </div>
                    <div class="feature-check flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Built-in presets & Google Fonts
                    </div>
                    <div class="feature-check flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Preview frame generation
                    </div>
                    <div class="feature-check flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Color & outline customization
                    </div>
                    <div class="feature-cross flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Limited animation options
                    </div>
                </div>

                <!-- Best For -->
                <div class="bg-orange-50 rounded-lg p-4">
                    <h4 class="font-medium text-orange-900 mb-2">Best for:</h4>
                    <ul class="text-sm text-orange-700 space-y-1">
                        <li>• Quick turnaround projects</li>
                        <li>• Simple, clean caption styles</li>
                        <li>• Batch processing multiple videos</li>
                        <li>• Educational or professional content</li>
                    </ul>
                </div>
            </div>

            <!-- Remotion Service Card -->
            <div class="service-card bg-white rounded-xl shadow-lg p-8 relative border-2 border-transparent transition-all"
                 onclick="selectService(this, 'remotion')">

                <!-- Creative Badge -->
                <div class="absolute top-4 right-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/>
                        </svg>
                        Creative
                    </span>
                </div>

                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center mb-3">
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" stroke="#8E24AA"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M21.37 36C22.82 30.75 27.89 27 33.73 27.62C39.29 28.21 43.71 32.9 43.99 38.48C44.06 39.95 43.86 41.36 43.43 42.67C43.17 43.47 42.39 44 41.54 44H11.7584C6.71004 44 2.92371 39.3814 3.91377 34.4311L9.99994 4H21.9999L25.9999 11L17.43 17.13L14.9999 14" stroke="#8E24AA" stroke-width="4" stroke-miterlimit="2" stroke-linecap="round" stroke-linejoin="round"></path> <path d="M17.4399 17.13L22 34" stroke="#8E24AA" stroke-width="4" stroke-miterlimit="2" stroke-linecap="round" stroke-linejoin="round"></path> </g></svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-xl font-bold text-gray-900">Remotion Captions</h3>
                            <p class="text-sm text-gray-500">Advanced & Customizable</p>
                        </div>

                        <!-- Info Button -->
                        <button type="button"
                                class="ml-auto p-2 text-gray-400 hover:text-gray-600 rounded-lg"
                                onmouseenter="showTooltip(event, 'remotion-tooltip')"
                                onmouseleave="hideTooltip('remotion-tooltip')">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </button>
                    </div>

                    <p class="text-gray-600">
                        Professional video captions with React-powered animations and advanced styling options.
                    </p>
                </div>

                <!-- Features -->
                <div class="space-y-3 mb-6">
                    <div class="feature-check flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Word-by-word highlighting animations
                    </div>
                    <div class="feature-check flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Custom background highlights
                    </div>
                    <div class="feature-check flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        React-based styling system
                    </div>
                    <div class="feature-check flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        Platform-specific presets (TikTok, Instagram)
                    </div>
                    <div class="feature-cross flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                        Slower processing (2-10min)
                    </div>
                </div>

                <!-- Best For -->
                <div class="bg-purple-50 rounded-lg p-4">
                    <h4 class="font-medium text-purple-900 mb-2">Best for:</h4>
                    <ul class="text-sm text-purple-700 space-y-1">
                        <li>• Social media content (TikTok, Instagram)</li>
                        <li>• Creative projects with custom branding</li>
                        <li>• Marketing videos requiring impact</li>
                        <li>• Content with dynamic word highlights</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-center space-x-4">
            <button type="button"
                    onclick="window.history.back()"
                    class="inline-flex items-center px-6 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Transcription
            </button>

            <button type="button"
                    id="continue-btn"
                    onclick="continueWithService()"
                    disabled
                    class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-gray-400 cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                Continue to Configuration
            </button>
        </div>
    </main>

    <!-- Tooltips -->
    <div id="ffmpeg-tooltip" class="tooltip absolute bg-gray-900 text-white text-sm rounded-lg px-3 py-2 max-w-xs">
        <div class="font-medium mb-1">FFmpeg Captions</div>
        <div>Uses FFmpeg with ASS subtitle format for fast processing. Ideal for professional content requiring quick turnaround with reliable results.</div>
    </div>

    <div id="remotion-tooltip" class="tooltip absolute bg-gray-900 text-white text-sm rounded-lg px-3 py-2 max-w-xs">
        <div class="font-medium mb-1">Remotion Captions</div>
        <div>React-based video generation with advanced animations and customization. Perfect for social media content requiring engaging visual effects.</div>
    </div>

    <!-- Settings Modal -->
    <?php include "../components/settings-modal.php"; ?>

    <!-- JavaScript -->
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/api.js"></script>
    <script>
        let selectedService = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎯 Service Choice page loaded');
        });

        function selectService(cardElem, service) {
            selectedService = service;

            // Remove selection on all cards
            document.querySelectorAll('.service-card').forEach(card => {
                card.classList.remove('selected');
            });

            // Add selection to the clicked card
            cardElem.classList.add('selected');

            // Enable continue button
            const continueBtn = document.getElementById('continue-btn');
            continueBtn.disabled = false;
            continueBtn.classList.remove('bg-gray-400', 'cursor-not-allowed');
            continueBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');

            showNotification('success', 'Service Selected', `${service === 'ffmpeg' ? 'FFmpeg' : 'Remotion'} service selected`);
        }

        function continueWithService() {
            if (!selectedService) {
                showNotification('warning', 'No Service Selected', 'Please select a service to continue');
                return;
            }

            // Save selected service
            sessionStorage.setItem('selectedService', selectedService);

            // Redirect based on service
            const targetPage = selectedService === 'ffmpeg' ? 'ffmpeg-config.php' : 'remotion-config.php';

            showNotification('success', 'Redirecting', `Configuring ${selectedService} captions...`);

            setTimeout(() => {
                window.location.href = targetPage;
            }, 1000);
        }

        function showTooltip(event, tooltipId) {
            const tooltip = document.getElementById(tooltipId);
            if (!tooltip) return;

            const rect = event.target.getBoundingClientRect();
            tooltip.style.left = `${rect.left}px`;
            tooltip.style.top = `${rect.bottom + 8}px`;

            tooltip.classList.add('show');
        }

        function hideTooltip(tooltipId) {
            const tooltip = document.getElementById(tooltipId);
            if (!tooltip) return;

            tooltip.classList.remove('show');
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            // 1 or F for FFmpeg
            if (event.key === '1' || event.key.toLowerCase() === 'f') {
                event.preventDefault();
                selectService(this, 'ffmpeg');
            }

            // 2 or R for Remotion
            if (event.key === '2' || event.key.toLowerCase() === 'r') {
                event.preventDefault();
                selectService(this, 'remotion');
            }

            // Enter to continue
            if (event.key === 'Enter' && selectedService) {
                event.preventDefault();
                continueWithService();
            }
        });
    </script>
</body>
</html>
