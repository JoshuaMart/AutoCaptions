import {
  Caption,
  GenerateCaptionsRequest,
  GenerateCaptionsResponse,
  GeneratePreviewResponse,
  CaptionStyle,
} from "../types";
import { presetService } from "./presetService";
import { generateASS } from "./assGenerator";
import { ffmpegService } from "./ffmpegService";
import { validateVideoFormat } from "../utils/videoValidator";
import logger from "../utils/logger";
import path from "path";
import fs from "fs";
import config from "../config";

export class CaptionService {
  /**
   * Find the best timestamp for preview generation based on caption data
   * @param captions Array of captions
   * @param preferredPosition 'start' | 'middle' | 'end' - preferred position in the video
   * @returns Optimal timestamp for preview
   */
  findOptimalPreviewTimestamp(
    captions: Caption[],
    preferredPosition: "start" | "middle" | "end" = "middle",
  ): { timestamp: number; caption: Caption; reason: string } {
    if (!captions || captions.length === 0) {
      return {
        timestamp: 0,
        caption: null as any,
        reason: "No captions available",
      };
    }

    let targetIndex: number;
    let reason: string;

    switch (preferredPosition) {
      case "start":
        // Find first caption that has a reasonable duration
        targetIndex = captions.findIndex((cap) => {
          const duration = (cap.endMs || cap.startMs + 0.5) - cap.startMs;
          return duration >= 0.3; // At least 300ms
        });
        if (targetIndex === -1) targetIndex = 0;
        reason = "First caption with good duration";
        break;

      case "end":
        // Find last caption that has a reasonable duration
        targetIndex = captions.length - 1;
        for (let i = captions.length - 1; i >= 0; i--) {
          const cap = captions[i];
          const duration = (cap.endMs || cap.startMs + 0.5) - cap.startMs;
          if (duration >= 0.3) {
            targetIndex = i;
            break;
          }
        }
        reason = "Last caption with good duration";
        break;

      case "middle":
      default:
        // Find caption in the middle, preferring longer captions
        const middleTime =
          captions.length > 1
            ? (captions[captions.length - 1].startMs - captions[0].startMs) /
                2 +
              captions[0].startMs
            : captions[0].startMs;

        // Find caption closest to middle time with good duration
        let bestIndex = 0;
        let bestScore = Infinity;

        captions.forEach((cap, idx) => {
          const duration = (cap.endMs || cap.startMs + 0.5) - cap.startMs;
          const timeDistance = Math.abs(cap.startMs - middleTime);

          // Score: prefer captions closer to middle with longer duration
          const score = timeDistance - duration * 2; // Favor longer captions

          if (score < bestScore && duration >= 0.2) {
            bestScore = score;
            bestIndex = idx;
          }
        });

        targetIndex = bestIndex;
        reason = "Middle caption with optimal duration";
        break;
    }

    const selectedCaption = captions[targetIndex];
    const captionStart = selectedCaption.startMs;
    const captionEnd = selectedCaption.endMs || captionStart + 0.5;

    // Use middle of the caption for optimal visibility
    const timestamp = captionStart + (captionEnd - captionStart) / 2;

    return {
      timestamp,
      caption: selectedCaption,
      reason: `${reason}: "${selectedCaption.text}" at ${timestamp.toFixed(2)}s`,
    };
  }

  async generateCaptionsVideo(
    videoPath: string,
    request: GenerateCaptionsRequest,
  ): Promise<GenerateCaptionsResponse> {
    const startTime = Date.now();
    let assPath: string | null = null;
    let outputPath: string | null = null;

    try {
      // Validate video format
      const validation = validateVideoFormat(videoPath);
      if (!validation.isValid) {
        return {
          success: false,
          error: validation.error,
        };
      }

      // Validate transcription data
      if (!request.transcriptionData?.transcription?.captions) {
        return {
          success: false,
          error: "Invalid transcription data provided",
        };
      }

      const captions = request.transcriptionData.transcription.captions;
      if (captions.length === 0) {
        return {
          success: false,
          error: "No captions found in transcription data",
        };
      }

      // Get style configuration
      const presetName = request.preset || "custom";
      const style = presetService.getPresetStyle(
        presetName,
        request.customStyle,
      );

      if (!style) {
        return {
          success: false,
          error: `Preset '${presetName}' not found`,
        };
      }

      // Validate customizations if provided
      if (request.customStyle) {
        const validation = presetService.validateCustomizations(
          presetName,
          request.customStyle,
        );
        if (!validation.isValid) {
          return {
            success: false,
            error: `Invalid customizations: ${validation.errors.join(", ")}`,
          };
        }
      }

      logger.info(
        `Generating captions with preset '${presetName}' for ${captions.length} words`,
      );

      // Generate ASS subtitle content
      const assContent = generateASS(captions, videoPath, style);

      // Debug: Save a copy of the ASS content for inspection
      const debugAssPath = path.join(
        config.upload.tempDir,
        `debug_preview_${Date.now()}.ass`,
      );
      try {
        fs.writeFileSync(debugAssPath, assContent, "utf-8");
        logger.info(`Debug: Preview ASS content saved to ${debugAssPath}`);
      } catch (debugError) {
        logger.warn("Could not save debug preview ASS file:", debugError);
      }

      // Create ASS file
      assPath = await ffmpegService.createAssFile(assContent);

      // Burn subtitles into video
      outputPath = await ffmpegService.burnSubtitles(videoPath, assPath);

      const processingTime = Date.now() - startTime;

      logger.info(`Caption generation completed in ${processingTime}ms`);

      return {
        success: true,
        videoPath: outputPath,
        processingTime,
        metadata: {
          preset: presetName,
          style,
          timestamp: new Date().toISOString(),
        },
      };
    } catch (error) {
      logger.error("Caption generation failed:", error);

      // Cleanup temporary files
      if (assPath) {
        ffmpegService.cleanupFiles(assPath);
      }
      if (outputPath) {
        ffmpegService.cleanupFiles(outputPath);
      }

      return {
        success: false,
        error:
          error instanceof Error ? error.message : "Unknown error occurred",
      };
    } finally {
      // Always cleanup ASS file
      if (assPath) {
        ffmpegService.cleanupFiles(assPath);
      }
    }
  }

  validateRequest(request: any): { isValid: boolean; error?: string } {
    if (!request) {
      return { isValid: false, error: "Request body is required" };
    }

    if (!request.transcriptionData) {
      return { isValid: false, error: "transcriptionData is required" };
    }

    if (!request.transcriptionData.transcription?.captions) {
      return {
        isValid: false,
        error: "transcriptionData.transcription.captions is required",
      };
    }

    if (!Array.isArray(request.transcriptionData.transcription.captions)) {
      return { isValid: false, error: "captions must be an array" };
    }

    // Validate caption format
    const captions = request.transcriptionData.transcription.captions;
    for (let i = 0; i < captions.length; i++) {
      const caption = captions[i];
      if (!caption.text || typeof caption.text !== "string") {
        return {
          isValid: false,
          error: `Caption ${i}: text is required and must be a string`,
        };
      }
      if (typeof caption.startMs !== "number") {
        return {
          isValid: false,
          error: `Caption ${i}: startMs is required and must be a number`,
        };
      }
    }

    // Validate preset if provided
    if (request.preset && typeof request.preset !== "string") {
      return { isValid: false, error: "preset must be a string" };
    }

    // Validate customStyle if provided
    if (request.customStyle && typeof request.customStyle !== "object") {
      return { isValid: false, error: "customStyle must be an object" };
    }

    return { isValid: true };
  }

  async generatePreviewFrame(
    videoPath: string,
    request: GenerateCaptionsRequest,
    timestamp: number,
  ): Promise<GeneratePreviewResponse> {
    const startTime = Date.now();
    let assPath: string | null = null;
    let imagePath: string | null = null;

    try {
      // Validate video format
      const validation = validateVideoFormat(videoPath);
      if (!validation.isValid) {
        return {
          success: false,
          error: validation.error,
        };
      }

      // Validate transcription data
      if (!request.transcriptionData?.transcription?.captions) {
        return {
          success: false,
          error: "Invalid transcription data provided",
        };
      }

      const captions = request.transcriptionData.transcription.captions;
      if (captions.length === 0) {
        return {
          success: false,
          error: "No captions found in transcription data",
        };
      }

      // Get style configuration
      const presetName = request.preset || "custom";
      const style = presetService.getPresetStyle(
        presetName,
        request.customStyle,
      );

      if (!style) {
        return {
          success: false,
          error: `Preset '${presetName}' not found`,
        };
      }

      // Validate customizations if provided
      if (request.customStyle) {
        const validation = presetService.validateCustomizations(
          presetName,
          request.customStyle,
        );
        if (!validation.isValid) {
          return {
            success: false,
            error: `Invalid customizations: ${validation.errors.join(", ")}`,
          };
        }
      }

      logger.info(
        `Generating preview frame with preset '${presetName}' at timestamp ${timestamp}s`,
      );

      // Check if there are any captions that should be visible at the requested timestamp
      const visibleCaptions = captions.filter((caption) => {
        const endTime = caption.endMs || caption.startMs + 0.5;
        return caption.startMs <= timestamp && endTime >= timestamp;
      });

      if (visibleCaptions.length === 0) {
        logger.warn(
          `No captions visible at timestamp ${timestamp}s. Available caption times:`,
        );
        captions.forEach((caption, idx) => {
          const endTime = caption.endMs || caption.startMs + 0.5;
          logger.warn(
            `  ${idx}: "${caption.text}" (${caption.startMs}s - ${endTime}s)`,
          );
        });
      } else {
        logger.info(
          `Found ${visibleCaptions.length} visible captions at ${timestamp}s:`,
        );
        visibleCaptions.forEach((caption) => {
          logger.info(
            `  "${caption.text}" (${caption.startMs}s - ${caption.endMs || caption.startMs + 0.5}s)`,
          );
        });
      }

      // Generate ASS subtitle content
      const assContent = generateASS(captions, videoPath, style);

      // Create ASS file
      assPath = await ffmpegService.createAssFile(assContent);

      // Generate preview frame with subtitles
      imagePath = await ffmpegService.generatePreviewFrame(
        videoPath,
        assPath,
        timestamp,
      );

      const processingTime = Date.now() - startTime;

      logger.info(`Preview frame generation completed in ${processingTime}ms`);

      return {
        success: true,
        imagePath,
        processingTime,
        metadata: {
          preset: presetName,
          style,
          timestamp: new Date().toISOString(),
          frameTimestamp: timestamp,
        },
      };
    } catch (error) {
      logger.error("Preview frame generation failed:", error);

      // Cleanup temporary files
      if (assPath) {
        ffmpegService.cleanupFiles(assPath);
      }
      if (imagePath) {
        ffmpegService.cleanupFiles(imagePath);
      }

      return {
        success: false,
        error:
          error instanceof Error ? error.message : "Unknown error occurred",
      };
    } finally {
      // Always cleanup ASS file
      if (assPath) {
        ffmpegService.cleanupFiles(assPath);
      }
    }
  }
}

export const captionService = new CaptionService();
