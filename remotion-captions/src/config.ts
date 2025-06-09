import path from "path";

export default {
  server: {
    port: process.env.PORT || 3003,
  },
  upload: {
    uploadDir: path.join(__dirname, "../remotion/public/uploads"),
    maxFileSize: 100 * 1024 * 1024, // 100MB
    allowedMimeTypes: [
      "video/mp4",
      "video/quicktime",
      "video/x-msvideo",
      "video/x-matroska",
      "video/webm",
    ],
  },
  render: {
    cleanupTimeoutMinutes: 60,
    remotionPath: path.join(__dirname, "../remotion"),
  },
  ffmpeg: {
    timeoutMs: 30000, // 30 seconds
  },
};
