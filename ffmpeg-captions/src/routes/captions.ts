import { Router, Request, Response, NextFunction } from 'express';
import { presetService } from '../services/presetService';
import { fontService } from '../services/fontService';
import { captionService } from '../services/captionService';
import { upload } from '../middleware/upload';
import { validateVideoFormat } from '../utils/videoValidator';
import logger from '../utils/logger';
import fs from 'fs';

const router = Router();

// GET /presets - List all available presets
router.get('/presets', (req: Request, res: Response): void => {
  try {
    const presets = presetService.getAllPresets();
    res.json({
      success: true,
      presets: presets.map(preset => ({
        name: preset.name,
        displayName: preset.displayName,
        description: preset.description
      }))
    });
  } catch (error) {
    logger.error('Error fetching presets:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch presets'
    });
  }
});

// GET /presets/:name - Get specific preset details
router.get('/presets/:name', (req: Request, res: Response): void => {
  try {
    const { name } = req.params;
    const preset = presetService.getPreset(name);
    
    if (!preset) {
      res.status(404).json({
        success: false,
        error: `Preset '${name}' not found`
      });
      return;
    }
    
    res.json({
      success: true,
      preset
    });
  } catch (error) {
    logger.error('Error fetching preset:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch preset'
    });
  }
});

// GET /fonts - List all available fonts
router.get('/fonts', (req: Request, res: Response): void => {
  try {
    const { category } = req.query;
    
    let fonts;
    if (category && typeof category === 'string') {
      fonts = fontService.getFontsByCategory(category);
    } else {
      fonts = fontService.getAllFonts();
    }
    
    const categories = fontService.getFontCategories();
    
    res.json({
      success: true,
      fonts,
      categories
    });
  } catch (error) {
    logger.error('Error fetching fonts:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch fonts'
    });
  }
});

// GET /fonts/:family/variants - Get font variants for a specific font family
router.get('/fonts/:family/variants', (req: Request, res: Response): void => {
  try {
    const { family } = req.params;
    
    if (!fontService.isValidFont(family)) {
      res.status(404).json({
        success: false,
        error: `Font family '${family}' not found`
      });
      return;
    }
    
    const variants = fontService.getFontVariants(family);
    
    res.json({
      success: true,
      family,
      variants
    });
  } catch (error) {
    logger.error('Error fetching font variants:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch font variants'
    });
  }
});

// POST /generate - Generate captions video
router.post('/generate', upload.single('video'), async (req: Request, res: Response): Promise<void> => {
  let videoPath: string | null = null;
  let outputPath: string | null = null;
  
  try {
    // Check if file was uploaded
    if (!req.file) {
      res.status(400).json({
        success: false,
        error: 'Video file is required'
      });
      return;
    }
    
    videoPath = req.file.path;
    
    // Validate video format
    const validation = validateVideoFormat(videoPath);
    if (!validation.isValid) {
      res.status(400).json({
        success: false,
        error: validation.error
      });
      return;
    }
    
    // Parse request body
    let requestData;
    try {
      requestData = typeof req.body.data === 'string' 
        ? JSON.parse(req.body.data) 
        : req.body;
    } catch (error) {
      res.status(400).json({
        success: false,
        error: 'Invalid JSON in request body'
      });
      return;
    }
    
    // Validate request
    const requestValidation = captionService.validateRequest(requestData);
    if (!requestValidation.isValid) {
      res.status(400).json({
        success: false,
        error: requestValidation.error
      });
      return;
    }
    
    logger.info(`Processing caption generation request for file: ${req.file.originalname}`);
    
    // Generate captions
    const result = await captionService.generateCaptionsVideo(videoPath, requestData);
    
    if (!result.success) {
      res.status(400).json(result);
      return;
    }
    
    outputPath = result.videoPath!;
    
    // Send the video file
    res.setHeader('Content-Type', 'video/mp4');
    res.setHeader('Content-Disposition', `attachment; filename="captioned_${req.file.originalname}"`);
    
    const stream = fs.createReadStream(outputPath);
    stream.pipe(res);
    
    // Cleanup after response is sent
    stream.on('end', () => {
      if (videoPath) {
        fs.unlink(videoPath, (err) => {
          if (err) logger.error('Failed to cleanup input video:', err);
        });
      }
      if (outputPath) {
        fs.unlink(outputPath, (err) => {
          if (err) logger.error('Failed to cleanup output video:', err);
        });
      }
    });
    
    stream.on('error', (error) => {
      logger.error('Error streaming video:', error);
      if (!res.headersSent) {
        res.status(500).json({
          success: false,
          error: 'Failed to send video file'
        });
      }
    });
    
  } catch (error) {
    logger.error('Error in generate endpoint:', error);
    
    // Cleanup files
    if (videoPath && fs.existsSync(videoPath)) {
      fs.unlinkSync(videoPath);
    }
    if (outputPath && fs.existsSync(outputPath)) {
      fs.unlinkSync(outputPath);
    }
    
    if (!res.headersSent) {
      res.status(500).json({
        success: false,
        error: 'Internal server error'
      });
    }
  }
});

// POST /preview - Generate preview frame with captions
router.post('/preview', upload.single('video'), async (req: Request, res: Response): Promise<void> => {
  let videoPath: string | null = null;
  let previewPath: string | null = null;
  
  try {
    // Check if file was uploaded
    if (!req.file) {
      res.status(400).json({
        success: false,
        error: 'Video file is required'
      });
      return;
    }
    
    videoPath = req.file.path;
    
    // Validate video format
    const validation = validateVideoFormat(videoPath);
    if (!validation.isValid) {
      res.status(400).json({
        success: false,
        error: validation.error
      });
      return;
    }
    
    // Parse request body
    let requestData;
    try {
      requestData = typeof req.body.data === 'string' 
        ? JSON.parse(req.body.data) 
        : req.body;
    } catch (error) {
      res.status(400).json({
        success: false,
        error: 'Invalid JSON in request body'
      });
      return;
    }
    
    // Validate request
    const requestValidation = captionService.validateRequest(requestData);
    if (!requestValidation.isValid) {
      res.status(400).json({
        success: false,
        error: requestValidation.error
      });
      return;
    }
    
    // Validate timestamp parameter
    let timestamp = parseFloat(req.query.timestamp as string || '');
    const position = (req.query.position as string || 'middle') as 'start' | 'middle' | 'end';
    
    // Validate position parameter
    if (!['start', 'middle', 'end'].includes(position)) {
      res.status(400).json({
        success: false,
        error: 'Invalid position parameter. Must be "start", "middle", or "end".'
      });
      return;
    }
    
    // If no timestamp provided or invalid, find a good timestamp from captions
    if (isNaN(timestamp) || timestamp < 0) {
      // Extract captions to find a suitable timestamp
      const captions = requestData.transcriptionData?.transcription?.captions;
      if (captions && captions.length > 0) {
        const optimal = captionService.findOptimalPreviewTimestamp(captions, position);
        timestamp = optimal.timestamp;
        logger.info(`No timestamp provided, auto-selected: ${optimal.reason}`);
      } else {
        timestamp = 0;
        logger.warn('No timestamp provided and no captions available, defaulting to 0s');
      }
    }
    
    logger.info(`Generating preview frame at ${timestamp}s for file: ${req.file.originalname}`);
    
    // Generate preview frame
    const result = await captionService.generatePreviewFrame(videoPath, requestData, timestamp);
    
    if (!result.success) {
      res.status(400).json(result);
      return;
    }
    
    previewPath = result.imagePath!;
    
    // Send the image file
    res.setHeader('Content-Type', 'image/png');
    res.setHeader('Content-Disposition', `attachment; filename="preview_${req.file.originalname.replace(/\.[^/.]+$/, "")}.png"`);
    
    const stream = fs.createReadStream(previewPath);
    stream.pipe(res);
    
    // Cleanup after response is sent
    stream.on('end', () => {
      if (videoPath) {
        fs.unlink(videoPath, (err) => {
          if (err) logger.error('Failed to cleanup input video:', err);
        });
      }
      if (previewPath) {
        fs.unlink(previewPath, (err) => {
          if (err) logger.error('Failed to cleanup preview image:', err);
        });
      }
    });
    
    stream.on('error', (error) => {
      logger.error('Error streaming preview image:', error);
      if (!res.headersSent) {
        res.status(500).json({
          success: false,
          error: 'Failed to send preview image'
        });
      }
    });
    
  } catch (error) {
    logger.error('Error in preview endpoint:', error);
    
    // Cleanup files
    if (videoPath && fs.existsSync(videoPath)) {
      fs.unlinkSync(videoPath);
    }
    if (previewPath && fs.existsSync(previewPath)) {
      fs.unlinkSync(previewPath);
    }
    
    if (!res.headersSent) {
      res.status(500).json({
        success: false,
        error: 'Internal server error'
      });
    }
  }
});

// Health check endpoint
router.get('/health', (req: Request, res: Response): void => {
  res.json({
    success: true,
    message: 'FFmpeg Captions Service is running',
    timestamp: new Date().toISOString()
  });
});

export default router;
