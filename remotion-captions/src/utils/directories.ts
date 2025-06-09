import fs from 'fs';
import config from '../config';

/**
 * Initialize required directories
 */
export function initializeDirectories(): void {
  // Create uploads directory
  if (!fs.existsSync(config.upload.uploadDir)) {
    fs.mkdirSync(config.upload.uploadDir, { recursive: true });
    console.log(`Created upload directory: ${config.upload.uploadDir}`);
  }

  // Create temp directory
  const tempDir = `${config.upload.uploadDir}/temp`;
  if (!fs.existsSync(tempDir)) {
    fs.mkdirSync(tempDir, { recursive: true });
    console.log(`Created temp directory: ${tempDir}`);
  }
}

/**
 * Cleanup old uploads and renders
 */
export function cleanupOldUploads(): void {
  try {
    const uploadsDir = config.upload.uploadDir;
    
    if (!fs.existsSync(uploadsDir)) {
      return;
    }

    const items = fs.readdirSync(uploadsDir);
    const now = Date.now();
    const maxAge = config.render.cleanupTimeoutMinutes * 60 * 1000;

    items.forEach(item => {
      if (item === 'temp') return; // Skip temp directory
      
      const itemPath = `${uploadsDir}/${item}`;
      const stat = fs.statSync(itemPath);
      
      if (stat.isDirectory() && (now - stat.mtime.getTime()) > maxAge) {
        fs.rmSync(itemPath, { recursive: true, force: true });
        console.log(`Cleaned up old upload: ${item}`);
      }
    });
  } catch (error) {
    console.error('Error during cleanup:', error);
  }
}
