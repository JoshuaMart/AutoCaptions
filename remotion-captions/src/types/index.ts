export interface Caption {
  text: string;
  startMs: number;
  endMs: number;
  timestampMs: number;
}

export interface TranscriptionMetadata {
  service: string;
  model: string;
  timestamp: string;
}

export interface Transcription {
  captions: Caption[];
  duration: number;
  language: string;
  metadata: TranscriptionMetadata;
}

export interface TranscriptionResponse {
  success: boolean;
  transcription: Transcription;
  processingTime: number;
}

export interface FontConfig {
  family: string;
  weight: string;
}

export interface CaptionStyle {
  maxWidth: number;
  textColor: string;
  strokeColor: string;
  strokeWidth: number;
  activeWordColor: string;
  textPosition: string;
  textPositionOffset: number;
  wordPadding: number;
  activeWordBackgroundColor: string;
  activeWordBackgroundOpacity: number;
  activeWordBorderRadius: number;
  fontSize: number;
}

export interface RenderProps {
  src: string;
  fontConfig: FontConfig;
  captionStyle: CaptionStyle;
  captions: Caption[];
}

export interface RenderRequest {
  transcription: TranscriptionResponse;
  props: Omit<RenderProps, 'src' | 'captions'>;
}

export interface RenderResponse {
  success: boolean;
  downloadUrl: string;
  renderTime: number;
}

export interface VideoMetadata {
  codec: string;
  duration: number;
  width: number;
  height: number;
  isH264: boolean;
}

export interface UploadedFile {
  id: string;
  originalPath: string;
  processedPath: string;
  metadata: VideoMetadata;
}
