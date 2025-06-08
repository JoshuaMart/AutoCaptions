import { Caption, CaptionStyle, VideoResolution } from "../types";
import { getVideoResolution } from "../utils/videoValidator";
import logger from "../utils/logger";

function secToASS(sec: number): string {
  const h = Math.floor(sec / 3600);
  const m = Math.floor((sec % 3600) / 60);
  const s = Math.floor(sec % 60);
  const cs = Math.floor((sec - Math.floor(sec)) * 100);
  return `${h}:${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}.${cs.toString().padStart(2, "0")}`;
}

function calculateEndTime(captions: Caption[], currentIndex: number): number {
  // If endInSeconds is defined and > 0, use it
  if (captions[currentIndex].endInSeconds > 0) {
    return captions[currentIndex].endInSeconds;
  }

  // Otherwise, use the beginning of the following word
  if (currentIndex < captions.length - 1) {
    return captions[currentIndex + 1].startInSeconds;
  }

  // For the last word, add a default duration
  return captions[currentIndex].startInSeconds + 0.5;
}

function groupWordsByTime(
  captions: Caption[],
  minMs: number = 1200,
): Caption[][] {
  const groups: Caption[][] = [];
  let currentGroup: Caption[] = [];
  let groupStart: number | null = null;

  for (let i = 0; i < captions.length; i++) {
    const caption = captions[i];
    const endTime = calculateEndTime(captions, i);

    if (currentGroup.length === 0) {
      groupStart = caption.startInSeconds;
      currentGroup.push({ ...caption, endInSeconds: endTime });
    } else {
      const groupDuration = (endTime - groupStart!) * 1000;

      if (groupDuration >= minMs) {
        groups.push([...currentGroup]);
        currentGroup = [{ ...caption, endInSeconds: endTime }];
        groupStart = caption.startInSeconds;
      } else {
        currentGroup.push({ ...caption, endInSeconds: endTime });
      }
    }
  }

  if (currentGroup.length > 0) {
    groups.push(currentGroup);
  }

  return groups;
}

function getAlignment(position: string): number {
  // We always use low-center alignment (2) for greater control
  // and calculate position via marginV
  return 2;
}

function hexToBGR(hex: string): string {
  // Converts hex color (RGB) to BGR format for ASS
  if (hex.length !== 6) {
    throw new Error(`Invalid hex color: ${hex}`);
  }
  const r = hex.substring(0, 2);
  const g = hex.substring(2, 4);
  const b = hex.substring(4, 6);
  return `${b}${g}${r}`;
}

function calculateMargins(
  style: CaptionStyle,
  resolution: VideoResolution,
): { marginL: number; marginR: number; marginV: number } {
  const marginL = style.marginHorizontal;
  const marginR = style.marginHorizontal;

  // Vertical position calculation based on position + offset
  // Always use bottom alignment (2), so marginV = distance from bottom
  let basePosition: number;

  switch (style.position) {
    case "top":
      // Top position: 10% from top = 90% from bottom
      basePosition = Math.floor(resolution.height * 0.9);
      break;
    case "center":
      // Center position: 50% from bottom
      basePosition = Math.floor(resolution.height * 0.5);
      break;
    case "bottom":
    default:
      // Bottom position: 15% from bottom
      basePosition = Math.floor(resolution.height * 0.15);
      break;
  }

  // Apply offset: positive = lower (less margin), negative = higher (more margin)
  const marginV = Math.max(0, basePosition - style.positionOffset);

  return { marginL, marginR, marginV };
}

export function generateASS(
  captions: Caption[],
  videoPath: string,
  style: CaptionStyle,
): string {
  try {
    const resolution = getVideoResolution(videoPath);
    const alignment = getAlignment(style.position);
    const margins = calculateMargins(style, resolution);

    // Adapt font size to resolution
    const scaleFactor = Math.min(
      resolution.width / 1080,
      resolution.height / 1920,
    );
    const adjustedFontSize = Math.round(style.fontSize * scaleFactor);

    let ass = `[Script Info]
ScriptType: v4.00+
PlayResX: ${resolution.width}
PlayResY: ${resolution.height}

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: Default,${style.fontFamily},${adjustedFontSize},&H${hexToBGR(style.textColor)},&H0,&H${hexToBGR(style.outlineColor)},&H0,${style.bold ? 1 : 0},${style.italic ? 1 : 0},0,0,100,100,0,0,1,${style.outlineWidth},0,${alignment},${margins.marginL},${margins.marginR},${margins.marginV},1

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
`;

    const wordGroups = groupWordsByTime(captions);

    for (let groupIdx = 0; groupIdx < wordGroups.length; groupIdx++) {
      const group = wordGroups[groupIdx];

      for (let wordIdx = 0; wordIdx < group.length; wordIdx++) {
        const currentWord = group[wordIdx];
        const start = secToASS(currentWord.startInSeconds);
        const end = secToASS(currentWord.endInSeconds);

        const line = group
          .map((word, idx) => {
            const text = style.uppercase ? word.text.toUpperCase() : word.text;

            if (idx === wordIdx) {
              // Current word: special color and border
              const activeColorBGR = hexToBGR(style.activeWordColor);
              const outlineColorBGR = hexToBGR(style.outlineColor);
              return `{\\bord${style.activeWordOutlineWidth}\\shad0\\fs${adjustedFontSize}\\b${style.bold ? 1 : 0}\\1c&H${activeColorBGR}&\\3c&H${outlineColorBGR}&}${text}{\\r}`;
            } else {
              // Other words: normal style
              const textColorBGR = hexToBGR(style.textColor);
              const outlineColorBGR = hexToBGR(style.outlineColor);
              return `{\\bord${style.outlineWidth}\\shad0\\fs${adjustedFontSize}\\b${style.bold ? 1 : 0}\\1c&H${textColorBGR}&\\3c&H${outlineColorBGR}&}${text}{\\r}`;
            }
          })
          .join(" ");

        ass += `Dialogue: 0,${start},${end},Default,,0,0,0,,${line}\n`;
      }
    }

    logger.info(
      `Generated ASS subtitle with ${captions.length} captions in ${wordGroups.length} groups`,
    );
    return ass;
  } catch (error) {
    logger.error("Error generating ASS subtitle:", error);
    throw new Error("Failed to generate subtitle file");
  }
}
