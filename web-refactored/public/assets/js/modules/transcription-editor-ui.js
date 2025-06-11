// modules/transcription-editor-ui.js
// Gère l'édition des transcriptions avec interface interactive

export class TranscriptionEditorUI {
    constructor(apiClient) {
        this.apiClient = apiClient;
        this.transcriptionData = null;
        this.isEditing = false;
        this.hasUnsavedChanges = false;

        // DOM elements
        this.editorContainer = null;
        this.saveButton = null;
        
        // Templates
        this.segmentTemplate = null;
        this.wordTemplate = null;
    }

    async init() {
        console.log('✏️ TranscriptionEditorUI - Initializing...');
        
        // Get DOM elements
        this.editorContainer = document.getElementById('transcription-editor-container');
        this.segmentTemplate = document.getElementById('transcription-segment-template');
        this.wordTemplate = document.getElementById('transcription-word-template');

        if (!this.editorContainer) {
            console.warn('TranscriptionEditorUI - Editor container not found');
            return;
        }

        this.setupEventListeners();
        console.log('✅ TranscriptionEditorUI - Ready');
    }

    setupEventListeners() {
        // Listen for transcription completion
        document.addEventListener('transcriptionCompleted', (event) => {
            this.loadTranscription(event.detail.data);
        });

        // Listen for file clearing
        document.addEventListener('fileCleared', () => {
            this.clearEditor();
        });

        // Setup save button if it exists
        const saveButton = document.querySelector('button[onclick*="saveTranscription"]');
        if (saveButton) {
            saveButton.onclick = () => this.saveTranscription();
        }
    }

    loadTranscription(transcriptionData) {
        console.log('✏️ TranscriptionEditorUI - Loading transcription data');
        
        this.transcriptionData = transcriptionData;
        this.renderEditor();
        this.isEditing = true;
        this.hasUnsavedChanges = false;
    }

    renderEditor() {
        if (!this.editorContainer || !this.transcriptionData) return;

        const captions = this.transcriptionData.transcription?.captions || [];
        
        if (captions.length === 0) {
            this.editorContainer.innerHTML = `
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No Captions Found</h3>
                    <p class="mt-1 text-sm text-gray-500">The transcription did not generate any captions to edit.</p>
                </div>
            `;
            return;
        }

        // Group captions into segments for better editing
        const segments = this.groupCaptionsIntoSegments(captions);
        
        let html = `
            <div class="space-y-4">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Transcription Segments</h3>
                        <p class="text-sm text-gray-500">${segments.length} segments • ${captions.length} words total</p>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="app.transcriptionEditorUI.addSegment()" 
                                class="text-sm px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                            Add Segment
                        </button>
                        <button onclick="app.transcriptionEditorUI.validateTimestamps()" 
                                class="text-sm px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">
                            Validate Times
                        </button>
                    </div>
                </div>
                
                <div id="segments-container" class="space-y-4">
                    ${segments.map((segment, index) => this.renderSegment(segment, index)).join('')}
                </div>
            </div>
        `;

        this.editorContainer.innerHTML = html;
        this.setupSegmentEventListeners();
    }

    groupCaptionsIntoSegments(captions) {
        // Group captions into logical segments (sentences or pauses)
        const segments = [];
        let currentSegment = [];
        
        for (let i = 0; i < captions.length; i++) {
            const caption = captions[i];
            currentSegment.push(caption);
            
            // End segment on punctuation or long pause
            const hasEndPunctuation = /[.!?]$/.test(caption.text?.trim() || '');
            const nextCaption = captions[i + 1];
            const longPause = nextCaption && (nextCaption.startMs - caption.endMs) > 1000; // 1 second pause
            
            if (hasEndPunctuation || longPause || currentSegment.length >= 15) {
                segments.push({
                    words: [...currentSegment],
                    startMs: currentSegment[0].startMs,
                    endMs: currentSegment[currentSegment.length - 1].endMs
                });
                currentSegment = [];
            }
        }
        
        // Add remaining words as final segment
        if (currentSegment.length > 0) {
            segments.push({
                words: [...currentSegment],
                startMs: currentSegment[0].startMs,
                endMs: currentSegment[currentSegment.length - 1].endMs
            });
        }
        
        return segments;
    }

    renderSegment(segment, index) {
        const startTime = this.formatTimestamp(segment.startMs);
        const endTime = this.formatTimestamp(segment.endMs);
        const text = segment.words.map(word => word.text || '').join('');

        return `
            <div class="bg-gray-50 rounded-lg p-4 segment" data-segment-index="${index}">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex space-x-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Start</label>
                            <input type="text" 
                                   class="segment-start-time w-24 text-xs px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   value="${startTime}"
                                   onchange="app.transcriptionEditorUI.updateSegmentTime(${index}, 'start', this.value)">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">End</label>
                            <input type="text" 
                                   class="segment-end-time w-24 text-xs px-2 py-1 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                   value="${endTime}"
                                   onchange="app.transcriptionEditorUI.updateSegmentTime(${index}, 'end', this.value)">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Duration</label>
                            <span class="text-xs text-gray-500 px-2 py-1 bg-gray-200 rounded">${this.formatDuration(segment.endMs - segment.startMs)}</span>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="app.transcriptionEditorUI.splitSegment(${index})" 
                                class="text-xs px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                                title="Split this segment">
                            Split
                        </button>
                        <button onclick="app.transcriptionEditorUI.mergeWithPrevious(${index})" 
                                class="text-xs px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600"
                                title="Merge with previous segment"
                                ${index === 0 ? 'disabled' : ''}>
                            Merge
                        </button>
                        <button onclick="app.transcriptionEditorUI.deleteSegment(${index})" 
                                class="text-xs px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600"
                                title="Delete this segment">
                            Delete
                        </button>
                    </div>
                </div>
                
                <div class="segment-text-editor border border-gray-300 rounded p-3 min-h-16 focus-within:ring-2 focus-within:ring-blue-500 bg-white" 
                     contenteditable="true"
                     data-segment="${index}"
                     onblur="app.transcriptionEditorUI.updateSegmentText(${index}, this.textContent)">
                    ${this.escapeHtml(text)}
                </div>
                
                <div class="mt-2 text-xs text-gray-500">
                    ${segment.words.length} words • Click text above to edit
                </div>
            </div>
        `;
    }

    setupSegmentEventListeners() {
        // Add event listeners for text editing
        const textEditors = document.querySelectorAll('.segment-text-editor');
        textEditors.forEach(editor => {
            editor.addEventListener('input', () => {
                this.hasUnsavedChanges = true;
                this.updateSaveButtonState();
            });
        });
    }

    updateSegmentTime(segmentIndex, type, value) {
        if (!this.transcriptionData?.transcription?.captions) return;

        try {
            const milliseconds = this.parseTimestamp(value);
            const segments = this.groupCaptionsIntoSegments(this.transcriptionData.transcription.captions);
            
            if (segments[segmentIndex]) {
                if (type === 'start') {
                    segments[segmentIndex].startMs = milliseconds;
                    // Update all words in this segment
                    segments[segmentIndex].words.forEach((word, i) => {
                        if (i === 0) word.startMs = milliseconds;
                    });
                } else if (type === 'end') {
                    segments[segmentIndex].endMs = milliseconds;
                    // Update last word in segment
                    const lastWord = segments[segmentIndex].words[segments[segmentIndex].words.length - 1];
                    if (lastWord) lastWord.endMs = milliseconds;
                }
                
                this.hasUnsavedChanges = true;
                this.updateSaveButtonState();
                console.log(`✏️ Updated segment ${segmentIndex} ${type} time to ${value}`);
            }
        } catch (error) {
            console.error('✏️ Invalid timestamp format:', value);
            window.app.showNotification('error', 'Invalid Time', 'Please use format: MM:SS.mmm');
        }
    }

    updateSegmentText(segmentIndex, newText) {
        console.log(`✏️ Updating segment ${segmentIndex} text:`, newText);
        
        // For now, just mark as changed
        // In a full implementation, we would split the text back into words
        // and update the individual word timings proportionally
        
        this.hasUnsavedChanges = true;
        this.updateSaveButtonState();
    }

    splitSegment(segmentIndex) {
        console.log(`✏️ Splitting segment ${segmentIndex}`);
        window.app.showNotification('info', 'Feature Coming Soon', 'Segment splitting will be implemented soon');
    }

    mergeWithPrevious(segmentIndex) {
        if (segmentIndex === 0) return;
        
        console.log(`✏️ Merging segment ${segmentIndex} with previous`);
        window.app.showNotification('info', 'Feature Coming Soon', 'Segment merging will be implemented soon');
    }

    deleteSegment(segmentIndex) {
        if (!confirm('Are you sure you want to delete this segment?')) return;
        
        console.log(`✏️ Deleting segment ${segmentIndex}`);
        window.app.showNotification('info', 'Feature Coming Soon', 'Segment deletion will be implemented soon');
    }

    addSegment() {
        console.log('✏️ Adding new segment');
        window.app.showNotification('info', 'Feature Coming Soon', 'Adding segments will be implemented soon');
    }

    validateTimestamps() {
        console.log('✏️ Validating timestamps');
        
        let hasErrors = false;
        const segments = this.groupCaptionsIntoSegments(this.transcriptionData?.transcription?.captions || []);
        
        for (let i = 0; i < segments.length; i++) {
            const segment = segments[i];
            
            // Check if end time is after start time
            if (segment.endMs <= segment.startMs) {
                hasErrors = true;
                console.error(`Segment ${i}: End time must be after start time`);
            }
            
            // Check if segments overlap
            if (i > 0 && segment.startMs < segments[i-1].endMs) {
                hasErrors = true;
                console.error(`Segment ${i}: Overlaps with previous segment`);
            }
        }
        
        if (hasErrors) {
            window.app.showNotification('error', 'Validation Failed', 'Found timing errors in segments');
        } else {
            window.app.showNotification('success', 'Validation Passed', 'All timestamps are valid');
        }
    }

    async saveTranscription() {
        if (!this.hasUnsavedChanges) {
            window.app.showNotification('info', 'No Changes', 'No changes to save');
            return;
        }

        try {
            console.log('✏️ Saving transcription changes...');
            
            // For now, just save to session storage as placeholder
            // In full implementation, this would send to the backend
            
            const response = await this.apiClient.post('/api/transcription/save', {
                transcriptionData: this.transcriptionData
            });

            if (response.success) {
                this.hasUnsavedChanges = false;
                this.updateSaveButtonState();
                window.app.showNotification('success', 'Saved', 'Transcription changes saved successfully');
            } else {
                throw new Error(response.error || 'Save failed');
            }
            
        } catch (error) {
            console.error('✏️ Save failed:', error);
            window.app.showNotification('error', 'Save Failed', error.message);
        }
    }

    updateSaveButtonState() {
        const saveButton = document.querySelector('button[onclick*="saveTranscription"]');
        if (saveButton) {
            if (this.hasUnsavedChanges) {
                saveButton.classList.remove('bg-gray-400');
                saveButton.classList.add('bg-blue-600', 'hover:bg-blue-700');
                saveButton.disabled = false;
                saveButton.innerHTML = saveButton.innerHTML.replace('Save Changes', 'Save Changes *');
            } else {
                saveButton.classList.add('bg-gray-400');
                saveButton.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                saveButton.disabled = true;
                saveButton.innerHTML = saveButton.innerHTML.replace(' *', '');
            }
        }
    }

    clearEditor() {
        if (this.editorContainer) {
            this.editorContainer.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <p>Upload a video and generate transcription to start editing</p>
                </div>
            `;
        }
        
        this.transcriptionData = null;
        this.isEditing = false;
        this.hasUnsavedChanges = false;
    }

    // Utility functions
    formatTimestamp(milliseconds) {
        const totalSeconds = milliseconds / 1000;
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = Math.floor(totalSeconds % 60);
        const ms = Math.floor((milliseconds % 1000));
        
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}.${ms.toString().padStart(3, '0')}`;
    }

    parseTimestamp(timeString) {
        const match = timeString.match(/^(\d{1,2}):(\d{2})\.(\d{3})$/);
        if (!match) {
            throw new Error('Invalid timestamp format');
        }
        
        const [, minutes, seconds, milliseconds] = match;
        return (parseInt(minutes) * 60 + parseInt(seconds)) * 1000 + parseInt(milliseconds);
    }

    formatDuration(milliseconds) {
        const seconds = (milliseconds / 1000).toFixed(1);
        return `${seconds}s`;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Public API
    getTranscriptionData() {
        return this.transcriptionData;
    }

    hasUnsavedChanges() {
        return this.hasUnsavedChanges;
    }

    isCurrentlyEditing() {
        return this.isEditing;
    }
}