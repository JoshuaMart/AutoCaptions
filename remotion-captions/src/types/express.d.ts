// Global type definitions
declare global {
  namespace Express {
    interface Request {
      uploadId?: string;
    }
  }
}

export {};
