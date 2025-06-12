import { execSync } from "child_process";
import fs from "fs";
import path from "path";
import { v4 as uuidv4 } from "uuid";
import config from "../config";
import logger from "../utils/logger";

export class FFmpegService {
  async burnSubtitles(videoPath: string, assPath: string): Promise<string> {
    const outputFilename = `output_${uuidv4()}.mp4`;
    const outputPath = path.join(config.upload.tempDir, outputFilename);

    try {
      // Ensure temp directory exists
      if (!fs.existsSync(config.upload.tempDir)) {
        fs.mkdirSync(config.upload.tempDir, { recursive: true });
      }

      const command = `ffmpeg -y -i "${videoPath}" -vf "ass=${assPath}" -c:a copy "${outputPath}"`;

      logger.info(`Executing FFmpeg command: ${command}`);

      const startTime = Date.now();
      execSync(command, {
        stdio: "pipe",
        maxBuffer: 1024 * 1024 * 50, // 50MB buffer for large videos
      });
      const processingTime = Date.now() - startTime;

      // Verify output file exists and has content
      if (!fs.existsSync(outputPath)) {
        throw new Error("Output video file was not created");
      }

      const stats = fs.statSync(outputPath);
      if (stats.size === 0) {
        throw new Error("Output video file is empty");
      }

      logger.info(
        `Video processing completed in ${processingTime}ms. Output size: ${stats.size} bytes`,
      );

      return outputPath;
    } catch (error) {
      logger.error("FFmpeg processing failed:", error);

      // Clean up output file if it exists but failed
      if (fs.existsSync(outputPath)) {
        try {
          fs.unlinkSync(outputPath);
        } catch (cleanupError) {
          logger.error("Failed to cleanup failed output file:", cleanupError);
        }
      }

      throw new Error(
        `Video processing failed: ${error instanceof Error ? error.message : "Unknown error"}`,
      );
    }
  }

  cleanupFiles(...filePaths: string[]): void {
    for (const filePath of filePaths) {
      try {
        if (fs.existsSync(filePath)) {
          fs.unlinkSync(filePath);
          logger.info(`Cleaned up file: ${filePath}`);
        }
      } catch (error) {
        logger.error(`Failed to cleanup file ${filePath}:`, error);
      }
    }
  }

  async createAssFile(assContent: string): Promise<string> {
    const assFilename = `subtitles_${uuidv4()}.ass`;
    const assPath = path.join(config.upload.tempDir, assFilename);

    try {
      // Ensure temp directory exists
      if (!fs.existsSync(config.upload.tempDir)) {
        fs.mkdirSync(config.upload.tempDir, { recursive: true });
      }

      fs.writeFileSync(assPath, assContent, "utf-8");
      logger.info(`Created ASS file: ${assPath}`);

      return assPath;
    } catch (error) {
      logger.error("Failed to create ASS file:", error);
      throw new Error("Failed to create subtitle file");
    }
  }

  async generatePreviewFrame(videoPath: string, assPath: string, timestamp: number): Promise<string> {
    const imageFilename = `preview_${uuidv4()}.png`;
    const imagePath = path.join(config.upload.tempDir, imageFilename);

    try {
      // Ensure temp directory exists
      if (!fs.existsSync(config.upload.tempDir)) {
        fs.mkdirSync(config.upload.tempDir, { recursive: true });
      }

      const escapedAssPath = assPath.replace(/\\/g, '\\\\').replace(/:/g, '\\:');
      
      let command = `ffmpeg -y -i "${videoPath}" -ss ${timestamp} -vf "ass='${escapedAssPath}'" -vframes 1 -f image2 "${imagePath}"`;
      
      const startTime = Date.now();
      
      try {
        execSync(command, {
          stdio: "pipe",
          maxBuffer: 1024 * 1024 * 10, // 10MB buffer for image
        });
      } catch (ffmpegError: any) {
        // Try alternative approach with subtitles filter
        try {
          command = `ffmpeg -y -i "${videoPath}" -vf "subtitles='${assPath}':force_style='FontName=Inter'" -ss ${timestamp} -vframes 1 -f image2 "${imagePath}"`;
          
          execSync(command, {
            stdio: "pipe",
            maxBuffer: 1024 * 1024 * 10,
          });
        } catch (ffmpegError2: any) {
          throw ffmpegError; // Throw the original error
        }
      }
      
      const processingTime = Date.now() - startTime;

      // Verify output file exists and has content
      if (!fs.existsSync(imagePath)) {
        throw new Error("Preview image file was not created");
      }

      const stats = fs.statSync(imagePath);
      if (stats.size === 0) {
        throw new Error("Preview image file is empty");
      }

      logger.info(
        `Preview frame generation completed in ${processingTime}ms. Image size: ${stats.size} bytes`,
      );

      return imagePath;
    } catch (error) {
      logger.error("FFmpeg preview generation failed:", error);

      // Clean up output file if it exists but failed
      if (fs.existsSync(imagePath)) {
        try {
          fs.unlinkSync(imagePath);
        } catch (cleanupError) {
          logger.error("Failed to cleanup failed preview image:", cleanupError);
        }
      }

      throw new Error(
        `Preview generation failed: ${error instanceof Error ? error.message : "Unknown error"}`,
      );
    }
  }
}

export const ffmpegService = new FFmpegService();
