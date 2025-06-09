import express, { Request, Response, NextFunction } from 'express';
import cors from 'cors';
import multer from 'multer';
import { upload, generateUploadId } from './middleware/upload';
import { errorHandler } from './middleware/errorHandler';
import { RenderController } from './controllers/render.controller';
import config from './config';
import { initializeDirectories, cleanupOldUploads } from './utils/directories';

const app = express();
const renderController = new RenderController();

// Middleware
app.use(cors());
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Routes
app.get('/', (req, res) => renderController.getServiceInfo(req, res));
app.get('/health', (req, res) => renderController.healthCheck(req, res));

app.post('/render', 
  generateUploadId,
  upload.fields([{ name: 'video', maxCount: 1 }]),
  (req, res) => renderController.renderVideo(req, res)
);

app.get('/download/:uploadId', (req, res) => renderController.downloadVideo(req, res));

// 404 handler
app.use('*', (req: Request, res: Response) => {
  res.status(404).json({
    success: false,
    error: 'Endpoint not found',
    availableEndpoints: {
      "info": "GET /",
      "render": "POST /render",
      "download": "GET /download/:uploadId",
      "health": "GET /health"
    }
  });
});

// Error handling middleware (must be last)
app.use(errorHandler as any);

const PORT = config.server.port;

// Initialize directories and cleanup old uploads
initializeDirectories();
cleanupOldUploads();

// Schedule periodic cleanup every hour
setInterval(cleanupOldUploads, 60 * 60 * 1000);

app.listen(PORT, () => {
  console.log(`🚀 Remotion Captions API server running on port ${PORT}`);
  console.log(`📁 Upload directory: ${config.upload.uploadDir}`);
  console.log(`🎬 Remotion path: ${config.render.remotionPath}`);
  console.log(`⏰ Cleanup timeout: ${config.render.cleanupTimeoutMinutes} minutes`);
});

export default app;
