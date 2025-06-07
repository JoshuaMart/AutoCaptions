import path from 'path';
import fs from 'fs/promises';
import {
  downloadWhisperModel,
  installWhisperCpp,
  transcribe,
  convertToCaptions,
} from '@remotion/install-whisper-cpp';
import { TranscriptionResult, WhisperCppOptions } from '../types';
import config from '../config';
import logger from '../utils/logger';

export class WhisperCppService {
  private whisperPath: string;
  private isInitialized: boolean = false;

  constructor() {
    this.whisperPath = path.join(process.cwd(), 'whisper.cpp');
  }

  /**
   * Initializes Whisper.cpp if not already done
   */
  async initialize(): Promise<void> {
    if (this.isInitialized) {
      return;
    }

    try {
      logger.info('Initializing Whisper.cpp', {
        version: config.transcription.whisperCppVersion,
        model: config.transcription.whisperModel,
        path: this.whisperPath,
      });

      // Check if whisper.cpp is already installed
      const whisperExists = await this.checkWhisperExists();
      
      if (!whisperExists) {
        logger.info('Installing Whisper.cpp...');
        await installWhisperCpp({
          to: this.whisperPath,
          version: config.transcription.whisperCppVersion,
        });
        logger.info('Whisper.cpp installation completed');
      } else {
        logger.info('Whisper.cpp already installed');
      }

      // Check if model is downloaded
      const modelExists = await this.checkModelExists(config.transcription.whisperModel);
      
      if (!modelExists) {
        logger.info(`Downloading model: ${config.transcription.whisperModel}`);
        await downloadWhisperModel({
          model: config.transcription.whisperModel,
          folder: this.whisperPath,
        });
        logger.info(`Model ${config.transcription.whisperModel} downloaded successfully`);
      } else {
        logger.info(`Model ${config.transcription.whisperModel} already exists`);
      }

      this.isInitialized = true;
      logger.info('Whisper.cpp initialization completed');
    } catch (error) {
      logger.error('Whisper.cpp initialization failed:', error);
      throw new Error(`Whisper.cpp initialization failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Transcribes audio using Whisper.cpp
   */
  async transcribe(
    audioPath: string,
    options: WhisperCppOptions = {}
  ): Promise<TranscriptionResult> {
    await this.initialize();
    
    const startTime = Date.now();
    
    try {
      logger.info('Starting Whisper.cpp transcription', { audioPath, options });

      const { transcription } = await transcribe({
        model: options.model || config.transcription.whisperModel,
        whisperPath: this.whisperPath,
        whisperCppVersion: config.transcription.whisperCppVersion,
        inputPath: audioPath,
        tokenLevelTimestamps: options.tokenLevelTimestamps ?? true,
        language: options.language,
        translateToEnglish: options.translateToEnglish,
      });

      logger.info('Whisper.cpp transcription completed, converting to captions');

      // Convert to captions format
      const { captions } = convertToCaptions({
        transcription,
        combineTokensWithinMilliseconds: options.combineTokensWithinMilliseconds || 200,
      });

      const processingTime = Date.now() - startTime;
      
      // Calculate total duration from transcription
      const duration = transcription.length > 0 
        ? Math.max(...transcription.map(t => t.timestamps.to)) / 1000 
        : 0;

      const result: TranscriptionResult = {
        captions: captions.map(caption => ({
          text: caption.text,
          startInSeconds: caption.startInSeconds,
          endInSeconds: caption.endInSeconds,
        })),
        duration,
        language: options.language,
        metadata: {
          service: 'whisper-cpp',
          model: options.model || config.transcription.whisperModel,
          timestamp: new Date().toISOString(),
        },
      };

      logger.info('Transcription completed', {
        duration: result.duration,
        captionsCount: result.captions.length,
        processingTime: `${processingTime}ms`
      });

      return result;
    } catch (error) {
      logger.error('Whisper.cpp transcription failed:', error);
      throw new Error(`Whisper.cpp transcription failed: ${error instanceof Error ? error.message : 'Unknown error'}`);
    }
  }

  /**
   * Checks if Whisper.cpp is installed
   */
  private async checkWhisperExists(): Promise<boolean> {
    try {
      const mainPath = path.join(this.whisperPath, 'main');
      const mainExePath = path.join(this.whisperPath, 'main.exe');
      
      const [mainExists, mainExeExists] = await Promise.all([
        fs.access(mainPath).then(() => true).catch(() => false),
        fs.access(mainExePath).then(() => true).catch(() => false),
      ]);
      
      return mainExists || mainExeExists;
    } catch {
      return false;
    }
  }

  /**
   * Checks if a specific model is downloaded
   */
  private async checkModelExists(model: string): Promise<boolean> {
    try {
      const modelPath = path.join(this.whisperPath, 'models', `ggml-${model}.bin`);
      await fs.access(modelPath);
      return true;
    } catch {
      return false;
    }
  }
}