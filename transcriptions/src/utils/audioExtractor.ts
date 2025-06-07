import { execSync, spawn } from "child_process";
import path from "path";
import fs from "fs/promises";
import { AudioExtractionResult } from "../types";
import config from "../config";
import logger from "./logger";
import { v4 as uuidv4 } from "uuid";

export class AudioExtractor {
  /**
   * Extracts audio from video/audio file and converts to 16kHz WAV format
   * Required for whisper-cpp processing
   */
  static async extractAudio(inputPath: string): Promise<AudioExtractionResult> {
    const outputFilename = `${uuidv4()}.wav`;
    const outputPath = path.join(config.upload.tempDir, outputFilename);

    // Ensure temp directory exists
    await fs.mkdir(config.upload.tempDir, { recursive: true });

    try {
      // Get file duration first
      const duration = await this.getFileDuration(inputPath);

      // Extract audio using FFmpeg CLI
      await this.runFFmpegExtraction(inputPath, outputPath);

      logger.info(`Audio extraction completed: ${outputPath}`);

      return {
        audioPath: outputPath,
        duration,
        sampleRate: 16000,
      };
    } catch (error) {
      logger.error("Audio extraction error:", error);
      throw new Error(
        `Audio extraction failed: ${error instanceof Error ? error.message : "Unknown error"}`,
      );
    }
  }

  /**
   * Gets file duration using ffprobe
   */
  private static async getFileDuration(inputPath: string): Promise<number> {
    try {
      const command = `ffprobe -v quiet -show_entries format=duration -of csv=p=0 "${inputPath}"`;
      const output = execSync(command, { encoding: "utf8" }).trim();
      const duration = parseFloat(output);

      if (isNaN(duration)) {
        throw new Error("Could not determine file duration");
      }

      return duration;
    } catch (error) {
      logger.error("Failed to get file duration:", error);
      throw new Error("Could not determine file duration");
    }
  }

  /**
   * Runs FFmpeg extraction with progress tracking
   */
  private static async runFFmpegExtraction(
    inputPath: string,
    outputPath: string,
  ): Promise<void> {
    return new Promise((resolve, reject) => {
      const args = [
        "-i",
        inputPath,
        "-ar",
        "16000", // 16kHz sample rate
        "-ac",
        "1", // Mono
        "-c:a",
        "pcm_s16le", // PCM 16-bit little-endian
        "-f",
        "wav", // WAV format
        "-y", // Overwrite output file
        "-hide_banner", // Hide banner
        "-loglevel",
        "error", // Less verbose
        outputPath,
      ];

      const ffmpeg = spawn("ffmpeg", args);
      let errorOutput = "";

      ffmpeg.stderr.on("data", (data) => {
        const output = data.toString();
        errorOutput += output;

        // Parse progress from FFmpeg stderr
        const timeMatch = output.match(/time=(\d{2}):(\d{2}):(\d{2}\.\d{2})/);
        if (timeMatch) {
          const hours = parseInt(timeMatch[1]);
          const minutes = parseInt(timeMatch[2]);
          const seconds = parseFloat(timeMatch[3]);
          const currentTime = hours * 3600 + minutes * 60 + seconds;

          logger.debug(`Audio extraction progress: ${currentTime.toFixed(1)}s`);
        }
      });

      ffmpeg.on("close", (code) => {
        if (code === 0) {
          resolve();
        } else {
          logger.error("FFmpeg stderr output:", errorOutput);
          reject(new Error(`FFmpeg process exited with code ${code}`));
        }
      });

      ffmpeg.on("error", (error) => {
        reject(new Error(`Failed to start FFmpeg: ${error.message}`));
      });
    });
  }

  /**
   * Cleans up temporary audio files
   */
  static async cleanup(audioPath: string): Promise<void> {
    try {
      await fs.unlink(audioPath);
      logger.info(`Cleaned up temporary file: ${audioPath}`);
    } catch (error) {
      logger.warn(`Failed to cleanup file ${audioPath}:`, error);
    }
  }
}
