// modules/transcription-ui.js
// Gère le processus de transcription et l'interface utilisateur associée

export class TranscriptionUI {
  constructor(apiClient) {
    this.apiClient = apiClient;
    this.transcriptionData = null;
    this.isProcessing = false;

    // DOM elements
    this.transcriptionSection = null;
    this.processingSection = null;
    this.uploadSection = null;
    this.actionButtons = null;
  }

  async init() {
    console.log("📝 TranscriptionUI - Initializing...");

    // Get DOM elements
    this.transcriptionSection = document.getElementById(
      "transcription-section",
    );
    this.processingSection = document.getElementById("processing-section");
    this.uploadSection = document.getElementById("upload-section");
    this.actionButtons = document.getElementById("action-buttons");

    this.setupEventListeners();
    console.log("✅ TranscriptionUI - Ready");
  }

  setupEventListeners() {
    // Listen for file upload events
    document.addEventListener("fileUploaded", (event) => {
      console.log(
        "📝 TranscriptionUI - File uploaded, ready for transcription",
      );
    });

    document.addEventListener("fileCleared", () => {
      this.hideTranscriptionSection();
      this.transcriptionData = null;
    });

    // Fix the transcribe button onclick reference
    const transcribeBtn = document.getElementById("transcribe-btn");
    if (transcribeBtn) {
      transcribeBtn.onclick = () => this.startTranscription();
    }
  }

  async startTranscription() {
    if (this.isProcessing) {
      console.warn("📝 TranscriptionUI - Already processing transcription");
      return;
    }

    // On transcriptions page, we don't need a current file since it's already uploaded
    // The file should be in the session from the upload step
    const fileUpload = window.app?.fileUpload;
    const currentFile = fileUpload?.getCurrentFile();

    // If no current file and we're on transcriptions page, proceed anyway
    // The backend will use the file from session
    const onTranscriptionsPage = window.location.pathname === '/transcriptions';
    
    if (!currentFile && !onTranscriptionsPage) {
      window.app.showNotification(
        "error",
        "No File",
        "Please select a video file first",
      );
      return;
    }

    try {
      this.isProcessing = true;
      console.log(
        "📝 TranscriptionUI - Starting transcription for:",
        currentFile?.name || "uploaded file",
      );

      // Show processing UI
      if (onTranscriptionsPage) {
        this.showTranscriptionProcessingState();
      } else {
        this.showProcessingUI();
      }

      // Prepare the request
      let requestData;
      
      if (currentFile) {
        // If we have a current file (home page), send it
        const formData = new FormData();
        formData.append("file", currentFile);
        formData.append("service", "openai-whisper"); // Default service
        requestData = formData;
      } else {
        // If no current file (transcriptions page), send JSON
        requestData = {
          service: "openai-whisper" // Default service
        };
      }

      // Start transcription via API proxy
      const response = await this.apiClient.post(
        "/api/transcription/start",
        requestData,
      );

      if (response.success) {
        this.transcriptionData = response.data;
        console.log("✅ TranscriptionUI - Transcription completed");

        // Dispatch event for other modules
        document.dispatchEvent(
          new CustomEvent("transcriptionCompleted", {
            detail: {
              data: this.transcriptionData,
            },
          }),
        );

        window.app.showNotification(
          "success",
          "Transcription Complete",
          "Your video has been transcribed successfully",
        );

        // Show transcription section and hide processing
        if (onTranscriptionsPage) {
          this.showTranscriptionCompleteState();
          this.showTranscriptionSection();
        } else {
          this.showTranscriptionSection();
          this.hideProcessingUI();
        }
      } else {
        throw new Error(response.error || "Transcription failed");
      }
    } catch (error) {
      console.error("📝 TranscriptionUI - Transcription failed:", error);
      window.app.showNotification(
        "error",
        "Transcription Failed",
        error.message,
      );

      // Reset UI
      if (onTranscriptionsPage) {
        this.showTranscriptionGenerateState();
      } else {
        this.hideProcessingUI();
        this.showUploadUI();
      }
    } finally {
      this.isProcessing = false;
    }
  }

  showProcessingUI() {
    if (this.uploadSection) this.uploadSection.classList.add("hidden");
    if (this.actionButtons) this.actionButtons.classList.add("hidden");
    if (this.processingSection) {
      this.processingSection.classList.remove("hidden");

      // Update processing message
      const messageElement = document.getElementById("processing-message");
      if (messageElement) {
        messageElement.textContent =
          "Extracting audio and generating transcription...";
      }
    }
  }

  hideProcessingUI() {
    if (this.processingSection) this.processingSection.classList.add("hidden");
  }

  showUploadUI() {
    if (this.uploadSection) this.uploadSection.classList.remove("hidden");
    if (this.actionButtons) this.actionButtons.classList.remove("hidden");
  }

  showTranscriptionSection() {
    if (this.transcriptionSection) {
      this.transcriptionSection.classList.remove("hidden");

      // Initialize transcription editor with data
      if (window.app.transcriptionEditorUI) {
        window.app.transcriptionEditorUI.loadTranscription(
          this.transcriptionData,
        );
      }
    }
  }

  hideTranscriptionSection() {
    if (this.transcriptionSection) {
      this.transcriptionSection.classList.add("hidden");
    }
  }

  // Public API methods
  getTranscriptionData() {
    return this.transcriptionData;
  }

  hasTranscription() {
    return this.transcriptionData !== null;
  }

  clearTranscription() {
    this.transcriptionData = null;
    this.hideTranscriptionSection();

    // Clear transcription data from server session
    this.apiClient.delete("/api/transcription/clear").catch((error) => {
      console.warn(
        "📝 TranscriptionUI - Failed to clear transcription from server:",
        error,
      );
    });

    console.log("📝 TranscriptionUI - Transcription data cleared");
  }

  async retryTranscription() {
    if (this.isProcessing) {
      return;
    }

    console.log("📝 TranscriptionUI - Retrying transcription...");
    await this.startTranscription();
  }

  // New methods for transcriptions page
  async loadVideoInfo() {
    console.log("📝 TranscriptionUI - Loading video info from session...");
    
    try {
      // This would typically be retrieved from the session via an API call
      // For now, we'll check if there's upload data in session via the file upload module
      const fileUpload = window.app?.fileUpload;
      if (fileUpload && fileUpload.getCurrentFile) {
        const currentFile = fileUpload.getCurrentFile();
        if (currentFile) {
          this.updateVideoInfoDisplay({
            name: currentFile.name,
            size: this.formatFileSize(currentFile.size),
            duration: currentFile.duration || 'Unknown'
          });
          return;
        }
      }
      
      // If no current file, try to get from session storage (temporary fallback)
      const sessionFile = sessionStorage.getItem('uploaded_file_info');
      if (sessionFile) {
        const fileInfo = JSON.parse(sessionFile);
        this.updateVideoInfoDisplay(fileInfo);
        return;
      }
      
      // If no video info found, redirect to upload page
      console.warn("📝 TranscriptionUI - No video found, redirecting to upload page");
      window.location.href = '/';
      
    } catch (error) {
      console.error("📝 TranscriptionUI - Error loading video info:", error);
      window.app?.showNotification(
        "error",
        "Error Loading Video",
        "Failed to load video information"
      );
    }
  }

  async checkExistingTranscription() {
    console.log("📝 TranscriptionUI - Checking for existing transcription...");
    
    try {
      const response = await this.apiClient.get('/api/transcription/current');
      
      if (response.success && response.data.transcription) {
        console.log("📝 TranscriptionUI - Found existing transcription");
        this.transcriptionData = response.data.transcription;
        
        // Show transcription complete state
        this.showTranscriptionCompleteState();
        this.showTranscriptionSection();
        
        // Load into editor
        if (window.app?.transcriptionEditorUI) {
          window.app.transcriptionEditorUI.loadTranscription(this.transcriptionData);
        }
        
      } else {
        console.log("📝 TranscriptionUI - No existing transcription found");
        this.showTranscriptionGenerateState();
      }
    } catch (error) {
      console.error("📝 TranscriptionUI - Error checking existing transcription:", error);
      this.showTranscriptionGenerateState();
    }
  }

  updateVideoInfoDisplay(videoInfo) {
    const fileNameElement = document.getElementById('current-file-name');
    const fileSizeElement = document.getElementById('current-file-size');
    const fileDurationElement = document.getElementById('current-file-duration');
    
    if (fileNameElement) fileNameElement.textContent = videoInfo.name;
    if (fileSizeElement) fileSizeElement.textContent = videoInfo.size;
    if (fileDurationElement) fileDurationElement.textContent = videoInfo.duration;
  }

  showTranscriptionGenerateState() {
    const generateSection = document.getElementById('transcription-generate-section');
    const processingSection = document.getElementById('transcription-processing-section');
    const completeSection = document.getElementById('transcription-complete-section');
    
    if (generateSection) generateSection.classList.remove('hidden');
    if (processingSection) processingSection.classList.add('hidden');
    if (completeSection) completeSection.classList.add('hidden');
  }

  showTranscriptionProcessingState() {
    const generateSection = document.getElementById('transcription-generate-section');
    const processingSection = document.getElementById('transcription-processing-section');
    const completeSection = document.getElementById('transcription-complete-section');
    
    if (generateSection) generateSection.classList.add('hidden');
    if (processingSection) processingSection.classList.remove('hidden');
    if (completeSection) completeSection.classList.add('hidden');
  }

  showTranscriptionCompleteState() {
    const generateSection = document.getElementById('transcription-generate-section');
    const processingSection = document.getElementById('transcription-processing-section');
    const completeSection = document.getElementById('transcription-complete-section');
    
    if (generateSection) generateSection.classList.add('hidden');
    if (processingSection) processingSection.classList.add('hidden');
    if (completeSection) completeSection.classList.remove('hidden');
  }

  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
}
