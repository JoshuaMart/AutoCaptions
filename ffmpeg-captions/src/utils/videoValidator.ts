import { execSync } from "child_process";
import config from "../config";
import logger from "./logger";
import { VideoResolution } from "../types";

export function getVideoResolution(videoPath: string): VideoResolution {
  try {
    const probe = execSync(
      `ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0 "${videoPath}"`,
    )
      .toString()
      .trim();

    const [width, height] = probe.split(",").map(Number);

    if (!width || !height) {
      throw new Error("Could not determine video resolution");
    }

    return { width, height };
  } catch (error) {
    logger.error("Error getting video resolution:", error);
    throw new Error("Failed to get video resolution");
  }
}

export function getVideoDuration(videoPath: string): number {
  try {
    const duration = execSync(
      `ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "${videoPath}"`,
    )
      .toString()
      .trim();

    const durationSeconds = parseFloat(duration);

    if (isNaN(durationSeconds)) {
      throw new Error("Could not determine video duration");
    }

    return durationSeconds;
  } catch (error) {
    logger.error("Error getting video duration:", error);
    throw new Error("Failed to get video duration");
  }
}

export function validateVideoFormat(videoPath: string): {
  isValid: boolean;
  error?: string;
  isVertical?: boolean;
} {
  try {
    const { width, height } = getVideoResolution(videoPath);
    const duration = getVideoDuration(videoPath);

    // Check if video is vertical (9:16 ratio with some tolerance)
    const aspectRatio = width / height;
    const expectedRatio = 9 / 16;
    const tolerance = 0.1;

    const isVertical = Math.abs(aspectRatio - expectedRatio) <= tolerance;

    if (!isVertical) {
      return {
        isValid: false,
        error: `Video must be in 9:16 format. Current ratio: ${aspectRatio.toFixed(2)} (${width}x${height})`,
      };
    }

    // Check duration (max 3 minutes = 180 seconds)
    if (duration > 180) {
      return {
        isValid: false,
        error: `Video duration must be less than 3 minutes. Current duration: ${Math.round(duration)}s`,
      };
    }

    return { isValid: true, isVertical: true };
  } catch (error) {
    logger.error("Error validating video format:", error);
    return {
      isValid: false,
      error: "Could not validate video format",
    };
  }
}
