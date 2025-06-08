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
  textColor: string; // hex without #
  outlineColor: string; // hex without #
  outlineWidth: number;
  activeWordColor: string; // hex without #
  activeWordOutlineWidth: number;
  position: "top" | "center" | "bottom";
  positionOffset: number; // vertical offset in pixels (+ down, - up)
  marginHorizontal: number;
  bold: boolean;
  italic?: boolean;
  uppercase: boolean;
  // Futurs paramètres
  backgroundColor?: string;
  backgroundOpacity?: number;
  shadow?: boolean;
  animation?: "none" | "fade" | "slide";
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

export interface GoogleFont {
  family: string;
  variants: string[];
  category: string;
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
