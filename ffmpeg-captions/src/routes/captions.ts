import { Router, Request, Response } from 'express';
import { presetService } from '../services/presetService';
import { fontService } from '../services/fontService';
import { captionService } from '../services/captionService';
import { upload } from '../middleware/upload';
import { validateVideoFormat } from '../utils/videoValidator';
import logger from '../utils/logger';
import fs from 'fs';

const router = Router();

// GET /presets - List all available presets
router.get('/presets', (req: Request, res: Response) => {
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
router.get('/presets/:name', (req: Request, res: Response) => {
  try {
    const { name } = req.params;
    const preset = presetService.getPreset(name);
    
    if (!preset) {
      return res.status(404).json({
        success: false,
        error: `Preset '${name}' not found`
      });
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
router.get('/fonts', (req: Request, res: Response) => {
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

// POST /generate - Generate captions video
router.post('/generate', upload.single('video'), async (req: Request, res: Response) => {
  let videoPath: string | null = null;
  let outputPath: string | null = null;
  
  try {
    // Check if file was uploaded
    if (!req.file) {
      return res.status(400).json({
        success: false,
        error: 'Video file is required'
      });
    }
    
    videoPath = req.file.path;
    
    // Validate video format
    const validation = validateVideoFormat(videoPath);
    if (!validation.isValid) {
      return res.status(400).json({
        success: false,
        error: validation.error
      });
    }
    
    // Parse request body
    let requestData;
    try {
      requestData = typeof req.body.data === 'string' 
        ? JSON.parse(req.body.data) 
        : req.body;
    } catch (error) {
      return res.status(400).json({
        success: false,
        error: 'Invalid JSON in request body'
      });
    }
    
    // Validate request
    const requestValidation = captionService.validateRequest(requestData);
    if (!requestValidation.isValid) {
      return res.status(400).json({
        success: false,
        error: requestValidation.error
      });
    }
    
    logger.info(`Processing caption generation request for file: ${req.file.originalname}`);
    
    // Generate captions
    const result = await captionService.generateCaptionsVideo(videoPath, requestData);
    
    if (!result.success) {
      return res.status(400).json(result);
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

// Health check endpoint
router.get('/health', (req: Request, res: Response) => {
  res.json({
    success: true,
    message: 'FFmpeg Captions Service is running',
    timestamp: new Date().toISOString()
  });
});

export default router;
