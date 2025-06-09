import { Request, Response } from 'express';
import fs from 'fs';
import path from 'path';
import { VideoService } from '../services/video.service';
import { RenderService } from '../services/render.service';
import { RenderRequest, RenderResponse, TranscriptionResponse } from '../types';

const videoService = new VideoService();
const renderService = new RenderService();

interface RenderRequestBody {
  transcription: TranscriptionResponse;
  props: any;
}

export class RenderController {
  /**
   * Render video with captions
   */
  async renderVideo(req: Request, res: Response): Promise<void> {
    const startTime = Date.now();
    let uploadId: string | undefined;
    
    try {
      // Type assertions for multer files and uploadId
      const files = req.files as any;
      uploadId = (req as any).uploadId as string;
      
      // Validate request
      if (!files?.video?.[0]) {
        res.status(400).json({
          success: false,
          error: 'No video file provided',
        });
        return;
      }

      if (!req.body.transcription || !req.body.props) {
        res.status(400).json({
          success: false,
          error: 'Missing transcription or props data',
        });
        return;
      }

      const videoFile = files.video[0];

      if (!uploadId) {
        res.status(500).json({
          success: false,
          error: 'Upload ID not generated',
        });
        return;
      }

      console.log(`Processing render request ${uploadId}...`);

      // Parse request data
      let transcriptionData: TranscriptionResponse;
      let propsData: any;

      try {
        transcriptionData = typeof req.body.transcription === 'string' 
          ? JSON.parse(req.body.transcription) 
          : req.body.transcription;
        
        propsData = typeof req.body.props === 'string' 
          ? JSON.parse(req.body.props) 
          : req.body.props;
      } catch (error) {
        res.status(400).json({
          success: false,
          error: 'Invalid JSON data in transcription or props',
        });
        return;
      }

      // Validate transcription data
      if (!transcriptionData.success || !transcriptionData.transcription?.captions) {
        res.status(400).json({
          success: false,
          error: 'Invalid transcription data',
        });
        return;
      }

      // Process video (convert to H.264 if needed)
      console.log(`Processing video file for upload ${uploadId}...`);
      const uploadedFile = await videoService.processUploadedVideo(videoFile, uploadId);
      console.log(`Video processed: ${uploadedFile.metadata.codec}, H.264: ${uploadedFile.metadata.isH264}`);

      // Render video with captions
      console.log(`Starting Remotion render for upload ${uploadId}...`);
      const outputPath = await renderService.renderVideo(
        uploadedFile,
        transcriptionData.transcription.captions,
        propsData,
        transcriptionData
      );

      // Generate download URL
      const baseUrl = `${req.protocol}://${req.get('host')}`;
      const downloadUrl = renderService.getDownloadUrl(uploadId, baseUrl);

      // Schedule cleanup
      renderService.scheduleCleanup(uploadId);

      const renderTime = Date.now() - startTime;

      const response: RenderResponse = {
        success: true,
        downloadUrl,
        renderTime,
      };

      console.log(`Render completed for upload ${uploadId} in ${renderTime}ms`);
      res.json(response);

    } catch (error) {
      console.error('Render error:', error);
      
      // Cleanup on error
      if (uploadId) {
        try {
          await videoService.cleanupUpload(uploadId);
        } catch (cleanupError) {
          console.error('Cleanup error:', cleanupError);
        }
      }

      res.status(500).json({
        success: false,
        error: error instanceof Error ? error.message : 'Internal server error',
      });
    }
  }

  /**
   * Download rendered video
   */
  async downloadVideo(req: Request, res: Response): Promise<void> {
    try {
      const { uploadId } = req.params;

      if (!uploadId) {
        res.status(400).json({
          success: false,
          error: 'Upload ID is required',
        });
        return;
      }

      // Check if file exists
      if (!renderService.hasRenderedFile(uploadId)) {
        res.status(404).json({
          success: false,
          error: 'Rendered video not found or has expired',
        });
        return;
      }

      const filePath = renderService.getRenderedFilePath(uploadId);
      const fileName = `captioned_video_${uploadId}.mp4`;

      // Set headers for file download
      res.setHeader('Content-Type', 'video/mp4');
      res.setHeader('Content-Disposition', `attachment; filename="${fileName}"`);

      // Stream file to response
      const fileStream = fs.createReadStream(filePath);
      fileStream.pipe(res);

      fileStream.on('error', (error) => {
        console.error('File stream error:', error);
        if (!res.headersSent) {
          res.status(500).json({
            success: false,
            error: 'Error streaming file',
          });
        }
      });

    } catch (error) {
      console.error('Download error:', error);
      res.status(500).json({
        success: false,
        error: error instanceof Error ? error.message : 'Internal server error',
      });
    }
  }

  /**
   * Health check endpoint
   */
  async healthCheck(req: Request, res: Response): Promise<void> {
    res.json({
      success: true,
      service: 'remotion-captions',
      timestamp: new Date().toISOString(),
    });
  }
}
