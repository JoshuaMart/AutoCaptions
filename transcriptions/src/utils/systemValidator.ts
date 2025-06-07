import { execSync } from 'child_process';
import logger from './logger';

export class SystemValidator {
  /**
   * Validates that required system dependencies are installed
   */
  static validateSystemDependencies(): void {
    this.checkFFmpeg();
    this.checkFFprobe();
  }

  /**
   * Checks if FFmpeg is installed and accessible
   */
  private static checkFFmpeg(): void {
    try {
      execSync('ffmpeg -version', { stdio: 'pipe' });
      logger.info('✅ FFmpeg is available');
    } catch (error) {
      const errorMessage = 'FFmpeg is not installed or not accessible. Please install FFmpeg first.';
      logger.error(errorMessage);
      throw new Error(errorMessage);
    }
  }

  /**
   * Checks if FFprobe is installed and accessible
   */
  private static checkFFprobe(): void {
    try {
      execSync('ffprobe -version', { stdio: 'pipe' });
      logger.info('✅ FFprobe is available');
    } catch (error) {
      const errorMessage = 'FFprobe is not installed or not accessible. Please install FFmpeg (includes FFprobe).';
      logger.error(errorMessage);
      throw new Error(errorMessage);
    }
  }
}