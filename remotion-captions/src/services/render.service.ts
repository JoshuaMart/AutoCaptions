import { exec } from 'child_process';
import { promisify } from 'util';
import fs from 'fs';
import path from 'path';
import config from '../config';
import { RenderProps, UploadedFile, Caption } from '../types';

const execAsync = promisify(exec);

export class RenderService {
  /**
   * Generate props.json file for Remotion
   */
  private generatePropsFile(uploadId: string, captions: Caption[], renderProps: Omit<RenderProps, 'src' | 'captions'>): string {
    const uploadDir = path.join(config.upload.uploadDir, uploadId);
    const propsPath = path.join(uploadDir, 'props.json');
    
    // Create props with relative path for Remotion
    const props: RenderProps = {
      src: `public/uploads/${uploadId}/video.mp4`,
      captions,
      ...renderProps,
    };

    fs.writeFileSync(propsPath, JSON.stringify(props, null, 2));
    return propsPath;
  }

  /**
   * Save transcription JSON file (captions only)
   */
  private saveTranscriptionFile(uploadId: string, transcriptionData: any): string {
    const uploadDir = path.join(config.upload.uploadDir, uploadId);
    const transcriptionPath = path.join(uploadDir, 'video.json');
    
    // Extract only the captions array
    const captions = transcriptionData.transcription?.captions || [];
    
    fs.writeFileSync(transcriptionPath, JSON.stringify(captions, null, 2));
    return transcriptionPath;
  }

  /**
   * Execute Remotion render command
   */
  private async executeRender(uploadId: string, propsPath: string): Promise<string> {
    const outputPath = path.join(config.upload.uploadDir, uploadId, 'output.mp4');
    const relativePropsPath = path.relative(config.render.remotionPath, propsPath);
    const relativeOutputPath = path.relative(config.render.remotionPath, outputPath);
    
    const command = `npx remotion render CaptionedVideo "${relativeOutputPath}" --props="${relativePropsPath}"`;
    
    try {
      console.log(`Executing: ${command}`);
      await execAsync(command, {
        cwd: config.render.remotionPath,
        timeout: 300000, // 5 minutes timeout
      });
      
      return outputPath;
    } catch (error) {
      throw new Error(`Remotion render failed: ${error}`);
    }
  }

  /**
   * Render video with captions
   */
  async renderVideo(
    uploadedFile: UploadedFile,
    captions: Caption[],
    renderProps: Omit<RenderProps, 'src' | 'captions'>,
    transcriptionData: any
  ): Promise<string> {
    const startTime = Date.now();
    
    try {
      console.log(`Starting render for upload ${uploadedFile.id}...`);
      
      // Save transcription file
      const transcriptionPath = this.saveTranscriptionFile(uploadedFile.id, transcriptionData);
      console.log(`Saved transcription file: ${transcriptionPath}`);
      
      // Generate props file
      const propsPath = this.generatePropsFile(uploadedFile.id, captions, renderProps);
      console.log(`Generated props file: ${propsPath}`);
      
      // Execute render
      const outputPath = await this.executeRender(uploadedFile.id, propsPath);
      console.log(`Render completed: ${outputPath}`);
      
      const renderTime = Date.now() - startTime;
      console.log(`Render took ${renderTime}ms`);
      
      return outputPath;
    } catch (error) {
      throw new Error(`Render failed: ${error}`);
    }
  }

  /**
   * Schedule cleanup of rendered files
   */
  scheduleCleanup(uploadId: string): void {
    const timeoutMs = config.render.cleanupTimeoutMinutes * 60 * 1000;
    
    setTimeout(() => {
      this.cleanupRender(uploadId);
    }, timeoutMs);
  }

  /**
   * Clean up rendered files
   */
  private async cleanupRender(uploadId: string): Promise<void> {
    try {
      const uploadDir = path.join(config.upload.uploadDir, uploadId);
      
      if (fs.existsSync(uploadDir)) {
        fs.rmSync(uploadDir, { recursive: true, force: true });
        console.log(`Cleaned up render files for upload ${uploadId}`);
      }
    } catch (error) {
      console.error(`Failed to cleanup render ${uploadId}:`, error);
    }
  }

  /**
   * Get download URL for rendered video
   */
  getDownloadUrl(uploadId: string, baseUrl: string): string {
    return `${baseUrl}/download/${uploadId}`;
  }

  /**
   * Check if rendered file exists
   */
  hasRenderedFile(uploadId: string): boolean {
    const outputPath = path.join(config.upload.uploadDir, uploadId, 'output.mp4');
    return fs.existsSync(outputPath);
  }

  /**
   * Get rendered file path
   */
  getRenderedFilePath(uploadId: string): string {
    return path.join(config.upload.uploadDir, uploadId, 'output.mp4');
  }
}
