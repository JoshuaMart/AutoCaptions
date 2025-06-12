/**
 * FFmpeg Configuration JavaScript Module
 * Handles the interactive configuration interface for FFmpeg caption generation
 */

class FFmpegConfig {
  constructor() {
    this.currentPreset = null;
    this.currentPresetDetails = null;
    this.availablePresets = [];
    this.availableFonts = [];
    this.transcriptionData = null;
    this.previewTimeout = null;

    this.init();
  }

  async init() {
    console.log("🎨 FFmpeg Configuration module loaded");

    // Load initial data
    await this.loadTranscriptionData();
    await this.loadPresets();
    await this.loadFonts();

    // Initialize event listeners
    this.initializeEventListeners();
  }

  initializeEventListeners() {
    // Auto-save configuration on changes
    document.addEventListener("change", (e) => {
      if (e.target.closest(".field-group")) {
        this.autoSaveConfiguration();
      }
    });

    // Handle input events for real-time feedback
    document.addEventListener("input", (e) => {
      if (e.target.type === "range") {
        this.updateRangeValue(e.target.id);
      }
    });

    // Handle keyboard shortcuts
    document.addEventListener("keydown", (e) => {
      if (e.ctrlKey || e.metaKey) {
        switch (e.key) {
          case "p":
            e.preventDefault();
            this.generatePreview();
            break;
          case "Enter":
            if (e.shiftKey) {
              e.preventDefault();
              this.generateFinalVideo();
            }
            break;
        }
      }
    });
  }

  async loadTranscriptionData() {
    // In the new architecture, transcription data should come from the server/session
    // For demo purposes, we can use sample data
    if (window.transcriptionData) {
      this.transcriptionData = window.transcriptionData;
      console.log("✅ Transcription data loaded from window");
    } else {
      this.showDemoData();
    }
  }

  showDemoData() {
    this.transcriptionData = {
      success: true,
      transcription: {
        captions: [
          { text: "Hello", startMs: 0, endMs: 500 },
          { text: "world", startMs: 500, endMs: 1000 },
          { text: "this", startMs: 1000, endMs: 1300 },
          { text: "is", startMs: 1300, endMs: 1500 },
          { text: "a", startMs: 1500, endMs: 1600 },
          { text: "demo", startMs: 1600, endMs: 2000 },
        ],
        duration: 2.5,
        language: "en",
      },
    };
    console.log("📝 Using demo transcription data");
  }

  async loadPresets() {
    const loadingEl = document.getElementById("presets-loading");
    const listEl = document.getElementById("presets-list");

    try {
      const response = await fetch("/api/ffmpeg/presets");
      const result = await response.json();

      if (result.success && result.presets) {
        this.availablePresets = result.presets;
        this.renderPresets();
        loadingEl?.classList.add("hidden");
        listEl?.classList.remove("hidden");
        console.log(`✅ Loaded ${result.presets.length} presets`);
      } else {
        throw new Error("Failed to load presets");
      }
    } catch (error) {
      console.error("❌ Failed to load presets:", error);
      this.showNotification(
        "error",
        "Loading Failed",
        "Could not load presets. Using defaults.",
      );
      this.showDefaultPresets();
    }
  }

  renderPresets() {
    const container = document.getElementById("presets-list");
    if (!container) return;

    container.innerHTML = this.availablePresets
      .map(
        (preset) => `
            <div class="preset-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-300 transition-colors duration-200"
                 data-preset="${preset.name}"
                 onclick="ffmpegConfig.selectPreset('${preset.name}')">
                <h4 class="font-medium text-gray-900">${preset.displayName}</h4>
                <p class="text-sm text-gray-600 mt-1">${preset.description}</p>
                <div class="mt-2 text-xs text-gray-500">
                    Click to configure options
                </div>
            </div>
        `,
      )
      .join("");
  }

  showDefaultPresets() {
    this.availablePresets = [
      {
        name: "custom",
        displayName: "Custom",
        description: "Fully customizable caption style",
      },
    ];
    this.renderPresets();
    document.getElementById("presets-loading")?.classList.add("hidden");
    document.getElementById("presets-list")?.classList.remove("hidden");
  }

  async loadFonts() {
    try {
      const response = await fetch("/api/ffmpeg/fonts");
      const result = await response.json();

      if (result.success && result.fonts) {
        this.availableFonts = result.fonts;
        console.log(`✅ Loaded ${result.fonts.length} fonts`);
      } else {
        throw new Error("Failed to load fonts");
      }
    } catch (error) {
      console.error("❌ Failed to load fonts:", error);
      this.showDefaultFonts();
    }
  }

  showDefaultFonts() {
    this.availableFonts = [
      {
        family: "Inter",
        variants: ["400", "600", "700", "800"],
        category: "sans-serif",
      },
      { family: "Arial Black", variants: ["400"], category: "sans-serif" },
      {
        family: "Montserrat",
        variants: ["400", "600", "700", "800"],
        category: "sans-serif",
      },
      {
        family: "Roboto",
        variants: ["400", "500", "700"],
        category: "sans-serif",
      },
    ];
  }

  async selectPreset(presetName) {
    this.currentPreset = presetName;

    // Update visual selection
    document.querySelectorAll(".preset-card").forEach((card) => {
      card.classList.remove("selected");
    });

    const selectedCard = document.querySelector(
      `[data-preset="${presetName}"]`,
    );
    if (selectedCard) {
      selectedCard.classList.add("selected");
    }

    // Show loading state
    this.showConfigLoading();

    try {
      const response = await fetch(`/api/ffmpeg/presets/${presetName}`);
      const result = await response.json();

      if (result.success && result.preset) {
        this.currentPresetDetails = result.preset;
        this.generateConfigurationInterface(result.preset);

        // Enable buttons
        const previewBtn = document.getElementById("preview-btn");
        const generateBtn = document.getElementById("generate-btn");
        if (previewBtn) previewBtn.disabled = false;
        if (generateBtn) generateBtn.disabled = false;

        console.log(
          `✅ Preset '${presetName}' loaded with ${result.preset.customizable?.length || 0} customizable options`,
        );
        this.showNotification(
          "success",
          "Preset Loaded",
          `${result.preset.displayName} configuration loaded`,
        );
      } else {
        throw new Error("Failed to load preset details");
      }
    } catch (error) {
      console.error("❌ Failed to load preset details:", error);
      this.showNotification(
        "error",
        "Loading Failed",
        "Could not load preset configuration",
      );
      this.showNoPresetMessage();
    }
  }

  showConfigLoading() {
    const container = document.getElementById("dynamic-config-container");
    if (!container) return;

    container.innerHTML = `
            <div class="bg-white rounded-lg shadow p-8 text-center">
                <div class="animate-spin mx-auto h-8 w-8 text-blue-600 mb-4">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </div>
                <p class="text-gray-600">Loading preset configuration...</p>
            </div>
        `;
  }

  generateConfigurationInterface(preset) {
    const container = document.getElementById("dynamic-config-container");
    if (!container) return;

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
    const groups = this.groupCustomizableOptions(preset.customizable);
    let sectionsHTML = "";

    for (const [groupName, options] of Object.entries(groups)) {
      if (options.length === 0) continue;

      const sectionId = `${groupName.toLowerCase().replace(/\s+/g, "-")}-section`;
      sectionsHTML += this.generateSectionHTML(
        groupName,
        sectionId,
        options,
        preset.defaults,
      );
    }

    container.innerHTML = sectionsHTML;

    // Apply default values and initialize form elements
    this.applyPresetDefaults(preset.defaults);
    this.initializeFormElements();
  }

  groupCustomizableOptions(customizableOptions) {
    const groups = {
      "Font Settings": [],
      "Colors & Effects": [],
      "Position & Layout": [],
      "Background & Shadows": [],
      "Other Settings": [],
    };

    customizableOptions.forEach((option) => {
      const key = option.key.toLowerCase();

      if (key.includes("font") || key.includes("weight")) {
        groups["Font Settings"].push(option);
      } else if (key.includes("color") || key.includes("outline")) {
        groups["Colors & Effects"].push(option);
      } else if (
        key.includes("position") ||
        key.includes("margin") ||
        key.includes("offset")
      ) {
        groups["Position & Layout"].push(option);
      } else if (key.includes("background") || key.includes("shadow")) {
        groups["Background & Shadows"].push(option);
      } else {
        groups["Other Settings"].push(option);
      }
    });

    return groups;
  }

  generateSectionHTML(groupName, sectionId, options, defaults) {
    return `
            <div class="config-section bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <button type="button" onclick="ffmpegConfig.toggleSection('${sectionId}')" class="w-full flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">${groupName}</h3>
                        <svg class="w-5 h-5 text-gray-500 transform transition-transform section-arrow" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>
                <div id="${sectionId}" class="section-content p-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        ${this.generateFieldsHTML(options, defaults)}
                    </div>
                </div>
            </div>
        `;
  }

  generateFieldsHTML(options, defaults) {
    return options
      .map((option) => {
        switch (option.type) {
          case "font":
            return this.generateFontField(option, defaults);
          case "number":
            return this.generateNumberField(option, defaults);
          case "color":
            return this.generateColorField(option, defaults);
          case "select":
            return this.generateSelectField(option, defaults);
          case "boolean":
            return this.generateBooleanField(option, defaults);
          case "text":
            return this.generateTextField(option, defaults);
          default:
            return "";
        }
      })
      .join("");
  }

  generateFontField(option, defaults) {
    const defaultValue = defaults[option.key] || "Inter";

    return `
            <div class="field-group">
                <label class="block text-sm font-medium text-gray-700 mb-2">${option.label}</label>
                <select name="${option.key}" id="${option.key}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    ${this.availableFonts
                      .map(
                        (font) => `
                        <option value="${font.family}" ${font.family === defaultValue ? "selected" : ""}>
                            ${font.family}
                        </option>
                    `,
                      )
                      .join("")}
                </select>
            </div>
        `;
  }

  generateNumberField(option, defaults) {
    const defaultValue = defaults[option.key] ?? option.min ?? 0;
    return `
      <div class="field-group">
        <label for="${option.key}">${option.label}</label>
        <div class="input-row">
          <div class="range-slider">
            <input type="range"
                   name="${option.key}"
                   id="${option.key}"
                   min="${option.min || 0}"
                   max="${option.max || 100}"
                   value="${defaultValue}"
                   class=""
                   oninput="ffmpegConfig.updateRangeValue('${option.key}')">
          </div>
          <span id="${option.key}-value" class="range-value-badge">${defaultValue}</span>
        </div>
      </div>
    `;
  }

  generateColorField(option, defaults) {
    const defaultValue = defaults[option.key] || "FFFFFF";
    return `
      <div class="field-group">
        <label for="${option.key}" class="">${option.label}</label>
        <div class="color-input-group">
          <input type="color"
              name="${option.key}-color"
              id="${option.key}-color"
              value="#${defaultValue}"
              class="color-input-modern"
              onchange="ffmpegConfig.syncHexField('${option.key}')">
          <input type="text"
              name="${option.key}"
              id="${option.key}"
              value="${defaultValue}"
              placeholder="FFFFFF"
              class="hex-input-modern px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              maxlength="6"
              oninput="ffmpegConfig.syncColorField('${option.key}')">
        </div>
      </div>
    `;
  }

  generateSelectField(option, defaults) {
    const defaultValue =
      defaults[option.key] ||
      (Array.isArray(option.options)
        ? option.options[0]
        : option.options[0]?.value || "");

    return `
            <div class="field-group">
                <label class="block text-sm font-medium text-gray-700 mb-2">${option.label}</label>
                <select name="${option.key}" id="${option.key}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    ${option.options
                      .map((opt) => {
                        // Handle both string array ["top", "center", "bottom"] and object array [{value: "top", label: "Top"}]
                        const value = typeof opt === "string" ? opt : opt.value;
                        const label =
                          typeof opt === "string"
                            ? opt.charAt(0).toUpperCase() + opt.slice(1)
                            : opt.label;
                        return `<option value="${value}" ${value === defaultValue ? "selected" : ""}>${label}</option>`;
                      })
                      .join("")}
                </select>
            </div>
        `;
  }

  generateBooleanField(option, defaults) {
    const defaultValue = defaults[option.key] || false;

    return `
            <div class="field-group">
                <label class="flex items-center">
                    <input type="checkbox"
                           name="${option.key}"
                           id="${option.key}"
                           ${defaultValue ? "checked" : ""}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm font-medium text-gray-700">${option.label}</span>
                </label>
            </div>
        `;
  }

  generateTextField(option, defaults) {
    const defaultValue = defaults[option.key] || "";

    return `
            <div class="field-group">
                <label class="block text-sm font-medium text-gray-700 mb-2">${option.label}</label>
                <input type="text"
                       name="${option.key}"
                       id="${option.key}"
                       value="${defaultValue}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
        `;
  }

  applyPresetDefaults(defaults) {
    for (const [key, value] of Object.entries(defaults)) {
      const element = document.getElementById(key);
      const colorElement = document.getElementById(`${key}-color`);
      
      if (element) {
        if (element.type === "checkbox") {
          element.checked = !!value;
        } else if (element.type === "range") {
          element.value = value;
          this.updateRangeValue(key);
        } else {
          element.value = value;
        }
      }
      
      // Handle color fields specifically
      if (colorElement && colorElement.type === "color") {
        const hexValue = value.toString().replace("#", "");
        element.value = hexValue;
        colorElement.value = `#${hexValue}`;
      }
    }
  }

  initializeFormElements() {
    // Initialize range inputs
    document.querySelectorAll('input[type="range"]').forEach((range) => {
      this.updateRangeValue(range.id);
    });

    // Initialize color inputs - sync hex to color picker
    document.querySelectorAll('input[type="color"]').forEach((colorInput) => {
      const key = colorInput.id.replace("-color", "");
      this.syncColorField(key);
    });
  }

  updateRangeValue(fieldId) {
    const range = document.getElementById(fieldId);
    const valueDisplay = document.getElementById(`${fieldId}-value`);

    if (range && valueDisplay) {
      valueDisplay.textContent = range.value;
    }
  }

  syncColorField(key) {
    const colorInput = document.getElementById(`${key}-color`);
    const hexInput = document.getElementById(key);

    if (colorInput && hexInput) {
      const hexValue = hexInput.value.replace("#", "");
      if (/^[0-9A-Fa-f]{6}$/.test(hexValue)) {
        colorInput.value = `#${hexValue}`;
      }
    }
  }

  syncHexField(key) {
    const colorInput = document.getElementById(`${key}-color`);
    const hexInput = document.getElementById(key);

    if (colorInput && hexInput) {
      const colorValue = colorInput.value.replace("#", "");
      hexInput.value = colorValue.toUpperCase();
    }
  }

  toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    if (!section) return;

    const parentSection = section.closest(".config-section");
    const arrow = parentSection?.querySelector(".section-arrow");

    if (parentSection?.classList.contains("collapsed")) {
      parentSection.classList.remove("collapsed");
      if (arrow) arrow.style.transform = "rotate(0deg)";
    } else {
      parentSection?.classList.add("collapsed");
      if (arrow) arrow.style.transform = "rotate(-90deg)";
    }
  }

  schedulePreviewUpdate() {
    // Auto-generate preview when settings change (debounced)
    clearTimeout(this.previewTimeout);
    this.previewTimeout = setTimeout(() => {
      if (
        this.currentPreset &&
        !document.getElementById("preview-btn")?.disabled
      ) {
        this.generatePreview();
      }
    }, 1500);
  }

  showNoPresetMessage() {
    const container = document.getElementById("dynamic-config-container");
    if (!container) return;

    container.innerHTML = `
            <div id="no-preset-message" class="bg-white rounded-lg shadow p-8 text-center">
                <div class="text-gray-400 mb-4">
                    <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Select a Preset</h3>
                <p class="text-gray-600">Choose a preset above to configure your caption style options.</p>
            </div>
        `;
  }

  getCurrentConfiguration() {
    if (!this.currentPresetDetails) return null;

    const config = {
      preset: this.currentPreset,
      customStyle: {},
    };

    // Collect all form values
    this.currentPresetDetails.customizable?.forEach((option) => {
      const element = document.getElementById(option.key);
      if (element) {
        if (element.type === "checkbox") {
          config.customStyle[option.key] = element.checked;
        } else if (option.type === "color") {
          // For color fields, read the hex input value (without #)
          config.customStyle[option.key] = element.value.replace("#", "");
        } else {
          config.customStyle[option.key] = element.value;
        }
      }
    });

    return config;
  }

  async generatePreview() {
    const config = this.getCurrentConfiguration();
    if (!config) {
      this.showNotification(
        "error",
        "Configuration Error",
        "Please select a preset first",
      );
      return;
    }

    // Show loading state
    this.showPreviewState("loading");

    try {
      const response = await fetch("/api/ffmpeg/preview", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(config),
      });

      if (response.ok) {
        const contentType = response.headers.get("content-type");

        if (contentType && contentType.includes("application/json")) {
          // Handle JSON response (error case)
          const jsonResponse = await response.json();
          if (!jsonResponse.success) {
            throw new Error(
              jsonResponse.error?.message || "Preview generation failed",
            );
          }
        } else {
          // Handle image response (success case)
          const blob = await response.blob();
          const imageUrl = URL.createObjectURL(blob);

          const previewImage = document.getElementById("preview-image");
          if (previewImage) {
            previewImage.src = imageUrl;
            this.showPreviewState("image");
          }

          this.showNotification(
            "success",
            "Preview Generated",
            "Preview updated successfully",
          );
        }
      } else {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
    } catch (error) {
      console.error("❌ Failed to generate preview:", error);
      this.showNotification(
        "error",
        "Preview Failed",
        "Could not generate preview: " + error.message,
      );
      this.showPreviewState("error");
    }
  }

  async generateFinalVideo() {
    const config = this.getCurrentConfiguration();
    if (!config) {
      this.showNotification(
        "error",
        "Configuration Error",
        "Please select a preset first",
      );
      return;
    }

    // Disable button and show loading
    const generateBtn = document.getElementById("generate-btn");
    if (!generateBtn) return;

    const originalHTML = generateBtn.innerHTML;
    generateBtn.disabled = true;
    generateBtn.innerHTML = `
            <svg class="animate-spin w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            Generating Video...
        `;

    try {
      this.showNotification(
        "info",
        "Processing",
        "Generating your captioned video. This may take a few minutes...",
      );

      const response = await fetch("/api/ffmpeg/generate", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(config),
      });

      if (response.ok) {
        const contentType = response.headers.get("content-type");

        if (contentType && contentType.includes("application/json")) {
          // Handle JSON response (error case)
          const jsonResponse = await response.json();
          if (!jsonResponse.success) {
            throw new Error(
              jsonResponse.error?.message || "Video generation failed",
            );
          }
        } else {
          // Handle video file response (success case)
          const blob = await response.blob();
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement("a");
          a.href = url;
          a.download = "captioned_video.mp4";
          document.body.appendChild(a);
          a.click();
          window.URL.revokeObjectURL(url);
          document.body.removeChild(a);

          this.showNotification(
            "success",
            "Video Generated",
            "Your captioned video has been generated and downloaded!",
          );
        }
      } else {
        const errorData = await response.json();
        throw new Error(
          errorData.error?.message ||
            `HTTP ${response.status}: ${response.statusText}`,
        );
      }
    } catch (error) {
      console.error("❌ Failed to generate video:", error);
      this.showNotification(
        "error",
        "Generation Failed",
        "Could not generate video: " + error.message,
      );
    } finally {
      // Re-enable button
      generateBtn.disabled = false;
      generateBtn.innerHTML = originalHTML;
    }
  }

  async refreshPresets() {
    const loadingEl = document.getElementById("presets-loading");
    const listEl = document.getElementById("presets-list");

    loadingEl?.classList.remove("hidden");
    listEl?.classList.add("hidden");

    await this.loadPresets();
    this.showNotification(
      "success",
      "Refreshed",
      "Presets reloaded successfully",
    );
  }

  showPreviewState(state) {
    const placeholder = document.getElementById("preview-placeholder");
    const image = document.getElementById("preview-image");
    const loading = document.getElementById("preview-loading");
    const error = document.getElementById("preview-error");

    // Hide all states
    [placeholder, image, loading, error].forEach((el) =>
      el?.classList.add("hidden"),
    );

    // Show the requested state
    switch (state) {
      case "placeholder":
        placeholder?.classList.remove("hidden");
        break;
      case "image":
        image?.classList.remove("hidden");
        break;
      case "loading":
        loading?.classList.remove("hidden");
        break;
      case "error":
        error?.classList.remove("hidden");
        break;
    }
  }

  async autoSaveConfiguration() {
    const config = this.getCurrentConfiguration();
    if (!config) return;

    try {
      await fetch("/api/ffmpeg/config/save", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify(config),
      });
    } catch (error) {
      console.warn("Failed to auto-save configuration:", error);
    }
  }

  showNotification(type, title, message) {
    const container = document.getElementById("notifications-container");
    if (!container) return;

    const id = "notification-" + Date.now();

    const colors = {
      success: "bg-green-50 border-green-200 text-green-800",
      error: "bg-red-50 border-red-200 text-red-800",
      warning: "bg-yellow-50 border-yellow-200 text-yellow-800",
      info: "bg-blue-50 border-blue-200 text-blue-800",
    };

    const icons = {
      success: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>`,
      error: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>`,
      warning: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>`,
      info: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>`,
    };

    const notification = document.createElement("div");
    notification.id = id;
    notification.className = `${colors[type]} border rounded-lg p-4 shadow-lg max-w-md transform transition-all duration-300 translate-x-full opacity-0`;
    notification.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${icons[type]}
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium">${title}</h3>
                    <p class="mt-1 text-sm">${message}</p>
                </div>
                <div class="ml-auto pl-3">
                    <button onclick="ffmpegConfig.removeNotification('${id}')" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;

    container.appendChild(notification);

    // Animate in
    setTimeout(() => {
      notification.classList.remove("translate-x-full", "opacity-0");
    }, 100);

    // Auto remove after 5 seconds
    setTimeout(() => {
      this.removeNotification(id);
    }, 5000);
  }

  removeNotification(id) {
    const notification = document.getElementById(id);
    if (notification) {
      notification.classList.add("translate-x-full", "opacity-0");
      setTimeout(() => {
        notification.remove();
      }, 300);
    }
  }
}

// Global instance
let ffmpegConfig;

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function () {
  ffmpegConfig = new FFmpegConfig();
});

// Global functions for backward compatibility
function selectPreset(presetName) {
  ffmpegConfig?.selectPreset(presetName);
}

function generatePreview() {
  ffmpegConfig?.generatePreview();
}

function generateFinalVideo() {
  ffmpegConfig?.generateFinalVideo();
}

function refreshPresets() {
  ffmpegConfig?.refreshPresets();
}

function toggleSection(sectionId) {
  ffmpegConfig?.toggleSection(sectionId);
}

function updateRangeValue(fieldId) {
  ffmpegConfig?.updateRangeValue(fieldId);
}

function syncColorField(key) {
  ffmpegConfig?.syncColorField(key);
}

function syncHexField(key) {
  ffmpegConfig?.syncHexField(key);
}
