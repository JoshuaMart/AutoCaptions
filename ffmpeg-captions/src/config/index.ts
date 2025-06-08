import dotenv from "dotenv";
import path from "path";

dotenv.config();

interface Config {
  server: {
    port: number;
    nodeEnv: string;
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
    port: parseInt(process.env.PORT || "3002", 10),
    nodeEnv: process.env.NODE_ENV || "development",
  },
  upload: {
    maxFileSize: parseInt(process.env.MAX_FILE_SIZE || "524288000", 10), // 500MB
    uploadDir: path.resolve(process.env.UPLOAD_DIR || "./uploads"),
    tempDir: path.resolve(process.env.TEMP_DIR || "./temp"),
    allowedMimeTypes: [
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
    file: process.env.LOG_FILE || "./logs/captions.log",
  },
};

export default config;
