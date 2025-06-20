import path from "path";
import fs from "fs/promises";
import {
  downloadWhisperModel,
  installWhisperCpp,
  transcribe,
  toCaptions,
  WhisperModel,
  Language,
} from "@remotion/install-whisper-cpp";
import { TranscriptionResult, WhisperCppOptions } from "../types";
import config from "../config";
import logger from "../utils/logger";

export class WhisperCppService {
  private whisperPath: string;
  private isInitialized: boolean = false;

  constructor() {
    this.whisperPath = path.join(process.cwd(), "whisper.cpp");
  }

  /**
   * Initializes Whisper.cpp if not already done
   */
  async initialize(): Promise<void> {
    if (this.isInitialized) {
      return;
    }

    try {
      logger.info("Initializing Whisper.cpp", {
        version: config.transcription.whisperCppVersion,
        model: config.transcription.whisperModel,
        path: this.whisperPath,
      });

      // Check if whisper.cpp is already installed
      const whisperExists = await this.checkWhisperExists();

      if (!whisperExists) {
        logger.info("Installing Whisper.cpp...");
        await installWhisperCpp({
          to: this.whisperPath,
          version: config.transcription.whisperCppVersion,
        });
        logger.info("Whisper.cpp installation completed");
      } else {
        logger.info("Whisper.cpp already installed");
      }

      // Check if model is downloaded
      const modelExists = await this.checkModelExists(
        config.transcription.whisperModel,
      );

      if (!modelExists) {
        logger.info(`Downloading model: ${config.transcription.whisperModel}`);
        await downloadWhisperModel({
          model: config.transcription.whisperModel as WhisperModel,
          folder: this.whisperPath,
        });
        logger.info(
          `Model ${config.transcription.whisperModel} downloaded successfully`,
        );
      } else {
        logger.info(
          `Model ${config.transcription.whisperModel} already exists`,
        );
      }

      this.isInitialized = true;
      logger.info("Whisper.cpp initialization completed");
    } catch (error) {
      logger.error("Whisper.cpp initialization failed:", error);
      throw new Error(
        `Whisper.cpp initialization failed: ${error instanceof Error ? error.message : "Unknown error"}`,
      );
    }
  }

  /**
   * Transcribes audio using Whisper.cpp
   */
  async transcribe(
    audioPath: string,
    options: WhisperCppOptions = {},
  ): Promise<TranscriptionResult> {
    await this.initialize();

    const startTime = Date.now();

    try {
      logger.info("Starting Whisper.cpp transcription", { audioPath, options });

      const whisperCppOutput = await transcribe({
        model: (options.model ||
          config.transcription.whisperModel) as WhisperModel,
        whisperPath: this.whisperPath,
        whisperCppVersion: config.transcription.whisperCppVersion,
        inputPath: audioPath,
        tokenLevelTimestamps: true, // Always true for toCaptions compatibility
        language: options.language as Language | null | undefined,
        translateToEnglish: options.translateToEnglish,
      });

      logger.info(
        "Whisper.cpp transcription completed, converting to captions",
      );

      // Convert to captions format using the new toCaptions function
      // whisperCppOutput is guaranteed to be TranscriptionJson<true> since tokenLevelTimestamps is true
      const { captions } = toCaptions({
        whisperCppOutput,
      });

      const processingTime = Date.now() - startTime;

      // Calculate total duration from captions
      const duration =
        captions.length > 0
          ? Math.max(...captions.map((c) => c.endMs)) / 1000
          : 0;

      const result: TranscriptionResult = {
        captions: captions.map((caption) => ({
          text: caption.text,
          startMs: caption.startMs,
          endMs: caption.endMs,
          timestampMs: caption.timestampMs ?? undefined,
          confidence: caption.confidence ?? undefined,
        })),
        duration,
        language: options.language || "auto",
        metadata: {
          service: "whisper-cpp",
          model: options.model || config.transcription.whisperModel,
          timestamp: new Date().toISOString(),
        },
      };

      logger.info("Transcription completed", {
        duration: result.duration,
        captionsCount: result.captions.length,
        processingTime: `${processingTime}ms`,
      });

      return result;
    } catch (error) {
      logger.error("Whisper.cpp transcription failed:", error);
      throw new Error(
        `Whisper.cpp transcription failed: ${error instanceof Error ? error.message : "Unknown error"}`,
      );
    }
  }

  /**
   * Checks if Whisper.cpp is installed
   */
  private async checkWhisperExists(): Promise<boolean> {
    try {
      const mainPath = path.join(this.whisperPath, "main");
      const mainExePath = path.join(this.whisperPath, "main.exe");

      const [mainExists, mainExeExists] = await Promise.all([
        fs
          .access(mainPath)
          .then(() => true)
          .catch(() => false),
        fs
          .access(mainExePath)
          .then(() => true)
          .catch(() => false),
      ]);

      return mainExists || mainExeExists;
    } catch {
      return false;
    }
  }

  /**
   * Checks if a specific model is downloaded
   */
  private async checkModelExists(model: string): Promise<boolean> {
    try {
      const modelPath = path.join(
        this.whisperPath,
        "models",
        `ggml-${model}.bin`,
      );
      await fs.access(modelPath);
      return true;
    } catch {
      return false;
    }
  }
}
