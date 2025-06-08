export interface Caption {
  text: string;
  startInSeconds: number;
  endInSeconds: number;
  confidence?: number;
}

export interface TranscriptionData {
  success: boolean;
  transcription: {
    captions: Caption[];
    duration: number | null;
    language: string;
    metadata: {
      service: string;
      model: string;
      timestamp: string;
    };
  };
  processingTime: number;
}

export interface CaptionStyle {
  fontFamily: string;
  fontSize: number;
  fontWeight: number; // 100-900 for font variants
  uppercase: boolean;
  textColor: string; // hex without #
  outlineColor: string; // hex without #
  outlineWidth: number;
  activeWordColor: string; // hex without #
  activeWordOutlineColor: string; // hex without #
  activeWordOutlineWidth: number;
  activeWordFontSize: number;
  position: "top" | "center" | "bottom";
  positionOffset: number; // vertical offset in pixels (+ down, - up)
  backgroundColor: string; // hex without #
  backgroundOpacity: number; // 0-100
  shadowColor: string; // hex without #
  shadowOpacity: number; // 0-100
  activeWordShadowColor: string; // hex without #
  activeWordShadowOpacity: number; // 0-100
}

export interface CustomizableParam {
  key: keyof CaptionStyle;
  type: "font" | "number" | "color" | "select" | "boolean";
  label: string;
  min?: number;
  max?: number;
  options?: string[];
}

export interface CaptionPreset {
  name: string;
  displayName: string;
  description: string;
  defaults: CaptionStyle;
  customizable: CustomizableParam[];
}

export interface GenerateCaptionsRequest {
  preset?: string;
  customStyle?: Partial<CaptionStyle>;
  transcriptionData: TranscriptionData;
}

export interface GenerateCaptionsResponse {
  success: boolean;
  videoPath?: string;
  error?: string;
  processingTime?: number;
  metadata?: {
    preset: string;
    style: CaptionStyle;
    timestamp: string;
  };
}

export interface GeneratePreviewResponse {
  success: boolean;
  imagePath?: string;
  error?: string;
  processingTime?: number;
  metadata?: {
    preset: string;
    style: CaptionStyle;
    timestamp: string;
    frameTimestamp: number;
  };
}

export interface GoogleFont {
  family: string;
  variants: string[];
  category: string;
}

export interface FontVariant {
  name: string;
  weight: number;
  style: 'normal' | 'italic';
}

export interface VideoResolution {
  width: number;
  height: number;
}

export interface FileValidationResult {
  isValid: boolean;
  error?: string;
  fileInfo?: {
    size: number;
    mimeType: string;
    duration?: number;
    resolution?: VideoResolution;
  };
}
