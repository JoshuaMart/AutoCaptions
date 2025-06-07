import { Router, Request, Response } from "express";
import { TranscriptionService } from "../services/transcriptionService";
import { FileValidator } from "../utils/fileValidator";
import { upload } from "../middleware/upload";
import { TranscriptionRequest, TranscriptionResponse } from "../types";
import logger from "../utils/logger";
import fs from "fs/promises";

const router = Router();
const transcriptionService = new TranscriptionService();

/**
 * POST /transcribe
 * Transcribes an uploaded audio or video file
 */
router.post(
  "/transcribe",
  upload.single("file"),
  async (req: Request, res: Response): Promise<void> => {
    const startTime = Date.now();
    let uploadedFile: Express.Multer.File | undefined;

    try {
      uploadedFile = req.file;

      if (!uploadedFile) {
        res.status(400).json({
          success: false,
          error: "No file uploaded",
        } as TranscriptionResponse);
        return;
      }

      // Parse request body
      const requestBody: TranscriptionRequest = {
        service: req.body.service || transcriptionService.getDefaultService(),
        language: req.body.language,
        translateToEnglish: req.body.translateToEnglish === "true",
      };

      // Validate service
      const availableServices = transcriptionService.getAvailableServices();
      if (!availableServices.includes(requestBody.service)) {
        res.status(400).json({
          success: false,
          error: `Service '${requestBody.service}' is not available. Available services: ${availableServices.join(", ")}`,
        } as TranscriptionResponse);
        return;
      }

      logger.info("Transcription request received", {
        filename: uploadedFile.originalname,
        size: uploadedFile.size,
        mimetype: uploadedFile.mimetype,
        service: requestBody.service,
      });

      // Validate file
      const validation = await FileValidator.validateFile(uploadedFile);
      if (!validation.isValid) {
        res.status(400).json({
          success: false,
          error: validation.error,
        } as TranscriptionResponse);
        return;
      }

      // Perform transcription
      const transcriptionResult = await transcriptionService.transcribeFile(
        uploadedFile.path,
        requestBody,
      );

      const processingTime = Date.now() - startTime;

      const response: TranscriptionResponse = {
        success: true,
        transcription: transcriptionResult,
        processingTime,
      };

      logger.info("Transcription request completed", {
        filename: uploadedFile.originalname,
        captionsCount: transcriptionResult.captions.length,
        duration: transcriptionResult.duration,
        processingTime: `${processingTime}ms`,
      });

      res.json(response);
    } catch (error) {
      const processingTime = Date.now() - startTime;
      const errorMessage =
        error instanceof Error ? error.message : "Unknown error";

      logger.error("Transcription request failed", {
        error: errorMessage,
        processingTime: `${processingTime}ms`,
        filename: uploadedFile?.originalname,
      });

      res.status(500).json({
        success: false,
        error: errorMessage,
        processingTime,
      } as TranscriptionResponse);
    } finally {
      // Cleanup uploaded file
      if (uploadedFile?.path) {
        try {
          await fs.unlink(uploadedFile.path);
          logger.info(`Cleaned up uploaded file: ${uploadedFile.path}`);
        } catch (cleanupError) {
          logger.warn(
            `Failed to cleanup uploaded file: ${uploadedFile.path}`,
            cleanupError,
          );
        }
      }
    }
  },
);

/**
 * GET /services
 * Returns available transcription services
 */
router.get("/services", (req: Request, res: Response) => {
  const availableServices = transcriptionService.getAvailableServices();
  const defaultService = transcriptionService.getDefaultService();

  res.json({
    success: true,
    data: {
      available: availableServices,
      default: defaultService,
    },
  });
});

/**
 * GET /health
 * Health check endpoint
 */
router.get("/health", (req: Request, res: Response) => {
  res.json({
    success: true,
    status: "healthy",
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
  });
});

export default router;
