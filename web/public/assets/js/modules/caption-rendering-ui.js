// modules/caption-rendering-ui.js
// Gère l'interface de rendu des sous-titres (FFmpeg et Remotion)

export class CaptionRenderingUI {
    constructor(apiClient) {
        this.apiClient = apiClient;
        this.selectedService = null;
        this.styleOptions = {};
        this.isGenerating = false;

        // DOM elements
        this.renderingSection = null;
        this.stylingContainer = null;
        this.generateButton = null;
    }

    async init() {
        console.log('🎬 CaptionRenderingUI - Initializing...');
        
        // Get DOM elements
        this.renderingSection = document.getElementById('caption-rendering-section');
        this.stylingContainer = document.getElementById('caption-styling-container');
        this.generateButton = document.getElementById('generate-video-btn');

        if (!this.renderingSection) {
            console.warn('CaptionRenderingUI - Rendering section not found');
            return;
        }

        this.setupEventListeners();
        console.log('✅ CaptionRenderingUI - Ready');
    }

    setupEventListeners() {
        // Listen for transcription completion
        document.addEventListener('transcriptionCompleted', () => {
            // Don't auto-show, wait for user to finish editing
        });

        // Setup service selection radio buttons
        document.querySelectorAll('input[name="rendering-service"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.selectService(e.target.value);
            });
        });

        // Setup generate button if it exists
        if (this.generateButton) {
            this.generateButton.onclick = () => this.generateVideo();
        }
    }

    show() {
        if (!window.app.transcriptionUI.hasTranscription()) {
            window.app.showNotification('warning', 'No Transcription', 'Please generate a transcription first');
            return;
        }

        if (this.renderingSection) {
            this.renderingSection.classList.remove('hidden');
            
            // Scroll to the section
            this.renderingSection.scrollIntoView({ behavior: 'smooth' });
        }

        console.log('🎬 CaptionRenderingUI - Rendering section shown');
    }

    hide() {
        if (this.renderingSection) {
            this.renderingSection.classList.add('hidden');
        }
    }

    selectService(serviceName) {
        this.selectedService = serviceName;
        console.log(`🎬 CaptionRenderingUI - Selected service: ${serviceName}`);

        // Update radio button selection visually
        this.updateServiceSelection(serviceName);

        // Load styling options for the selected service
        this.loadStylingOptions(serviceName);

        // Enable generate button
        if (this.generateButton) {
            this.generateButton.disabled = false;
            this.generateButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
            this.generateButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }
    }

    updateServiceSelection(serviceName) {
        // Update border styling for service cards
        document.querySelectorAll('[onclick*="selectService"]').forEach(card => {
            card.classList.remove('border-blue-500', 'bg-blue-50');
            card.classList.add('border-gray-200');
        });

        const selectedCard = document.querySelector(`[onclick*="selectService('${serviceName}')"]`);
        if (selectedCard) {
            selectedCard.classList.remove('border-gray-200');
            selectedCard.classList.add('border-blue-500', 'bg-blue-50');
        }

        // Update radio button
        const radio = document.querySelector(`input[name="rendering-service"][value="${serviceName}"]`);
        if (radio) {
            radio.checked = true;
        }
    }

    async loadStylingOptions(serviceName) {
        if (!this.stylingContainer) return;

        try {
            console.log(`🎬 CaptionRenderingUI - Loading styling options for ${serviceName}`);
            
            this.stylingContainer.innerHTML = `
                <div class="flex justify-center py-4">
                    <div class="animate-spin h-6 w-6 border-2 border-blue-600 rounded-full border-t-transparent"></div>
                </div>
            `;
            this.stylingContainer.classList.remove('hidden');

            if (serviceName === 'ffmpeg') {
                await this.loadFFmpegOptions();
            } else if (serviceName === 'remotion') {
                await this.loadRemotionOptions();
            }

        } catch (error) {
            console.error(`🎬 CaptionRenderingUI - Failed to load ${serviceName} options:`, error);
            this.stylingContainer.innerHTML = `
                <div class="text-center py-4 text-red-600">
                    <p>Failed to load styling options</p>
                    <button onclick="app.captionRenderingUI.loadStylingOptions('${serviceName}')" 
                            class="mt-2 text-sm px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">
                        Retry
                    </button>
                </div>
            `;
        }
    }

    async loadFFmpegOptions() {
        try {
            // Get available presets and fonts from ffmpeg-captions service
            const [presetsResponse, fontsResponse] = await Promise.all([
                this.apiClient.callService('ffmpeg-captions', 'api/captions/presets'),
                this.apiClient.callService('ffmpeg-captions', 'api/captions/fonts')
            ]);

            if (!presetsResponse.success || !fontsResponse.success) {
                throw new Error('Failed to load FFmpeg options');
            }

            const presets = presetsResponse.presets || [];
            const fonts = fontsResponse.fonts || [];

            this.renderFFmpegInterface(presets, fonts);

        } catch (error) {
            throw new Error(`FFmpeg service unavailable: ${error.message}`);
        }
    }

    renderFFmpegInterface(presets, fonts) {
        const html = `
            <div class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900">FFmpeg Caption Styling</h3>
                
                <!-- Preset Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Style Preset</label>
                    <select id="ffmpeg-preset" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        ${presets.map(preset => `
                            <option value="${preset.name}">${preset.displayName}</option>
                        `).join('')}
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Choose a predefined style or customize below</p>
                </div>

                <!-- Font Selection -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Font Family</label>
                        <select id="ffmpeg-font" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            ${fonts.slice(0, 20).map(font => `
                                <option value="${font.family}">${font.family}</option>
                            `).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Font Size</label>
                        <input type="range" id="ffmpeg-font-size" min="40" max="150" value="80" 
                               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                        <div class="flex justify-between text-xs text-gray-500">
                            <span>40px</span>
                            <span id="font-size-value">80px</span>
                            <span>150px</span>
                        </div>
                    </div>
                </div>

                <!-- Colors -->
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Text Color</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" id="ffmpeg-text-color" value="#ffffff" 
                                   class="w-10 h-10 border border-gray-300 rounded cursor-pointer">
                            <input type="text" id="ffmpeg-text-color-hex" value="#ffffff" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Active Word Color</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" id="ffmpeg-active-color" value="#ffff00" 
                                   class="w-10 h-10 border border-gray-300 rounded cursor-pointer">
                            <input type="text" id="ffmpeg-active-color-hex" value="#ffff00" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Outline Color</label>
                        <div class="flex items-center space-x-2">
                            <input type="color" id="ffmpeg-outline-color" value="#000000" 
                                   class="w-10 h-10 border border-gray-300 rounded cursor-pointer">
                            <input type="text" id="ffmpeg-outline-color-hex" value="#000000" 
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>
                    </div>
                </div>

                <!-- Position -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                        <select id="ffmpeg-position" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="bottom">Bottom</option>
                            <option value="center">Center</option>
                            <option value="top">Top</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Position Offset (px)</label>
                        <input type="number" id="ffmpeg-position-offset" value="300" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Preview Button -->
                <div class="text-center">
                    <button onclick="app.captionRenderingUI.generatePreview()" 
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Preview Style
                    </button>
                </div>
            </div>
        `;

        this.stylingContainer.innerHTML = html;
        this.setupFFmpegEventListeners();
    }

    setupFFmpegEventListeners() {
        // Font size slider
        const fontSizeSlider = document.getElementById('ffmpeg-font-size');
        const fontSizeValue = document.getElementById('font-size-value');
        
        if (fontSizeSlider && fontSizeValue) {
            fontSizeSlider.addEventListener('input', (e) => {
                fontSizeValue.textContent = `${e.target.value}px`;
            });
        }

        // Color pickers sync with hex inputs
        this.setupColorSync('ffmpeg-text-color', 'ffmpeg-text-color-hex');
        this.setupColorSync('ffmpeg-active-color', 'ffmpeg-active-color-hex');
        this.setupColorSync('ffmpeg-outline-color', 'ffmpeg-outline-color-hex');
    }

    setupColorSync(colorId, hexId) {
        const colorPicker = document.getElementById(colorId);
        const hexInput = document.getElementById(hexId);

        if (colorPicker && hexInput) {
            colorPicker.addEventListener('change', (e) => {
                hexInput.value = e.target.value;
            });

            hexInput.addEventListener('input', (e) => {
                if (/^#[0-9A-F]{6}$/i.test(e.target.value)) {
                    colorPicker.value = e.target.value;
                }
            });
        }
    }

    async loadRemotionOptions() {
        // For Remotion, we'll provide a simplified interface
        // In a full implementation, this would call the remotion-captions service
        
        this.renderRemotionInterface();
    }

    renderRemotionInterface() {
        const html = `
            <div class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900">Remotion Caption Styling</h3>
                
                <!-- Font Configuration -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Font Family</label>
                        <select id="remotion-font" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Inter">Inter</option>
                            <option value="Montserrat">Montserrat</option>
                            <option value="Oswald">Oswald</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Poppins">Poppins</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Font Weight</label>
                        <select id="remotion-font-weight" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="400">Regular (400)</option>
                            <option value="600">Semi Bold (600)</option>
                            <option value="700">Bold (700)</option>
                            <option value="800" selected>Extra Bold (800)</option>
                            <option value="900">Black (900)</option>
                        </select>
                    </div>
                </div>

                <!-- Colors and Effects -->
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Text Color</label>
                        <input type="color" id="remotion-text-color" value="#ffffff" 
                               class="w-full h-10 border border-gray-300 rounded cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Active Word Background</label>
                        <input type="color" id="remotion-active-bg" value="#ff5700" 
                               class="w-full h-10 border border-gray-300 rounded cursor-pointer">
                    </div>
                </div>

                <!-- Position and Size -->
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                        <select id="remotion-position" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="bottom" selected>Bottom</option>
                            <option value="center">Center</option>
                            <option value="top">Top</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Font Size</label>
                        <input type="number" id="remotion-font-size" value="80" min="40" max="150"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Word Padding</label>
                        <input type="number" id="remotion-word-padding" value="8" min="0" max="20"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Platform Presets -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Platform Preset</label>
                    <div class="grid grid-cols-3 gap-3">
                        <button onclick="app.captionRenderingUI.applyRemotionPreset('tiktok')" 
                                class="p-3 border border-gray-300 rounded-lg text-sm hover:border-blue-500 hover:bg-blue-50">
                            <div class="font-medium">TikTok Style</div>
                            <div class="text-xs text-gray-500">Orange highlights</div>
                        </button>
                        <button onclick="app.captionRenderingUI.applyRemotionPreset('youtube')" 
                                class="p-3 border border-gray-300 rounded-lg text-sm hover:border-blue-500 hover:bg-blue-50">
                            <div class="font-medium">YouTube Shorts</div>
                            <div class="text-xs text-gray-500">Gold highlights</div>
                        </button>
                        <button onclick="app.captionRenderingUI.applyRemotionPreset('clean')" 
                                class="p-3 border border-gray-300 rounded-lg text-sm hover:border-blue-500 hover:bg-blue-50">
                            <div class="font-medium">Clean Style</div>
                            <div class="text-xs text-gray-500">Simple text</div>
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.stylingContainer.innerHTML = html;
    }

    applyRemotionPreset(presetName) {
        const presets = {
            tiktok: {
                fontFamily: 'Inter',
                fontWeight: '800',
                textColor: '#ffffff',
                activeBackground: '#FF5700',
                fontSize: 80,
                wordPadding: 12
            },
            youtube: {
                fontFamily: 'Montserrat',
                fontWeight: '700',
                textColor: '#ffffff',
                activeBackground: '#FFD700',
                fontSize: 72,
                wordPadding: 10
            },
            clean: {
                fontFamily: 'Roboto',
                fontWeight: '600',
                textColor: '#ffffff',
                activeBackground: '#ffffff',
                fontSize: 48,
                wordPadding: 6
            }
        };

        const preset = presets[presetName];
        if (!preset) return;

        // Apply preset values to form elements
        const elements = {
            'remotion-font': preset.fontFamily,
            'remotion-font-weight': preset.fontWeight,
            'remotion-text-color': preset.textColor,
            'remotion-active-bg': preset.activeBackground,
            'remotion-font-size': preset.fontSize,
            'remotion-word-padding': preset.wordPadding
        };

        Object.keys(elements).forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.value = elements[id];
            }
        });

        window.app.showNotification('success', 'Preset Applied', `Applied ${presetName} styling preset`);
    }

    async generatePreview() {
        if (!this.selectedService) {
            window.app.showNotification('error', 'No Service Selected', 'Please select a rendering service first');
            return;
        }

        try {
            window.app.showNotification('info', 'Generating Preview', 'Creating preview frame...');
            
            // This would call the preview endpoint
            // For now, just show a placeholder
            setTimeout(() => {
                window.app.showNotification('info', 'Feature Coming Soon', 'Preview generation will be implemented soon');
            }, 1000);
            
        } catch (error) {
            console.error('🎬 Preview generation failed:', error);
            window.app.showNotification('error', 'Preview Failed', error.message);
        }
    }

    async generateVideo() {
        if (!this.selectedService) {
            window.app.showNotification('error', 'No Service Selected', 'Please select a rendering service first');
            return;
        }

        if (this.isGenerating) {
            console.warn('🎬 CaptionRenderingUI - Already generating video');
            return;
        }

        const transcriptionData = window.app.transcriptionUI.getTranscriptionData();
        if (!transcriptionData) {
            window.app.showNotification('error', 'No Transcription', 'No transcription data available');
            return;
        }

        try {
            this.isGenerating = true;
            this.updateGenerateButtonState(true);

            console.log(`🎬 CaptionRenderingUI - Starting video generation with ${this.selectedService}`);
            
            const styleConfig = this.collectStyleConfiguration();
            
            // This would make the actual API call to generate the video
            window.app.showNotification('info', 'Video Generation Started', 'This may take several minutes...');
            
            // Placeholder for actual implementation
            setTimeout(() => {
                window.app.showNotification('info', 'Feature Coming Soon', 'Video generation will be implemented soon');
                this.isGenerating = false;
                this.updateGenerateButtonState(false);
            }, 2000);

        } catch (error) {
            console.error('🎬 Video generation failed:', error);
            window.app.showNotification('error', 'Generation Failed', error.message);
            this.isGenerating = false;
            this.updateGenerateButtonState(false);
        }
    }

    collectStyleConfiguration() {
        const config = {
            service: this.selectedService
        };

        if (this.selectedService === 'ffmpeg') {
            config.ffmpeg = {
                preset: document.getElementById('ffmpeg-preset')?.value || 'custom',
                fontFamily: document.getElementById('ffmpeg-font')?.value || 'Inter',
                fontSize: parseInt(document.getElementById('ffmpeg-font-size')?.value || '80'),
                textColor: document.getElementById('ffmpeg-text-color-hex')?.value || '#ffffff',
                activeWordColor: document.getElementById('ffmpeg-active-color-hex')?.value || '#ffff00',
                outlineColor: document.getElementById('ffmpeg-outline-color-hex')?.value || '#000000',
                position: document.getElementById('ffmpeg-position')?.value || 'bottom',
                positionOffset: parseInt(document.getElementById('ffmpeg-position-offset')?.value || '300')
            };
        } else if (this.selectedService === 'remotion') {
            config.remotion = {
                fontFamily: document.getElementById('remotion-font')?.value || 'Inter',
                fontWeight: document.getElementById('remotion-font-weight')?.value || '800',
                textColor: document.getElementById('remotion-text-color')?.value || '#ffffff',
                activeWordBackgroundColor: document.getElementById('remotion-active-bg')?.value || '#FF5700',
                fontSize: parseInt(document.getElementById('remotion-font-size')?.value || '80'),
                wordPadding: parseInt(document.getElementById('remotion-word-padding')?.value || '8'),
                textPosition: document.getElementById('remotion-position')?.value || 'bottom'
            };
        }

        return config;
    }

    updateGenerateButtonState(isGenerating) {
        if (!this.generateButton) return;

        if (isGenerating) {
            this.generateButton.disabled = true;
            this.generateButton.innerHTML = `
                <svg class="animate-spin w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Generating Video...
            `;
            this.generateButton.classList.add('bg-gray-400', 'cursor-not-allowed');
            this.generateButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        } else {
            this.generateButton.disabled = false;
            this.generateButton.innerHTML = `
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h8m2-10a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Generate Video
            `;
            this.generateButton.classList.remove('bg-gray-400', 'cursor-not-allowed');
            this.generateButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }
    }

    // Public API
    getSelectedService() {
        return this.selectedService;
    }

    isCurrentlyGenerating() {
        return this.isGenerating;
    }

    getStyleConfiguration() {
        return this.collectStyleConfiguration();
    }
}