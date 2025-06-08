export interface TranscriptionRequest {
  service: "openai-whisper" | "whisper-cpp";
  language?: string;
  translateToEnglish?: boolean;
}

export interface TranscriptionResponse {
  success: boolean;
  transcription?: TranscriptionResult;
  error?: string;
  processingTime?: number;
}

export interface TranscriptionResult {
  captions: Caption[];
  duration: number;
  language?: string;
  metadata: {
    service: string;
    model: string;
    timestamp: string;
  };
}

export interface Caption {
  text: string;
  startMs: number;
  endMs: number;
  timestampMs?: number;
  confidence?: number;
}

export interface OpenAIWhisperOptions {
  model?: string;
  language?: string;
  response_format?: "json" | "text" | "srt" | "verbose_json" | "vtt";
  timestamp_granularities?: ("word" | "segment")[];
}

export interface WhisperCppOptions {
  model?: string;
  language?: string;
  translateToEnglish?: boolean;
  combineTokensWithinMilliseconds?: number;
}

export interface AudioExtractionResult {
  audioPath: string;
  duration: number;
  sampleRate: number;
}

export interface FileValidationResult {
  isValid: boolean;
  error?: string;
  fileInfo?: {
    size: number;
    mimeType: string;
    duration?: number;
  };
}
