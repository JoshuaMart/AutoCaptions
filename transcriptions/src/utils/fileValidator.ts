import { FileValidationResult } from '../types';
import config from '../config';
import { execSync } from 'child_process';
import logger from './logger';
import path from 'path';

export class FileValidator {
  /**
   * Gets expected MIME type from file extension
   */
  private static getMimeTypeFromExtension(filename: string): string | null {
    const ext = path.extname(filename).toLowerCase();
    const mimeMap: Record<string, string> = {
      '.mp3': 'audio/mpeg',
      '.wav': 'audio/wav', 
      '.m4a': 'audio/mp4',
      '.aac': 'audio/aac',
      '.ogg': 'audio/ogg',
      '.webm': 'audio/webm',
      '.mp4': 'video/mp4',
      '.mov': 'video/quicktime',
      '.avi': 'video/x-msvideo',
      '.mkv': 'video/x-matroska',
    };
    return mimeMap[ext] || null;
  }

  /**
   * Validates if the uploaded file meets our requirements
   */
  static async validateFile(file: Express.Multer.File): Promise<FileValidationResult> {
    try {
      // Check file size
      if (file.size > config.upload.maxFileSize) {
        return {
          isValid: false,
          error: `File size exceeds maximum allowed size of ${config.upload.maxFileSize} bytes`,
        };
      }

      // Check MIME type - be more flexible with detection
      let mimeType = file.mimetype;
      const expectedMimeFromExt = this.getMimeTypeFromExtension(file.originalname);
      
      // If MIME type is generic but extension is valid, use expected MIME type
      if ((mimeType === 'application/octet-stream' || mimeType === 'application/binary') && expectedMimeFromExt) {
        mimeType = expectedMimeFromExt;
        logger.info(`Corrected MIME type from ${file.mimetype} to ${mimeType} based on extension`);
      }
      
      if (!config.upload.allowedMimeTypes.includes(mimeType)) {
        // Try one more time with extension-based detection
        if (expectedMimeFromExt && config.upload.allowedMimeTypes.includes(expectedMimeFromExt)) {
          mimeType = expectedMimeFromExt;
          logger.info(`Accepted file based on extension: ${path.extname(file.originalname)}`);
        } else {
          return {
            isValid: false,
            error: `File type ${file.mimetype} (${path.extname(file.originalname)}) is not supported. Allowed types: ${config.upload.allowedMimeTypes.join(', ')}`,
          };
        }
      }

      // Get file metadata using ffprobe
      const metadata = await this.getFileMetadata(file.path);
      
      if (!metadata) {
        return {
          isValid: false,
          error: 'Could not read file metadata. File may be corrupted.',
        };
      }

      return {
        isValid: true,
        fileInfo: {
          size: file.size,
          mimeType: mimeType, // Use corrected MIME type
          duration: metadata.duration,
        },
      };
    } catch (error) {
      logger.error('File validation error:', error);
      return {
        isValid: false,
        error: 'Failed to validate file',
      };
    }
  }

  /**
   * Gets file metadata using ffprobe CLI
   */
  private static async getFileMetadata(filePath: string): Promise<{ duration: number } | null> {
    try {
      // Use ffprobe to get duration
      const command = `ffprobe -v quiet -show_entries format=duration -of csv=p=0 "${filePath}"`;
      const output = execSync(command, { encoding: 'utf8' }).trim();
      const duration = parseFloat(output);
      
      if (isNaN(duration)) {
        logger.error('Invalid duration from ffprobe:', output);
        return null;
      }
      
      return { duration };
    } catch (error) {
      logger.error('FFprobe error:', error);
      return null;
    }
  }
}