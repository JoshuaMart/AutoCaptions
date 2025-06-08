import { GenerateCaptionsRequest, GenerateCaptionsResponse, CaptionStyle } from '../types';
import { presetService } from './presetService';
import { generateASS } from './assGenerator';
import { ffmpegService } from './ffmpegService';
import { validateVideoFormat } from '../utils/videoValidator';
import logger from '../utils/logger';

export class CaptionService {
  
  async generateCaptionsVideo(
    videoPath: string, 
    request: GenerateCaptionsRequest
  ): Promise<GenerateCaptionsResponse> {
    const startTime = Date.now();
    let assPath: string | null = null;
    let outputPath: string | null = null;
    
    try {
      // Validate video format
      const validation = validateVideoFormat(videoPath);
      if (!validation.isValid) {
        return {
          success: false,
          error: validation.error
        };
      }
      
      // Validate transcription data
      if (!request.transcriptionData?.transcription?.captions) {
        return {
          success: false,
          error: 'Invalid transcription data provided'
        };
      }
      
      const captions = request.transcriptionData.transcription.captions;
      if (captions.length === 0) {
        return {
          success: false,
          error: 'No captions found in transcription data'
        };
      }
      
      // Get style configuration
      const presetName = request.preset || 'custom';
      const style = presetService.getPresetStyle(presetName, request.customStyle);
      
      if (!style) {
        return {
          success: false,
          error: `Preset '${presetName}' not found`
        };
      }
      
      // Validate customizations if provided
      if (request.customStyle) {
        const validation = presetService.validateCustomizations(presetName, request.customStyle);
        if (!validation.isValid) {
          return {
            success: false,
            error: `Invalid customizations: ${validation.errors.join(', ')}`
          };
        }
      }
      
      logger.info(`Generating captions with preset '${presetName}' for ${captions.length} words`);
      
      // Generate ASS subtitle content
      const assContent = generateASS(captions, videoPath, style);
      
      // Create ASS file
      assPath = await ffmpegService.createAssFile(assContent);
      
      // Burn subtitles into video
      outputPath = await ffmpegService.burnSubtitles(videoPath, assPath);
      
      const processingTime = Date.now() - startTime;
      
      logger.info(`Caption generation completed in ${processingTime}ms`);
      
      return {
        success: true,
        videoPath: outputPath,
        processingTime,
        metadata: {
          preset: presetName,
          style,
          timestamp: new Date().toISOString()
        }
      };
      
    } catch (error) {
      logger.error('Caption generation failed:', error);
      
      // Cleanup temporary files
      if (assPath) {
        ffmpegService.cleanupFiles(assPath);
      }
      if (outputPath) {
        ffmpegService.cleanupFiles(outputPath);
      }
      
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Unknown error occurred'
      };
    } finally {
      // Always cleanup ASS file
      if (assPath) {
        ffmpegService.cleanupFiles(assPath);
      }
    }
  }
  
  validateRequest(request: any): { isValid: boolean; error?: string } {
    if (!request) {
      return { isValid: false, error: 'Request body is required' };
    }
    
    if (!request.transcriptionData) {
      return { isValid: false, error: 'transcriptionData is required' };
    }
    
    if (!request.transcriptionData.transcription?.captions) {
      return { isValid: false, error: 'transcriptionData.transcription.captions is required' };
    }
    
    if (!Array.isArray(request.transcriptionData.transcription.captions)) {
      return { isValid: false, error: 'captions must be an array' };
    }
    
    // Validate caption format
    const captions = request.transcriptionData.transcription.captions;
    for (let i = 0; i < captions.length; i++) {
      const caption = captions[i];
      if (!caption.text || typeof caption.text !== 'string') {
        return { isValid: false, error: `Caption ${i}: text is required and must be a string` };
      }
      if (typeof caption.startInSeconds !== 'number') {
        return { isValid: false, error: `Caption ${i}: startInSeconds is required and must be a number` };
      }
    }
    
    // Validate preset if provided
    if (request.preset && typeof request.preset !== 'string') {
      return { isValid: false, error: 'preset must be a string' };
    }
    
    // Validate customStyle if provided
    if (request.customStyle && typeof request.customStyle !== 'object') {
      return { isValid: false, error: 'customStyle must be an object' };
    }
    
    return { isValid: true };
  }
}

export const captionService = new CaptionService();
