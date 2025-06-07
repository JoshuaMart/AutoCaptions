import { TranscriptionRequest, TranscriptionResult } from "../types";
import { OpenAIWhisperService } from "./openaiWhisperService";
import { WhisperCppService } from "./whisperCppService";
import { AudioExtractor } from "../utils/audioExtractor";
import config from "../config";
import logger from "../utils/logger";

export class TranscriptionService {
  private openaiService?: OpenAIWhisperService;
  private whisperCppService?: WhisperCppService;

  constructor() {
    // Initialize services based on configuration
    if (
      config.transcription.service === "openai-whisper" ||
      config.openai.apiKey
    ) {
      this.openaiService = new OpenAIWhisperService();
    }

    this.whisperCppService = new WhisperCppService();
  }

  /**
   * Transcribes an audio or video file
   */
  async transcribeFile(
    filePath: string,
    request: TranscriptionRequest,
  ): Promise<TranscriptionResult> {
    let audioPath: string | null = null;

    try {
      logger.info("Starting transcription process", {
        filePath,
        service: request.service,
      });

      // Extract audio from the file (converts to 16kHz WAV for whisper-cpp)
      const audioResult = await AudioExtractor.extractAudio(filePath);
      audioPath = audioResult.audioPath;

      logger.info("Audio extraction completed", {
        audioPath,
        duration: audioResult.duration,
        sampleRate: audioResult.sampleRate,
      });

      let transcriptionResult: TranscriptionResult;

      // Transcribe based on selected service
      if (request.service === "openai-whisper") {
        if (!this.openaiService) {
          throw new Error(
            "OpenAI Whisper service not available. Check your API key configuration.",
          );
        }

        transcriptionResult = await this.openaiService.transcribe(audioPath, {
          language: request.language,
        });
      } else if (request.service === "whisper-cpp") {
        transcriptionResult = await this.whisperCppService.transcribe(
          audioPath,
          {
            language: request.language,
            translateToEnglish: request.translateToEnglish,
          },
        );
      } else {
        throw new Error(
          `Unsupported transcription service: ${request.service}`,
        );
      }

      logger.info("Transcription completed successfully", {
        service: request.service,
        captionsCount: transcriptionResult.captions.length,
        duration: transcriptionResult.duration,
      });

      return transcriptionResult;
    } catch (error) {
      logger.error("Transcription process failed:", error);
      throw error;
    } finally {
      // Cleanup temporary audio file
      if (audioPath) {
        await AudioExtractor.cleanup(audioPath);
      }
    }
  }

  /**
   * Gets available transcription services
   */
  getAvailableServices(): string[] {
    const services = ["whisper-cpp"];

    if (this.openaiService) {
      services.push("openai-whisper");
    }

    return services;
  }

  /**
   * Gets the default transcription service
   */
  getDefaultService(): string {
    return config.transcription.service;
  }
}
