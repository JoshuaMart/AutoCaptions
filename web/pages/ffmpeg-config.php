<?php
/**
 * FFmpeg Configuration Page
 * Configure FFmpeg caption settings with preview functionality
 */

session_start();
require_once "../config/services.php";

// Check if we have transcription data and selected service
if (!isset($_SESSION["transcription_data"]) && !isset($_GET["demo"])) {
    $_GET["demo"] = true;
}

$selectedService = $_SESSION["selected_service"] ?? "ffmpeg";
if ($selectedService !== "ffmpeg") {
    header("Location: service-choice.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FFmpeg Configuration - AutoCaptions</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom styling -->
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
            padding: 0;
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
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <?php include "../components/header.php"; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        FFmpeg Caption Configuration
                    </h1>
                    <p class="text-lg text-gray-600">
                        Customize your caption style and preview the result
                    </p>
                </div>

                <!-- Progress Steps -->
                <div class="hidden lg:flex items-center space-x-4">
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
                        <span class="text-green-600">Service</span>
                    </div>
                    <div class="w-8 h-px bg-green-600"></div>
                    <div class="flex items-center text-sm">
                        <div class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center mr-2">4</div>
                        <span class="text-blue-600 font-medium">Configuration</span>
                    </div>
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

                <!-- Font Configuration -->
                <div class="config-section bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <button type="button" onclick="toggleSection('font-section')" class="w-full flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Font Settings</h3>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform section-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>

                    <div id="font-section" class="section-content p-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Font Family</label>
                                <select id="fontFamily" onchange="updatePreview()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Loading fonts...</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Font Size</label>
                                <div class="flex items-center space-x-3">
                                    <input type="range" id="fontSize" min="40" max="150" value="80" onchange="updatePreview(); updateRangeValue('fontSize')" class="flex-1">
                                    <span id="fontSize-value" class="text-sm text-gray-600 w-12">80px</span>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Font Weight</label>
                                <select id="fontWeight" onchange="updatePreview()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="400">Normal (400)</option>
                                    <option value="500">Medium (500)</option>
                                    <option value="600">Semi Bold (600)</option>
                                    <option value="700" selected>Bold (700)</option>
                                    <option value="800">Extra Bold (800)</option>
                                    <option value="900">Black (900)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Text Transform</label>
                                <label class="flex items-center">
                                    <input type="checkbox" id="uppercase" onchange="updatePreview()" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">UPPERCASE</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colors Configuration -->
                <div class="config-section bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <button type="button" onclick="toggleSection('colors-section')" class="w-full flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Colors & Effects</h3>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform section-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                    <div id="colors-section" class="section-content p-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <!-- Text Colors -->
                            <div class="space-y-4">
                                <h4 class="font-medium text-gray-900">Text Colors</h4>

                                <div class="flex items-center space-x-3">
                                    <label class="text-sm text-gray-700 w-20">Text:</label>
                                    <input type="color" id="textColor" value="#ffffff" onchange="updatePreview()" class="color-input">
                                    <input type="text" id="textColorHex" value="FFFFFF" onchange="updateColorFromHex('textColor', this.value); updatePreview()" class="px-2 py-1 border border-gray-300 rounded text-sm w-20">
                                </div>

                                <div class="flex items-center space-x-3">
                                    <label class="text-sm text-gray-700 w-20">Outline:</label>
                                    <input type="color" id="outlineColor" value="#000000" onchange="updatePreview()" class="color-input">
                                    <input type="text" id="outlineColorHex" value="000000" onchange="updateColorFromHex('outlineColor', this.value); updatePreview()" class="px-2 py-1 border border-gray-300 rounded text-sm w-20">
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-700 mb-2">Outline Width</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="range" id="outlineWidth" min="0" max="10" value="4" onchange="updatePreview(); updateRangeValue('outlineWidth')" class="flex-1">
                                        <span id="outlineWidth-value" class="text-sm text-gray-600 w-8">4px</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Word Colors -->
                            <div class="space-y-4">
                                <h4 class="font-medium text-gray-900">Active Word</h4>

                                <div class="flex items-center space-x-3">
                                    <label class="text-sm text-gray-700 w-20">Color:</label>
                                    <input type="color" id="activeWordColor" value="#ffff00" onchange="updatePreview()" class="color-input">
                                    <input type="text" id="activeWordColorHex" value="FFFF00" onchange="updateColorFromHex('activeWordColor', this.value); updatePreview()" class="px-2 py-1 border border-gray-300 rounded text-sm w-20">
                                </div>

                                <div class="flex items-center space-x-3">
                                    <label class="text-sm text-gray-700 w-20">Outline:</label>
                                    <input type="color" id="activeWordOutlineColor" value="#000000" onchange="updatePreview()" class="color-input">
                                    <input type="text" id="activeWordOutlineColorHex" value="000000" onchange="updateColorFromHex('activeWordOutlineColor', this.value); updatePreview()" class="px-2 py-1 border border-gray-300 rounded text-sm w-20">
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-700 mb-2">Font Size</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="range" id="activeWordFontSize" min="40" max="200" value="85" onchange="updatePreview(); updateRangeValue('activeWordFontSize')" class="flex-1">
                                        <span id="activeWordFontSize-value" class="text-sm text-gray-600 w-12">85px</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Position Configuration -->
                <div class="config-section bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <button type="button" onclick="toggleSection('position-section')" class="w-full flex items-center justify-between">
                            <h3 class="text-lg font-medium text-gray-900">Position & Layout</h3>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform section-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </div>
                    <div id="position-section" class="section-content p-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Base Position</label>
                                <select id="position" onchange="updatePreview()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="top">Top</option>
                                    <option value="center" selected>Center</option>
                                    <option value="bottom">Bottom</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm text-gray-700 mb-2">Position Offset</label>
                                <div class="flex items-center space-x-3">
                                    <input type="range" id="positionOffset" min="-500" max="500" value="300" onchange="updatePreview(); updateRangeValue('positionOffset')" class="flex-1">
                                    <span id="positionOffset-value" class="text-sm text-gray-600 w-12">300px</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">+ moves down, - moves up</p>
                            </div>
                        </div>
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
                            class="flex-1 inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
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
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-sm">Click "Generate Preview"<br>to see your captions</p>
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
                    </div>

                    <!-- Preview Info -->
                    <div class="mt-4 text-sm text-gray-600">
                        <p>• Preview shows a single frame with captions</p>
                        <p>• Adjust settings and regenerate to see changes</p>
                        <p>• Final video will have word-by-word highlighting</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Settings Modal -->
    <?php include "../components/settings-modal.php"; ?>

    <!-- JavaScript -->
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/api.js"></script>
    <script>
        // Global variables
        let currentPreset = 'custom';
        let availablePresets = {};
        let availableFonts = [];
        let transcriptionData = null;
        let selectedService = 'ffmpeg';

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎨 FFmpeg Configuration page loaded');
            loadTranscriptionData();
            loadPresets();
            loadFonts();
            initializeRangeValues();
        });

        function loadTranscriptionData() {
            const sessionData = sessionStorage.getItem('transcriptionData');
            if (sessionData) {
                try {
                    transcriptionData = JSON.parse(sessionData);
                    console.log('✅ Transcription data loaded');
                } catch (error) {
                    console.error('Failed to parse transcription data:', error);
                    showDemoData();
                }
            } else {
                showDemoData();
            }
        }

        function showDemoData() {
            transcriptionData = {
                success: true,
                transcription: {
                    captions: [
                        { text: "Hello", startMs: 0, endMs: 500 },
                        { text: "world", startMs: 500, endMs: 1000 },
                        { text: "this", startMs: 1000, endMs: 1300 },
                        { text: "is", startMs: 1300, endMs: 1500 },
                        { text: "a", startMs: 1500, endMs: 1600 },
                        { text: "test", startMs: 1600, endMs: 2000 }
                    ],
                    duration: 2.5,
                    language: "en"
                }
            };
        }

        async function loadPresets() {
            try {
                // Premier appel : liste des presets
                const result = await API.call('ffmpeg_captions', 'api/captions/presets', 'GET');

                if (result.success && result.presets) {
                    availablePresets = result.presets;

                    // Charger les détails de chaque preset
                    for (let preset of availablePresets) {
                        try {
                            const detailResult = await API.call('ffmpeg_captions', `api/captions/presets/${preset.name}`, 'GET');
                            if (detailResult.success && detailResult.preset) {
                                // Merge les détails dans le preset
                                Object.assign(preset, detailResult.preset);
                            }
                        } catch (error) {
                            console.warn(`Failed to load details for preset ${preset.name}:`, error);
                        }
                    }

                    renderPresets();
                    document.getElementById('presets-loading').classList.add('hidden');
                    document.getElementById('presets-list').classList.remove('hidden');
                } else {
                    throw new Error('Failed to load presets');
                }
            } catch (error) {
                console.error('❌ Failed to load presets:', error);
                showNotification('error', 'Loading Failed', 'Could not load presets. Using defaults.');
                showDefaultPresets();
            }
        }

        function renderPresets() {
            const container = document.getElementById('presets-list');

            container.innerHTML = availablePresets.map(preset => `
                <div class="preset-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-300 transition-colors duration-200"
                     data-preset="${preset.name}"
                     onclick="selectPreset('${preset.name}')">
                    <h4 class="font-medium text-gray-900">${preset.displayName}</h4>
                    <p class="text-sm text-gray-600 mt-1">${preset.description}</p>
                    <div class="mt-2 text-xs text-gray-500">
                        ${preset.customizable ? preset.customizable.length : 0} customizable options
                    </div>
                </div>
            `).join('');
        }

        function showDefaultPresets() {
            availablePresets = [{
                name: 'custom',
                displayName: 'Custom',
                description: 'Fully customizable caption style',
                defaults: {
                    fontFamily: 'Inter',
                    fontSize: 80,
                    fontWeight: 700,
                    textColor: 'FFFFFF',
                    outlineColor: '000000',
                    outlineWidth: 4,
                    activeWordColor: 'FFFF00',
                    position: 'center',
                    positionOffset: 300
                }
            }];
            renderPresets();
            document.getElementById('presets-loading').classList.add('hidden');
            document.getElementById('presets-list').classList.remove('hidden');
        }

        async function loadFonts() {
            try {
                const result = await API.call('ffmpeg_captions', 'api/captions/fonts', 'GET');

                if (result.success && result.fonts) {
                    availableFonts = result.fonts;
                    renderFonts();
                } else {
                    throw new Error('Failed to load fonts');
                }
            } catch (error) {
                console.error('❌ Failed to load fonts:', error);
                showDefaultFonts();
            }
        }

        function renderFonts() {
            const select = document.getElementById('fontFamily');
            select.innerHTML = availableFonts.map(font =>
                `<option value="${font.family}">${font.family}</option>`
            ).join('');

            // Set default font
            select.value = 'Inter';
        }

        function showDefaultFonts() {
            const select = document.getElementById('fontFamily');
            select.innerHTML = `
                <option value="Inter">Inter</option>
                <option value="Arial Black">Arial Black</option>
                <option value="Montserrat">Montserrat</option>
                <option value="Roboto">Roboto</option>
            `;
            select.value = 'Inter';
        }

        function selectPreset(presetName) {
            currentPreset = presetName;

            // Update visual selection
            document.querySelectorAll('.preset-card').forEach(card => {
                card.classList.remove('border-blue-500', 'bg-blue-50');
                card.classList.add('border-gray-200');
            });

            const selectedCard = document.querySelector(`[data-preset="${presetName}"]`);
            if (selectedCard) {
                selectedCard.classList.remove('border-gray-200');
                selectedCard.classList.add('border-blue-500', 'bg-blue-50');
            }

            // Load preset defaults and show/hide customizable options
            const preset = availablePresets.find(p => p.name === presetName);
            if (preset) {
                if (preset.defaults) {
                    applyPresetDefaults(preset.defaults);
                }

                // Show/hide customizable sections
                updateCustomizableInterface(preset.customizable || []);
            }

            console.log(`✅ Selected preset: ${presetName}`);
            showNotification('success', 'Preset Selected', `Applied ${preset?.displayName || presetName} preset`);
        }

        function updateCustomizableInterface(customizableOptions) {
            // Mapping des options vers les sections/éléments
            const optionMapping = {
                // Font section
                'fontFamily': 'font-section',
                'fontSize': 'font-section',
                'fontWeight': 'font-section',
                'uppercase': 'font-section',

                // Colors section
                'textColor': 'colors-section',
                'outlineColor': 'colors-section',
                'outlineWidth': 'colors-section',
                'activeWordColor': 'colors-section',
                'activeWordOutlineColor': 'colors-section',
                'activeWordFontSize': 'colors-section',
                'shadowColor': 'colors-section',
                'shadowOpacity': 'colors-section',
                'activeWordShadowColor': 'colors-section',
                'activeWordShadowOpacity': 'colors-section',

                // Position section
                'position': 'position-section',
                'positionOffset': 'position-section',
                'backgroundColor': 'position-section',
                'backgroundOpacity': 'position-section'
            };

            // Get customizable keys
            const customizableKeys = customizableOptions.map(option => option.key);

            // Show/hide individual controls
            Object.keys(optionMapping).forEach(optionKey => {
                const element = document.getElementById(optionKey);
                const container = element?.closest('.grid > div, .space-y-4 > div, .flex');

                if (container) {
                    if (customizableKeys.includes(optionKey)) {
                        container.style.display = '';
                        container.classList.remove('opacity-50', 'pointer-events-none');
                    } else {
                        // Option not customizable - disable but keep visible
                        container.classList.add('opacity-50', 'pointer-events-none');

                        // Or hide completely:
                        // container.style.display = 'none';
                    }
                }
            });

            // Show/hide entire sections if no customizable options
            const sections = ['font-section', 'colors-section', 'position-section'];

            sections.forEach(sectionId => {
                const hasCustomizableInSection = customizableKeys.some(key =>
                    optionMapping[key] === sectionId
                );

                const sectionElement = document.getElementById(sectionId);
                const sectionContainer = sectionElement?.closest('.config-section');

                if (sectionContainer) {
                    if (hasCustomizableInSection) {
                        sectionContainer.style.display = '';
                        sectionContainer.classList.remove('opacity-50');
                    } else {
                        // Hide section completely or disable it
                        sectionContainer.classList.add('opacity-50');
                        // ou : sectionContainer.style.display = 'none';
                    }
                }
            });

            console.log(`🎨 Updated interface for ${customizableKeys.length} customizable options`);
        }

        function applyPresetDefaults(defaults) {
           Object.keys(defaults).forEach(key => {
               const element = document.getElementById(key);
               if (element) {
                   if (element.type === 'checkbox') {
                       element.checked = defaults[key];
                   } else if (element.type === 'color') {
                       element.value = '#' + defaults[key];
                       // Update hex input too
                       const hexInput = document.getElementById(key + 'Hex');
                       if (hexInput) hexInput.value = defaults[key];
                   } else {
                       element.value = defaults[key];
                   }

                   // Update range value displays
                   updateRangeValue(key);
               }
           });

           updatePreview();
       }

       function initializeRangeValues() {
           // Initialize all range value displays
           ['fontSize', 'outlineWidth', 'activeWordFontSize', 'positionOffset'].forEach(id => {
               updateRangeValue(id);
           });
       }

       function updateRangeValue(inputId) {
           const input = document.getElementById(inputId);
           const display = document.getElementById(inputId + '-value');

           if (input && display) {
               const unit = inputId.includes('Offset') ? 'px' : (inputId.includes('Size') ? 'px' : (inputId.includes('Width') ? 'px' : ''));
               display.textContent = input.value + unit;
           }
       }

       function updateColorFromHex(colorInputId, hexValue) {
           const colorInput = document.getElementById(colorInputId);
           if (colorInput) {
               // Remove # if present and ensure 6 characters
               hexValue = hexValue.replace('#', '').substring(0, 6);
               colorInput.value = '#' + hexValue;
           }
       }

       function toggleSection(sectionId) {
           const section = document.getElementById(sectionId).parentElement;
           const arrow = section.querySelector('.section-arrow');

           section.classList.toggle('collapsed');
           arrow.style.transform = section.classList.contains('collapsed') ? 'rotate(-90deg)' : 'rotate(0deg)';
       }

       function updatePreview() {
           // This function could show a live CSS preview in the future
           console.log('🎨 Preview updated with current settings');
       }

       async function generatePreview() {
           if (!transcriptionData) {
               showNotification('error', 'No Data', 'No transcription data available for preview');
               return;
           }

           // Check if we have a saved video file
           const uploadId = sessionStorage.getItem('uploadId');
           if (!uploadId) {
               showNotification('warning', 'No Video File', 'Using demo preview. Upload a video for real preview.');
               showDemoPreview();
               return;
           }

           const previewBtn = document.getElementById('preview-btn');
           const placeholder = document.getElementById('preview-placeholder');
           const loading = document.getElementById('preview-loading');
           const image = document.getElementById('preview-image');

           // Show loading state
           placeholder.classList.add('hidden');
           image.classList.add('hidden');
           loading.classList.remove('hidden');

           previewBtn.disabled = true;
           previewBtn.innerHTML = `
               <svg class="animate-spin w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
               </svg>
               Generating...
           `;

           try {
               // Prepare configuration
               const config = {
                   preset: currentPreset,
                   customStyle: getCurrentConfiguration(),
                   transcriptionData: transcriptionData
               };

               console.log('🎬 Generating preview with config:', config);

               // Prepare form data for direct API call
               const formData = new FormData();
               formData.append('uploadId', uploadId);
               formData.append('data', JSON.stringify(config));

               // Make direct API call to preview endpoint
               const response = await fetch('/api/direct-preview.php?position=middle', {
                   method: 'POST',
                   body: formData
               });

               console.log('📡 Preview response status:', response.status);
               console.log('📡 Preview response content type:', response.headers.get('Content-Type'));

               if (!response.ok) {
                   // Try to get error message from response
                   const contentType = response.headers.get('Content-Type');
                   let errorMessage = 'Preview generation failed';

                   if (contentType && contentType.includes('application/json')) {
                       const errorData = await response.json();
                       errorMessage = errorData.error || errorMessage;
                   } else {
                       const errorText = await response.text();
                       console.error('Preview error response:', errorText);

                       // Try to parse as JSON in case of wrong content type
                       try {
                           const errorJson = JSON.parse(errorText);
                           errorMessage = errorJson.error || errorMessage;
                       } catch (e) {
                           errorMessage = errorText || errorMessage;
                       }
                   }

                   throw new Error(errorMessage);
               }

               // Check response content type
               const contentType = response.headers.get('Content-Type');
               console.log('✅ Preview response content type:', contentType);

               if (contentType && contentType.startsWith('image/')) {
                   // Success - display the preview image
                   const imageBlob = await response.blob();
                   
                   console.log('🖼️ Image blob size:', imageBlob.size, 'bytes');
                   
                   // Convert blob to Data URL instead of using blob URL
                   const reader = new FileReader();
                   reader.onload = function(e) {
                       console.log('✅ Image converted to Data URL');
                       
                       // Set the Data URL as source
                       image.src = e.target.result;
                       
                       // Force display immediately
                       loading.classList.add('hidden');
                       image.classList.remove('hidden');
                       
                       console.log('✅ Preview image displayed successfully');
                   };
                   
                   reader.onerror = function(e) {
                       console.error('❌ Failed to convert image to Data URL:', e);
                       showNotification('error', 'Image Processing Failed', 'Could not process preview image');
                       showDemoPreview();
                   };
                   
                   // Start conversion
                   reader.readAsDataURL(imageBlob);
                   
                   showNotification('success', 'Preview Generated', 'Real caption preview ready');

               } else {
                   // Unexpected response type
                   console.error('❌ Unexpected content type:', contentType);
                   const responseText = await response.text();
                   console.error('Response body:', responseText);
                   throw new Error('Unexpected response format: ' + contentType);
               }

           } catch (error) {
               console.error('❌ Preview generation failed:', error);
               showNotification('warning', 'Preview Failed', 'Using demo preview instead: ' + error.message);
               showDemoPreview();

           } finally {
               // Restore button state
               previewBtn.disabled = false;
               previewBtn.innerHTML = `
                   <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                       <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                   </svg>
                   Generate Preview
               `;
           }
       }

       function debugPreview() {
           console.log('🔍 Debug Preview State:');
           console.log('- Upload ID:', sessionStorage.getItem('uploadId'));
           console.log('- Transcription Data:', transcriptionData);
           console.log('- Current Preset:', currentPreset);
           console.log('- Current Config:', getCurrentConfiguration());

           // Test direct API call with minimal data
           fetch('/api/direct-preview.php', {
               method: 'POST',
               body: new FormData()
           })
           .then(response => {
               console.log('🔍 Direct API test - Status:', response.status);
               console.log('🔍 Direct API test - Content-Type:', response.headers.get('Content-Type'));
               return response.text();
           })
           .then(text => {
               console.log('🔍 Direct API test - Response:', text.substring(0, 200));
           })
           .catch(error => {
               console.error('🔍 Direct API test - Error:', error);
           });
       }

       function showDemoPreview() {
           const loading = document.getElementById('preview-loading');
           const image = document.getElementById('preview-image');

           // Create a demo preview using canvas
           const canvas = document.createElement('canvas');
           canvas.width = 360;  // 9:16 aspect ratio
           canvas.height = 640;
           const ctx = canvas.getContext('2d');

           // Background
           ctx.fillStyle = '#1a1a1a';
           ctx.fillRect(0, 0, canvas.width, canvas.height);

           // Get current settings
           const config = getCurrentConfiguration();

           // Draw sample text
           ctx.textAlign = 'center';
           ctx.font = `${config.fontWeight} ${Math.round(config.fontSize * 0.8)}px ${config.fontFamily}`;

           // Text with outline
           const text = 'Sample Caption Text';
           const x = canvas.width / 2;
           const y = canvas.height / 2 + (config.positionOffset * 0.5);

           // Outline
           ctx.strokeStyle = '#' + config.outlineColor;
           ctx.lineWidth = config.outlineWidth;
           ctx.strokeText(text, x, y);

           // Fill
           ctx.fillStyle = '#' + config.textColor;
           ctx.fillText(text, x, y);

           // Active word highlight
           ctx.font = `${config.fontWeight} ${Math.round(config.activeWordFontSize * 0.8)}px ${config.fontFamily}`;
           ctx.strokeStyle = '#' + config.outlineColor;
           ctx.lineWidth = config.outlineWidth;
           ctx.strokeText('Active', x - 60, y + 60);
           ctx.fillStyle = '#' + config.activeWordColor;
           ctx.fillText('Active', x - 60, y + 60);

           // Convert to blob and display
           canvas.toBlob(blob => {
               const imageUrl = URL.createObjectURL(blob);
               image.src = imageUrl;
               image.onload = () => {
                   loading.classList.add('hidden');
                   image.classList.remove('hidden');
               };
           });
       }

       function getCurrentConfiguration() {
           return {
               fontFamily: document.getElementById('fontFamily').value || 'Inter',
               fontSize: parseInt(document.getElementById('fontSize').value) || 80,
               fontWeight: parseInt(document.getElementById('fontWeight').value) || 700,
               uppercase: document.getElementById('uppercase').checked,
               textColor: document.getElementById('textColorHex').value || 'FFFFFF',
               outlineColor: document.getElementById('outlineColorHex').value || '000000',
               outlineWidth: parseInt(document.getElementById('outlineWidth').value) || 4,
               activeWordColor: document.getElementById('activeWordColorHex').value || 'FFFF00',
               activeWordOutlineColor: document.getElementById('activeWordOutlineColorHex').value || '000000',
               activeWordOutlineWidth: parseInt(document.getElementById('outlineWidth').value) || 4,
               activeWordFontSize: parseInt(document.getElementById('activeWordFontSize').value) || 85,
               position: document.getElementById('position').value || 'center',
               positionOffset: parseInt(document.getElementById('positionOffset').value) || 300,
               backgroundColor: '000000',
               backgroundOpacity: 0
           };
       }

       async function generateFinalVideo() {
           if (!transcriptionData) {
               showNotification('error', 'No Data', 'No transcription data available');
               return;
           }

           const uploadId = sessionStorage.getItem('uploadId');
           if (!uploadId) {
               showNotification('error', 'No Video', 'No video file found. Please upload a video first.');
               return;
           }

           const generateBtn = document.getElementById('generate-btn');
           const originalText = generateBtn.innerHTML;

           // Show loading state
           generateBtn.disabled = true;
           generateBtn.innerHTML = `
               <svg class="animate-spin w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
               </svg>
               Preparing Generation...
           `;

           try {
               // Prepare final configuration
               const config = {
                   preset: currentPreset,
                   customStyle: getCurrentConfiguration(),
                   transcriptionData: transcriptionData
               };

               // Validate configuration
               if (!config.transcriptionData || !config.transcriptionData.transcription || 
                   !config.transcriptionData.transcription.captions || 
                   config.transcriptionData.transcription.captions.length === 0) {
                   throw new Error('Invalid transcription data');
               }

               console.log('🎬 Starting video generation with config:', {
                   preset: config.preset,
                   captionsCount: config.transcriptionData.transcription.captions.length,
                   service: 'ffmpeg',
                   uploadId: uploadId
               });

               // Save configuration for result page
               sessionStorage.setItem('finalConfig', JSON.stringify(config));
               sessionStorage.setItem('selectedService', 'ffmpeg');

               showNotification('success', 'Configuration Ready', 'Redirecting to generation page...');

               // Redirect to result page
               setTimeout(() => {
                   window.location.href = 'result.php';
               }, 1500);

           } catch (error) {
               console.error('❌ Failed to prepare generation:', error);
               showNotification('error', 'Preparation Failed', error.message);

               // Restore button state
               generateBtn.disabled = false;
               generateBtn.innerHTML = originalText;
           }
       }

       async function refreshPresets() {
           document.getElementById('presets-loading').classList.remove('hidden');
           document.getElementById('presets-list').classList.add('hidden');
           await loadPresets();
       }

       // Auto-select first preset on load
       setTimeout(() => {
           if (availablePresets.length > 0) {
               selectPreset(availablePresets[0].name);
           }
       }, 1000);

       // Keyboard shortcuts
       document.addEventListener('keydown', function(event) {
           // P for preview
           if (event.key.toLowerCase() === 'p' && !event.ctrlKey && !event.metaKey) {
               event.preventDefault();
               generatePreview();
           }

           // Enter for generate
           if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
               event.preventDefault();
               generateFinalVideo();
           }
       });

       // Color input synchronization
       ['textColor', 'outlineColor', 'activeWordColor', 'activeWordOutlineColor'].forEach(colorId => {
           const colorInput = document.getElementById(colorId);
           const hexInput = document.getElementById(colorId + 'Hex');

           if (colorInput && hexInput) {
               colorInput.addEventListener('input', function() {
                   hexInput.value = this.value.substring(1).toUpperCase();
                   updatePreview();
               });

               hexInput.addEventListener('input', function() {
                   const hex = this.value.replace('#', '').substring(0, 6);
                   if (hex.length === 6) {
                       colorInput.value = '#' + hex;
                       updatePreview();
                   }
               });
           }
       });
   </script>
</body>
</html>
