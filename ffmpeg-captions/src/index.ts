import express from "express";
import cors from "cors";
import helmet from "helmet";
import path from "path";
import config from "./config";
import logger from "./utils/logger";
import { errorHandler } from "./middleware/errorHandler";
import captionsRouter from "./routes/captions";

const app = express();

// Security middleware
app.use(helmet());

// CORS middleware
app.use(
  cors({
    origin: process.env.ALLOWED_ORIGINS?.split(",") || "*",
    credentials: true,
  }),
);

// Body parsing middleware
app.use(express.json({ limit: "50mb" }));
app.use(express.urlencoded({ extended: true, limit: "50mb" }));

// Request logging
app.use((req, res, next): void => {
  logger.info(`${req.method} ${req.path} - ${req.ip}`);
  next();
});

// Routes
app.use("/api/captions", captionsRouter);

// Root endpoint
app.get("/", (req, res): void => {
  res.json({
    message: "FFmpeg Captions Service",
    version: "1.0.0",
    endpoints: {
      health: "/api/captions/health",
      presets: "/api/captions/presets",
      fonts: "/api/captions/fonts",
      generate: "/api/captions/generate",
      preview: "/api/captions/preview",
    },
  });
});

// 404 handler
app.use("*", (req, res): void => {
  res.status(404).json({
    success: false,
    error: "Endpoint not found",
  });
});

// Error handling middleware (must be last)
app.use(errorHandler as any);

// Start server
const startServer = () => {
  try {
    app.listen(config.server.port, () => {
      logger.info(
        `🚀 FFmpeg Captions Service started on port ${config.server.port}`,
      );
      logger.info(`📝 Environment: ${config.server.nodeEnv}`);
      logger.info(`📁 Upload directory: ${config.upload.uploadDir}`);
    });
  } catch (error) {
    logger.error("Failed to start server:", error);
    process.exit(1);
  }
};

// Handle uncaught exceptions
process.on("uncaughtException", (error) => {
  logger.error("Uncaught Exception:", error);
  process.exit(1);
});

process.on("unhandledRejection", (reason, promise) => {
  logger.error("Unhandled Rejection at:", promise, "reason:", reason);
  process.exit(1);
});

// Graceful shutdown
process.on("SIGTERM", () => {
  logger.info("SIGTERM received, shutting down gracefully");
  process.exit(0);
});

process.on("SIGINT", () => {
  logger.info("SIGINT received, shutting down gracefully");
  process.exit(0);
});

startServer();

export default app;
