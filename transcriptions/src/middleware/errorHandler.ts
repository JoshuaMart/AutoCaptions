import { Request, Response, NextFunction } from 'express';
import logger from '../utils/logger';

export const errorHandler = (
  error: Error,
  req: Request,
  res: Response,
  next: NextFunction
): void => {
  logger.error('Request error:', {
    error: error.message,
    stack: error.stack,
    method: req.method,
    url: req.url,
    body: req.body,
  });

  // Handle multer errors
  if (error.message.includes('File too large')) {
    res.status(413).json({
      success: false,
      error: 'File size exceeds maximum allowed size',
    });
    return;
  }

  if (error.message.includes('File type') && error.message.includes('not allowed')) {
    res.status(400).json({
      success: false,
      error: error.message,
    });
    return;
  }

  // Handle validation errors
  if (error.message.includes('validation')) {
    res.status(400).json({
      success: false,
      error: error.message,
    });
    return;
  }

  // Handle transcription service errors
  if (error.message.includes('transcription failed') || error.message.includes('not available')) {
    res.status(500).json({
      success: false,
      error: error.message,
    });
    return;
  }

  // Default error response
  res.status(500).json({
    success: false,
    error: 'Internal server error',
  });
};