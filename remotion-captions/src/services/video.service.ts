import { exec } from 'child_process';
import { promisify } from 'util';
import fs from 'fs';
import path from 'path';
import config from '../config';
import { VideoMetadata, UploadedFile } from '../types';

const execAsync = promisify(exec);

export class VideoService {
  /**
   * Get video metadata using ffprobe
   */
  async getVideoMetadata(filePath: string): Promise<VideoMetadata> {
    try {
      const command = `ffprobe -v quiet -print_format json -show_format -show_streams "${filePath}"`;
      const { stdout } = await execAsync(command, { timeout: config.ffmpeg.timeoutMs });
      
      const data = JSON.parse(stdout);
      const videoStream = data.streams.find((stream: any) => stream.codec_type === 'video');
      
      if (!videoStream) {
        throw new Error('No video stream found');
      }

      const codec = videoStream.codec_name;
      const duration = parseFloat(data.format.duration);
      const width = videoStream.width;
      const height = videoStream.height;
      const isH264 = codec === 'h264';

      return {
        codec,
        duration,
        width,
        height,
        isH264,
      };
    } catch (error) {
      throw new Error(`Failed to get video metadata: ${error}`);
    }
  }

  /**
   * Convert video to H.264 if needed
   */
  async convertToH264(inputPath: string, outputPath: string): Promise<void> {
    try {
      const command = `ffmpeg -i "${inputPath}" -c:v libx264 -c:a copy -movflags +faststart "${outputPath}"`;
      await execAsync(command, { timeout: 60000 }); // 60 seconds for conversion
    } catch (error) {
      throw new Error(`Failed to convert video to H.264: ${error}`);
    }
  }

  /**
   * Process uploaded video file
   */
  async processUploadedVideo(file: Express.Multer.File, uploadId: string): Promise<UploadedFile> {
    const uploadDir = path.join(config.upload.uploadDir, uploadId);
    
    // Ensure upload directory exists
    if (!fs.existsSync(uploadDir)) {
      fs.mkdirSync(uploadDir, { recursive: true });
    }

    const originalPath = path.join(uploadDir, 'original.mp4');
    const processedPath = path.join(uploadDir, 'video.mp4');

    // Move uploaded file to upload directory
    fs.renameSync(file.path, originalPath);

    // Get video metadata
    const metadata = await this.getVideoMetadata(originalPath);

    let finalPath = originalPath;

    // Convert to H.264 if needed
    if (!metadata.isH264) {
      console.log(`Converting video from ${metadata.codec} to H.264...`);
      await this.convertToH264(originalPath, processedPath);
      finalPath = processedPath;
      
      // Update metadata for converted file
      const convertedMetadata = await this.getVideoMetadata(processedPath);
      metadata.codec = convertedMetadata.codec;
      metadata.isH264 = true;
    } else {
      // Just copy to processed path if already H.264
      fs.copyFileSync(originalPath, processedPath);
      finalPath = processedPath;
    }

    return {
      id: uploadId,
      originalPath,
      processedPath: finalPath,
      metadata,
    };
  }

  /**
   * Clean up upload directory
   */
  async cleanupUpload(uploadId: string): Promise<void> {
    const uploadDir = path.join(config.upload.uploadDir, uploadId);
    
    if (fs.existsSync(uploadDir)) {
      fs.rmSync(uploadDir, { recursive: true, force: true });
    }
  }
}
