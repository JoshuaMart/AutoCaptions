import { Request, Response, NextFunction } from 'express';
import logger from '../utils/logger';

export const errorHandler = (
  error: Error,
  req: Request,
  res: Response,
  next: NextFunction
) => {
  logger.error('Request error:', {
    error: error.message,
    stack: error.stack,
    method: req.method,
    url: req.url,
    body: req.body,
  });

  // Handle multer errors
  if (error.message.includes('File too large')) {
    return res.status(413).json({
      success: false,
      error: 'File size exceeds maximum allowed size',
    });
  }

  if (error.message.includes('File type') && error.message.includes('not allowed')) {
    return res.status(400).json({
      success: false,
      error: error.message,
    });
  }

  // Handle validation errors
  if (error.message.includes('validation')) {
    return res.status(400).json({
      success: false,
      error: error.message,
    });
  }

  // Handle transcription service errors
  if (error.message.includes('transcription failed') || error.message.includes('not available')) {
    return res.status(500).json({
      success: false,
      error: error.message,
    });
  }

  // Default error response
  res.status(500).json({
    success: false,
    error: 'Internal server error',
  });
};