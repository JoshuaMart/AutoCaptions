import dotenv from "dotenv";
import path from "path";

dotenv.config();

type WhisperModel = "medium" | "tiny" | "tiny.en" | "base" | "base.en" | "small" | "small.en" | "medium.en" | "large-v1" | "large-v2" | "large-v3" | "large-v3-turbo";

interface Config {
  server: {
    port: number;
    nodeEnv: string;
  };
  transcription: {
    service: "openai-whisper" | "whisper-cpp";
    whisperCppVersion: string;
    whisperModel: WhisperModel;
  };
  openai: {
    apiKey?: string;
  };
  upload: {
    maxFileSize: number;
    uploadDir: string;
    tempDir: string;
    allowedMimeTypes: string[];
  };
  logging: {
    level: string;
    file: string;
  };
}

const config: Config = {
  server: {
    port: parseInt(process.env.PORT || "3001", 10),
    nodeEnv: process.env.NODE_ENV || "development",
  },
  transcription: {
    service:
      (process.env.TRANSCRIPTION_SERVICE as "openai-whisper" | "whisper-cpp") ||
      "whisper-cpp",
    whisperCppVersion: process.env.WHISPER_CPP_VERSION || "1.7.5",
    whisperModel: (process.env.WHISPER_MODEL as WhisperModel) || "medium",
  },
  openai: {
    apiKey: process.env.OPENAI_API_KEY,
  },
  upload: {
    maxFileSize: parseInt(process.env.MAX_FILE_SIZE || "524288000", 10), // 500MB
    uploadDir: path.resolve(process.env.UPLOAD_DIR || "./uploads"),
    tempDir: path.resolve(process.env.TEMP_DIR || "./temp"),
    allowedMimeTypes: [
      "audio/mpeg",
      "audio/wav",
      "audio/mp4",
      "audio/aac",
      "audio/ogg",
      "audio/webm",
      "video/mp4",
      "video/mpeg",
      "video/quicktime",
      "video/x-msvideo",
      "video/webm",
      "video/x-matroska",
      // Fallback for generic types that we'll detect by extension
      "application/octet-stream",
    ],
  },
  logging: {
    level: process.env.LOG_LEVEL || "info",
    file: process.env.LOG_FILE || "./logs/transcription.log",
  },
};

// Validation
if (
  config.transcription.service === "openai-whisper" &&
  !config.openai.apiKey
) {
  throw new Error(
    "OPENAI_API_KEY is required when using openai-whisper service",
  );
}

export default config;
