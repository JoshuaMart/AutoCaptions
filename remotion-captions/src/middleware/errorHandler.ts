import { Request, Response, NextFunction } from 'express';

export const errorHandler = (
  error: any,
  req: Request,
  res: Response,
  next: NextFunction
) => {
  console.error('Error occurred:', error);

  // Multer errors
  if (error.code === 'LIMIT_FILE_SIZE') {
    return res.status(400).json({
      success: false,
      error: 'File too large. Maximum size is 100MB.',
    });
  }

  if (error.code === 'LIMIT_UNEXPECTED_FILE') {
    return res.status(400).json({
      success: false,
      error: 'Unexpected file field. Use "video" field name.',
    });
  }

  // Custom validation errors
  if (error.message && error.message.includes('not allowed')) {
    return res.status(400).json({
      success: false,
      error: error.message,
    });
  }

  // FFmpeg errors
  if (error.message && error.message.includes('ffmpeg')) {
    return res.status(500).json({
      success: false,
      error: 'Video processing failed. Please try again with a different file.',
    });
  }

  // Remotion errors
  if (error.message && error.message.includes('Remotion')) {
    return res.status(500).json({
      success: false,
      error: 'Video rendering failed. Please try again.',
    });
  }

  // Default error
  res.status(500).json({
    success: false,
    error: 'Internal server error',
  });
};
