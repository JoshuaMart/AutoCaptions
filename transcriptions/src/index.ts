import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import config from './config';
import logger from './utils/logger';
import { SystemValidator } from './utils/systemValidator';
import transcriptionRoutes from './routes/transcription';
import { errorHandler } from './middleware/errorHandler';

const app = express();

// Validate system dependencies on startup
try {
  SystemValidator.validateSystemDependencies();
} catch (error) {
  logger.error('System validation failed:', error);
  process.exit(1);
}

// Security middleware
app.use(helmet());

// CORS configuration
app.use(cors({
  origin: config.server.nodeEnv === 'production' 
    ? ['http://localhost', 'http://localhost:3000'] // Add your production domains
    : true, // Allow all origins in development
  credentials: true,
}));

// Body parsing middleware
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Request logging
app.use((req, res, next) => {
  logger.info(`${req.method} ${req.path}`, {
    method: req.method,
    url: req.url,
    userAgent: req.get('User-Agent'),
    ip: req.ip,
  });
  next();
});

// Routes
app.use('/api', transcriptionRoutes);

// Root endpoint
app.get('/', (req, res) => {
  res.json({
    name: 'Transcription Service API',
    version: '1.0.0',
    status: 'running',
    timestamp: new Date().toISOString(),
    endpoints: {
      transcribe: 'POST /api/transcribe',
      services: 'GET /api/services',
      health: 'GET /api/health',
    },
  });
});

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({
    success: false,
    error: 'Endpoint not found',
  });
});

// Error handling middleware (must be last)
app.use(errorHandler);

// Start server
const server = app.listen(config.server.port, () => {
  logger.info(`Transcription service started`, {
    port: config.server.port,
    nodeEnv: config.server.nodeEnv,
    transcriptionService: config.transcription.service,
    whisperModel: config.transcription.whisperModel,
  });
});

// Graceful shutdown
process.on('SIGTERM', () => {
  logger.info('SIGTERM received, shutting down gracefully');
  server.close(() => {
    logger.info('Process terminated');
    process.exit(0);
  });
});

process.on('SIGINT', () => {
  logger.info('SIGINT received, shutting down gracefully');
  server.close(() => {
    logger.info('Process terminated');
    process.exit(0);
  });
});

export default app;