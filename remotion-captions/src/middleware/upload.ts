import multer from 'multer';
import path from 'path';
import fs from 'fs';
import { Request, Response, NextFunction } from 'express';
import config from '../config';
import { v4 as uuidv4 } from 'uuid';

// Ensure upload directory exists
if (!fs.existsSync(config.upload.uploadDir)) {
  fs.mkdirSync(config.upload.uploadDir, { recursive: true });
}

// Helper function to get MIME type from file extension
function getMimeTypeFromExtension(filename: string): string | null {
  const ext = path.extname(filename).toLowerCase();
  const mimeMap: Record<string, string> = {
    '.mp4': 'video/mp4',
    '.mov': 'video/quicktime',
    '.avi': 'video/x-msvideo',
    '.mkv': 'video/x-matroska',
    '.webm': 'video/webm',
  };
  return mimeMap[ext] || null;
}

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    // Create temporary directory for this upload
    const tempDir = path.join(config.upload.uploadDir, 'temp');
    if (!fs.existsSync(tempDir)) {
      fs.mkdirSync(tempDir, { recursive: true });
    }
    cb(null, tempDir);
  },
  filename: (req, file, cb) => {
    // Generate unique filename with original extension
    const uniqueName = `${uuidv4()}${path.extname(file.originalname)}`;
    cb(null, uniqueName);
  },
});

// Enhanced file filter with fallback MIME type detection
const fileFilter = (req: any, file: Express.Multer.File, cb: multer.FileFilterCallback) => {
  let mimeType = file.mimetype;
  
  // If MIME type is generic, try to detect from extension
  if (mimeType === 'application/octet-stream' || mimeType === 'application/binary') {
    const detectedMime = getMimeTypeFromExtension(file.originalname);
    if (detectedMime) {
      mimeType = detectedMime;
      // Update the file object
      file.mimetype = detectedMime;
    }
  }
  
  if (config.upload.allowedMimeTypes.includes(mimeType)) {
    cb(null, true);
  } else {
    const ext = path.extname(file.originalname).toLowerCase();
    const detectedMime = getMimeTypeFromExtension(file.originalname);
    
    if (detectedMime && config.upload.allowedMimeTypes.includes(detectedMime)) {
      // Accept file based on extension
      file.mimetype = detectedMime;
      cb(null, true);
    } else {
      cb(new Error(`File type ${mimeType} (${ext}) is not allowed. Only video files are accepted.`));
    }
  }
};

export const upload = multer({
  storage,
  fileFilter,
  limits: {
    fileSize: config.upload.maxFileSize,
  },
});

// Middleware to generate upload ID
export const generateUploadId = (req: Request, res: Response, next: NextFunction) => {
  (req as any).uploadId = uuidv4();
  next();
};
