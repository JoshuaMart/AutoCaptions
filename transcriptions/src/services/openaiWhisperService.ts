import fs from "fs";
import { OpenAI } from "openai";
import { openAiWhisperApiToCaptions } from "@remotion/openai-whisper";
import { TranscriptionResult, OpenAIWhisperOptions } from "../types";
import config from "../config";
import logger from "../utils/logger";

export class OpenAIWhisperService {
  private openai: OpenAI;

  constructor() {
    if (!config.openai.apiKey) {
      throw new Error("OpenAI API key is required for OpenAI Whisper service");
    }

    this.openai = new OpenAI({
      apiKey: config.openai.apiKey,
    });
  }

  /**
   * Transcribes audio using OpenAI Whisper API
   */
  async transcribe(
    audioPath: string,
    options: OpenAIWhisperOptions = {},
  ): Promise<TranscriptionResult> {
    const startTime = Date.now();

    try {
      logger.info("Starting OpenAI Whisper transcription", {
        audioPath,
        options,
      });

      const transcription = await this.openai.audio.transcriptions.create({
        file: fs.createReadStream(audioPath),
        model: "whisper-1",
        response_format: "verbose_json",
        timestamp_granularities: ["word"],
      });

      logger.info("OpenAI Whisper API response received");

      // Convert OpenAI response to our caption format
      const { captions } = openAiWhisperApiToCaptions({ transcription });

      const processingTime = Date.now() - startTime;

      const result: TranscriptionResult = {
        captions: captions.map((caption) => ({
          text: caption.text,
          startMs: caption.startMs,
          endMs: caption.endMs,
          timestampMs: caption.timestampMs ?? undefined,
          confidence: caption.confidence ?? undefined,
        })),
        duration: transcription.duration,
        language: transcription.language,
        metadata: {
          service: "openai-whisper",
          model: options.model || "whisper-1",
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
      logger.error("OpenAI Whisper transcription failed:", error);
      throw new Error(
        `OpenAI Whisper transcription failed: ${error instanceof Error ? error.message : "Unknown error"}`,
      );
    }
  }
}
