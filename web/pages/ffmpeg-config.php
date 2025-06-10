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
                                class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                disabled>
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
        let currentPreset = null;
        let currentPresetDetails = null;
        let availablePresets = [];
        let availableFonts = [];
        let transcriptionData = null;
        let selectedService = 'ffmpeg';

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎨 FFmpeg Configuration page loaded');
            loadTranscriptionData();
            loadPresets();
            loadFonts();
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
                const result = await API.call('ffmpeg_captions', 'api/captions/presets', 'GET');

                if (result.success && result.presets) {
                    availablePresets = result.presets;
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
                        Click to configure options
                    </div>
                </div>
            `).join('');
        }

        function showDefaultPresets() {
            availablePresets = [{
                name: 'custom',
                displayName: 'Custom',
                description: 'Fully customizable caption style'
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
                } else {
                    throw new Error('Failed to load fonts');
                }
            } catch (error) {
                console.error('❌ Failed to load fonts:', error);
                showDefaultFonts();
            }
        }

        function showDefaultFonts() {
            availableFonts = [
                { family: 'Inter', variants: ['400', '600', '700', '800'], category: 'sans-serif' },
                { family: 'Arial Black', variants: ['400'], category: 'sans-serif' },
                { family: 'Montserrat', variants: ['400', '600', '700', '800'], category: 'sans-serif' },
                { family: 'Roboto', variants: ['400', '500', '700'], category: 'sans-serif' }
            ];
        }

        async function selectPreset(presetName) {
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

            // Show loading in config container
            const configContainer = document.getElementById('dynamic-config-container');
            configContainer.innerHTML = `
                <div class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="animate-spin mx-auto h-8 w-8 text-blue-600 mb-4">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <p class="text-gray-600">Loading preset configuration...</p>
                </div>
            `;

            try {
                // Load preset details from API
                const result = await API.call('ffmpeg_captions', `api/captions/presets/${presetName}`, 'GET');

                if (result.success && result.preset) {
                    currentPresetDetails = result.preset;
                    generateConfigurationInterface(result.preset);

                    // Enable buttons
                    document.getElementById('preview-btn').disabled = false;
                    document.getElementById('generate-btn').disabled = false;

                    console.log(`✅ Preset '${presetName}' loaded with ${result.preset.customizable?.length || 0} customizable options`);
                    showNotification('success', 'Preset Loaded', `${result.preset.displayName} configuration loaded`);
                } else {
                    throw new Error('Failed to load preset details');
                }
            } catch (error) {
                console.error('❌ Failed to load preset details:', error);
                showNotification('error', 'Loading Failed', 'Could not load preset configuration');
                showNoPresetMessage();
            }
        }

        function generateConfigurationInterface(preset) {
            const container = document.getElementById('dynamic-config-container');

            if (!preset.customizable || preset.customizable.length === 0) {
                container.innerHTML = `
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <div class="text-green-500 mb-4">
                            <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Preset Ready</h3>
                        <p class="text-gray-600 mb-4">This preset has predefined settings and doesn't require configuration.</p>
                        <p class="text-sm text-gray-500">You can generate a preview or proceed directly to video generation.</p>
                    </div>
                `;
                return;
            }

            // Group customizable options by category
            const groups = groupCustomizableOptions(preset.customizable);

            let sectionsHTML = '';

            for (const [groupName, options] of Object.entries(groups)) {
                if (options.length === 0) continue;

                const sectionId = `${groupName.toLowerCase().replace(/\s+/g, '-')}-section`;

                sectionsHTML += `
                    <div class="config-section bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <button type="button" onclick="toggleSection('${sectionId}')" class="w-full flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">${groupName}</h3>
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform section-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                        </div>
                        <div id="${sectionId}" class="section-content p-6">
                            <div class="grid md:grid-cols-2 gap-6">
                                ${generateFieldsHTML(options, preset.defaults)}
                            </div>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = sectionsHTML;

            // Apply default values
            applyPresetDefaults(preset.defaults);

            // Initialize range values and event listeners
            initializeFormElements();
        }

        function groupCustomizableOptions(customizableOptions) {
            const groups = {
                'Font Settings': [],
                'Colors & Effects': [],
                'Position & Layout': [],
                'Background & Shadows': [],
                'Other Settings': []
            };

            customizableOptions.forEach(option => {
                const key = option.key.toLowerCase();

                if (key.includes('font') || key.includes('weight') || key === 'uppercase') {
                    groups['Font Settings'].push(option);
                } else if (key.includes('color') || key.includes('outline') || key.includes('activeword')) {
                    groups['Colors & Effects'].push(option);
                } else if (key.includes('position') || key.includes('offset')) {
                    groups['Position & Layout'].push(option);
                } else if (key.includes('background') || key.includes('shadow')) {
                    groups['Background & Shadows'].push(option);
                } else {
                    groups['Other Settings'].push(option);
                }
            });

            // Remove empty groups
            Object.keys(groups).forEach(key => {
                if (groups[key].length === 0) {
                    delete groups[key];
                }
            });

            return groups;
        }

        function generateFieldsHTML(options, defaults) {
            return options.map(option => {
                const defaultValue = defaults?.[option.key] || '';

                switch (option.type) {
                    case 'font':
                        return generateFontField(option, defaultValue);
                    case 'number':
                        return generateNumberField(option, defaultValue);
                    case 'color':
                        return generateColorField(option, defaultValue);
                    case 'select':
                        return generateSelectField(option, defaultValue);
                    case 'boolean':
                        return generateBooleanField(option, defaultValue);
                    default:
                        return generateTextField(option, defaultValue);
                }
            }).join('');
        }

        function generateFontField(option, defaultValue) {
            const fontOptions = availableFonts.map(font =>
                `<option value="${font.family}" ${font.family === defaultValue ? 'selected' : ''}>${font.family}</option>`
            ).join('');

            return `
                <div class="field-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">${option.label}</label>
                    <select id="${option.key}" onchange="updatePreview()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        ${fontOptions || '<option value="Inter">Inter</option>'}
                    </select>
                </div>
            `;
        }

        function generateNumberField(option, defaultValue) {
            const min = option.min || 0;
            const max = option.max || 100;
            const value = defaultValue || min;

            return `
                <div class="field-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">${option.label}</label>
                    <div class="flex items-center space-x-3">
                        <input type="range" id="${option.key}" min="${min}" max="${max}" value="${value}"
                               onchange="updatePreview(); updateRangeValue('${option.key}')" class="flex-1">
                        <span id="${option.key}-value" class="text-sm text-gray-600 w-16">${value}</span>
                    </div>
                </div>
            `;
        }

        function generateColorField(option, defaultValue) {
            const hexValue = defaultValue || 'FFFFFF';
            const colorValue = '#' + hexValue;

            return `
                <div class="field-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">${option.label}</label>
                    <div class="flex items-center space-x-3">
                        <input type="color" id="${option.key}" value="${colorValue}" onchange="syncColorField('${option.key}'); updatePreview()" class="color-input">
                        <input type="text" id="${option.key}Hex" value="${hexValue}" onchange="syncHexField('${option.key}'); updatePreview()"
                               class="px-2 py-1 border border-gray-300 rounded text-sm w-20" maxlength="6" placeholder="FFFFFF">
                    </div>
                </div>
            `;
        }

        function generateSelectField(option, defaultValue) {
            const options = option.options || [];
            const optionsHTML = options.map(opt =>
                `<option value="${opt}" ${opt === defaultValue ? 'selected' : ''}>${opt.charAt(0).toUpperCase() + opt.slice(1)}</option>`
            ).join('');

            return `
                <div class="field-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">${option.label}</label>
                    <select id="${option.key}" onchange="updatePreview()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        ${optionsHTML}
                    </select>
                </div>
            `;
        }

        function generateBooleanField(option, defaultValue) {
            const checked = defaultValue ? 'checked' : '';

            return `
                <div class="field-group">
                    <label class="flex items-center">
                        <input type="checkbox" id="${option.key}" onchange="updatePreview()" ${checked} class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm font-medium text-gray-700">${option.label}</span>
                    </label>
                </div>
            `;
        }

        function generateTextField(option, defaultValue) {
            return `
                <div class="field-group">
                    <label class="block text-sm font-medium text-gray-700 mb-2">${option.label}</label>
                    <input type="text" id="${option.key}" value="${defaultValue || ''}" onchange="updatePreview()"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            `;
        }

        function applyPresetDefaults(defaults) {
            if (!defaults) return;

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
        }

        function initializeFormElements() {
            // Initialize all range value displays
            document.querySelectorAll('input[type="range"]').forEach(range => {
                updateRangeValue(range.id);
            });

            // Initialize color field synchronization
            document.querySelectorAll('input[type="color"]').forEach(colorInput => {
                const key = colorInput.id;
                const hexInput = document.getElementById(key + 'Hex');

                if (hexInput) {
                    colorInput.addEventListener('input', function() {
                        hexInput.value = this.value.substring(1).toUpperCase();
                        updatePreview();
                    });

                    hexInput.addEventListener('input', function() {
                        const hex = this.value.replace('#', '').substring(0, 6);
                        if (hex.length === 6 && /^[0-9A-Fa-f]{6}$/.test(hex)) {
                            colorInput.value = '#' + hex;
                            updatePreview();
                        }
                    });
                }
            });
        }

        function updateRangeValue(inputId) {
            const input = document.getElementById(inputId);
            const display = document.getElementById(inputId + '-value');

            if (input && display) {
                let unit = '';
                if (inputId.toLowerCase().includes('size') || inputId.toLowerCase().includes('width') || inputId.toLowerCase().includes('offset')) {
                    unit = 'px';
                } else if (inputId.toLowerCase().includes('opacity')) {
                    unit = '%';
                }
                display.textContent = input.value + unit;
            }
        }

        function syncColorField(key) {
            const colorInput = document.getElementById(key);
            const hexInput = document.getElementById(key + 'Hex');

            if (colorInput && hexInput) {
                hexInput.value = colorInput.value.substring(1).toUpperCase();
            }
        }

        function syncHexField(key) {
            const colorInput = document.getElementById(key);
            const hexInput = document.getElementById(key + 'Hex');

            if (colorInput && hexInput) {
                const hex = hexInput.value.replace('#', '').substring(0, 6);
                if (hex.length === 6 && /^[0-9A-Fa-f]{6}$/.test(hex)) {
                    colorInput.value = '#' + hex;
                }
            }
        }

        function toggleSection(sectionId) {
            const section = document.getElementById(sectionId).parentElement;
            const arrow = section.querySelector('.section-arrow');

            section.classList.toggle('collapsed');
            arrow.style.transform = section.classList.contains('collapsed') ? 'rotate(-90deg)' : 'rotate(0deg)';
        }

        function updatePreview() {
            console.log('🎨 Preview updated with current settings');
        }

        function showNoPresetMessage() {
            const container = document.getElementById('dynamic-config-container');
            container.innerHTML = `
                <div id="no-preset-message" class="bg-white rounded-lg shadow p-8 text-center">
                    <div class="text-red-400 mb-4">
                        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Configuration Error</h3>
                    <p class="text-gray-600 mb-4">Unable to load preset configuration. Please try selecting a different preset.</p>
                    <button onclick="refreshPresets()" class="text-blue-600 hover:text-blue-800 font-medium">Refresh Presets</button>
                </div>
            `;
        }

        function getCurrentConfiguration() {
            if (!currentPresetDetails) return {};

            const config = { ...currentPresetDetails.defaults };

            // Override with current form values
            if (currentPresetDetails.customizable) {
                currentPresetDetails.customizable.forEach(option => {
                    const element = document.getElementById(option.key);
                    if (element) {
                        if (element.type === 'checkbox') {
                            config[option.key] = element.checked;
                        } else if (element.type === 'color') {
                            const hexInput = document.getElementById(option.key + 'Hex');
                            config[option.key] = hexInput ? hexInput.value : element.value.substring(1);
                        } else {
                            config[option.key] = element.type === 'number' || element.type === 'range' ?
                                parseFloat(element.value) : element.value;
                        }
                    }
                });
            }

            return config;
        }

        async function generatePreview() {
            if (!transcriptionData) {
                showNotification('error', 'No Data', 'No transcription data available for preview');
                return;
            }

            if (!currentPreset) {
                showNotification('warning', 'No Preset', 'Please select a preset first');
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

                if (!response.ok) {
                    const contentType = response.headers.get('Content-Type');
                    let errorMessage = 'Preview generation failed';

                    if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.json();
                        errorMessage = errorData.error || errorMessage;
                    } else {
                        const errorText = await response.text();
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

                if (contentType && contentType.startsWith('image/')) {
                    // Success - display the preview image
                    const imageBlob = await response.blob();

                    // Convert blob to Data URL
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        image.src = e.target.result;
                        loading.classList.add('hidden');
                        image.classList.remove('hidden');
                    };

                    reader.onerror = function(e) {
                        console.error('❌ Failed to convert image to Data URL:', e);
                        showNotification('error', 'Image Processing Failed', 'Could not process preview image');
                        showDemoPreview();
                    };

                    reader.readAsDataURL(imageBlob);
                    showNotification('success', 'Preview Generated', 'Real caption preview ready');
                } else {
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
            const fontSize = Math.round((config.fontSize || 80) * 0.8);
            const fontWeight = config.fontWeight || 700;
            const fontFamily = config.fontFamily || 'Inter';
            ctx.font = `${fontWeight} ${fontSize}px ${fontFamily}`;

            // Text with outline
            const text = 'Sample Caption Text';
            const x = canvas.width / 2;
            let y = canvas.height / 2;

            // Apply position offset
            if (config.positionOffset) {
                y += config.positionOffset * 0.5;
            }

            // Outline
            ctx.strokeStyle = '#' + (config.outlineColor || '000000');
            ctx.lineWidth = config.outlineWidth || 4;
            ctx.strokeText(text, x, y);

            // Fill
            ctx.fillStyle = '#' + (config.textColor || 'FFFFFF');
            ctx.fillText(text, x, y);

            // Active word highlight
            const activeFontSize = Math.round((config.activeWordFontSize || 85) * 0.8);
            ctx.font = `${fontWeight} ${activeFontSize}px ${fontFamily}`;
            ctx.strokeStyle = '#' + (config.outlineColor || '000000');
            ctx.lineWidth = config.outlineWidth || 4;
            ctx.strokeText('Active', x - 60, y + 60);
            ctx.fillStyle = '#' + (config.activeWordColor || 'FFFF00');
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

        async function generateFinalVideo() {
            if (!transcriptionData) {
                showNotification('error', 'No Data', 'No transcription data available');
                return;
            }

            if (!currentPreset) {
                showNotification('error', 'No Preset', 'Please select a preset first');
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

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            // P for preview
            if (event.key.toLowerCase() === 'p' && !event.ctrlKey && !event.metaKey) {
                if (!document.getElementById('preview-btn').disabled) {
                    event.preventDefault();
                    generatePreview();
                }
            }

            // Enter for generate
            if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
                if (!document.getElementById('generate-btn').disabled) {
                    event.preventDefault();
                    generateFinalVideo();
                }
            }
        });

    </script>
</body>
</html>
