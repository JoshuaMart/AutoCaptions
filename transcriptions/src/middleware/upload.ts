import multer from 'multer';
import path from 'path';
import fs from 'fs';
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
    '.mp3': 'audio/mpeg',
    '.wav': 'audio/wav',
    '.m4a': 'audio/mp4',
    '.aac': 'audio/aac',
    '.ogg': 'audio/ogg',
    '.webm': 'audio/webm',
    '.mp4': 'video/mp4',
    '.mov': 'video/quicktime',
    '.avi': 'video/x-msvideo',
    '.mkv': 'video/x-matroska',
  };
  return mimeMap[ext] || null;
}

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, config.upload.uploadDir);
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
      cb(new Error(`File type ${mimeType} (${ext}) is not allowed. Allowed types: ${config.upload.allowedMimeTypes.join(', ')}`));
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