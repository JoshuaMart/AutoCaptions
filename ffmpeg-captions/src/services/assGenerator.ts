import { Caption, CaptionStyle, VideoResolution } from "../types";
import { getVideoResolution } from "../utils/videoValidator";
import logger from "../utils/logger";

/**
 * Convert seconds to ASS timestamp format (h:mm:ss.cs)
 */
function secToASS(sec: number): string {
  const h = Math.floor(sec / 3600);
  const m = Math.floor((sec % 3600) / 60);
  const s = Math.floor(sec % 60);
  const cs = Math.floor((sec - Math.floor(sec)) * 100);
  return `${h}:${m.toString().padStart(2, "0")}:${s.toString().padStart(2, "0")}.${cs.toString().padStart(2, "0")}`;
}

/**
 * Convert hex color to BGR format for ASS
 */
function hexToBGR(hex: string): string {
  if (hex.length !== 6) {
    throw new Error(`Invalid hex color: ${hex}`);
  }
  const r = hex.substring(0, 2);
  const g = hex.substring(2, 4);
  const b = hex.substring(4, 6);
  return `${b}${g}${r}`;
}

/**
 * Convert opacity (0-100) to ASS alpha (00-FF)
 */
function opacityToAlpha(opacity: number): string {
  const alpha = Math.round((100 - opacity) * 2.55);
  return alpha.toString(16).padStart(2, "0").toUpperCase();
}

/**
 * Calculate margins based on style and resolution
 */
function calculateMargins(
  style: CaptionStyle,
  resolution: VideoResolution,
): { marginL: number; marginR: number; marginV: number } {
  const marginL = 20;
  const marginR = 20;

  let basePosition: number;
  switch (style.position) {
    case "top":
      basePosition = Math.floor(resolution.height * 0.9);
      break;
    case "center":
      basePosition = Math.floor(resolution.height * 0.5);
      break;
    case "bottom":
    default:
      basePosition = Math.floor(resolution.height * 0.15);
      break;
  }

  const marginV = Math.max(0, basePosition - style.positionOffset);
  return { marginL, marginR, marginV };
}

/**
 * Create styled text for a word
 */
function createStyledWord(
  text: string,
  style: CaptionStyle,
  isActive: boolean,
  fontSize: number,
): string {
  const displayText = style.uppercase ? text.toUpperCase() : text;

  if (isActive) {
    const activeColorBGR = hexToBGR(style.activeWordColor);
    const activeOutlineBGR = hexToBGR(style.activeWordOutlineColor);
    const scaleFactor = fontSize / style.fontSize;
    const activeWordSize = Math.round(style.activeWordFontSize * scaleFactor);

    let tags = `\\fs${activeWordSize}\\b${style.fontWeight}\\1c&H${activeColorBGR}&`;
    tags += `\\3c&H${activeOutlineBGR}&\\bord${style.activeWordOutlineWidth}`;

    if (style.activeWordShadowOpacity > 0) {
      const shadowColorBGR = hexToBGR(style.activeWordShadowColor);
      const shadowAlpha = opacityToAlpha(style.activeWordShadowOpacity);
      tags += `\\4c&H${shadowAlpha}${shadowColorBGR}&\\shad4`;
    } else {
      tags += `\\shad0`;
    }

    return `{${tags}}${displayText}{\\r}`;
  } else {
    const textColorBGR = hexToBGR(style.textColor);
    const outlineColorBGR = hexToBGR(style.outlineColor);

    let tags = `\\fs${fontSize}\\b${style.fontWeight}\\1c&H${textColorBGR}&`;
    tags += `\\3c&H${outlineColorBGR}&\\bord${style.outlineWidth}`;

    if (style.shadowOpacity > 0) {
      const shadowColorBGR = hexToBGR(style.shadowColor);
      const shadowAlpha = opacityToAlpha(style.shadowOpacity);
      tags += `\\4c&H${shadowAlpha}${shadowColorBGR}&\\shad2`;
    } else {
      tags += `\\shad0`;
    }

    return `{${tags}}${displayText}{\\r}`;
  }
}

/**
 * Group words into display units based on count and duration limits
 */
function createWordGroups(
  captions: Caption[],
  maxGroupDuration: number = 2500,
  maxWordsPerGroup: number = 5,
): Caption[][] {
  const groups: Caption[][] = [];
  let currentGroup: Caption[] = [];
  let groupStartTime = 0;

  for (let i = 0; i < captions.length; i++) {
    const caption = captions[i];
    
    if (currentGroup.length === 0) {
      currentGroup.push(caption);
      groupStartTime = caption.startMs;
    } else {
      const groupDuration = (caption.endMs || caption.startMs + 300) - groupStartTime;
      
      if (
        currentGroup.length >= maxWordsPerGroup ||
        groupDuration >= maxGroupDuration
      ) {
        groups.push([...currentGroup]);
        currentGroup = [caption];
        groupStartTime = caption.startMs;
      } else {
        currentGroup.push(caption);
      }
    }
  }

  if (currentGroup.length > 0) {
    groups.push(currentGroup);
  }

  return groups;
}

/**
 * Generate dialogue entries for a word group
 */
interface DialogueEntry {
  startMs: number;
  endMs: number;
  text: string;
}

function generateGroupDialogues(
  group: Caption[],
  groupIndex: number,
  totalGroups: number,
  nextGroupStartMs: number | null,
  style: CaptionStyle,
  fontSize: number,
): DialogueEntry[] {
  const dialogues: DialogueEntry[] = [];
  
  // For each word in the group, create a dialogue entry
  for (let wordIndex = 0; wordIndex < group.length; wordIndex++) {
    const currentWord = group[wordIndex];
    const startMs = currentWord.startMs;
    
    // Calculate end time
    let endMs: number;
    if (wordIndex < group.length - 1) {
      // Not the last word in group: end when next word starts
      endMs = group[wordIndex + 1].startMs;
    } else {
      // Last word in group
      if (nextGroupStartMs !== null) {
        // There's a next group: end just before it starts
        endMs = Math.min(
          currentWord.endMs || currentWord.startMs + 400,
          nextGroupStartMs - 20
        );
      } else {
        // Last word of last group
        endMs = currentWord.endMs || currentWord.startMs + 400;
      }
    }
    
    // Build dialogue text with all words in group
    const dialogueText = group
      .map((word, idx) => createStyledWord(word.text, style, idx === wordIndex, fontSize))
      .join(" ");
    
    dialogues.push({
      startMs,
      endMs,
      text: dialogueText,
    });
  }
  
  return dialogues;
}

/**
 * Main ASS generation function
 */
export function generateASS(
  captions: Caption[],
  videoPath: string,
  style: CaptionStyle,
): string {
  try {
    if (!captions || captions.length === 0) {
      throw new Error("No captions provided");
    }

    const resolution = getVideoResolution(videoPath);
    const margins = calculateMargins(style, resolution);

    // Calculate font size based on resolution
    const scaleFactor = Math.min(
      resolution.width / 1080,
      resolution.height / 1920,
    );
    const adjustedFontSize = Math.round(style.fontSize * scaleFactor);

    // Prepare style values
    const textColorBGR = hexToBGR(style.textColor);
    const outlineColorBGR = hexToBGR(style.outlineColor);
    const hasLineBackground = style.backgroundOpacity > 0;
    const borderStyle = hasLineBackground ? 4 : 1;
    const backColour = hasLineBackground
      ? `&H${opacityToAlpha(style.backgroundOpacity)}${hexToBGR(style.backgroundColor)}&`
      : "&H0&";

    // Generate ASS header
    let ass = `[Script Info]
ScriptType: v4.00+
PlayResX: ${resolution.width}
PlayResY: ${resolution.height}

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: Default,${style.fontFamily},${adjustedFontSize},&H${textColorBGR}&,&H0&,&H${outlineColorBGR}&,${backColour},${style.fontWeight >= 700 ? 1 : 0},0,0,0,100,100,0,0,${borderStyle},${style.outlineWidth},0,2,${margins.marginL},${margins.marginR},${margins.marginV},1

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
`;

    // Create word groups using style parameters
    const wordGroups = createWordGroups(
      captions,
      style.maxGroupDuration,
      style.maxWordsPerGroup
    );

    // Generate all dialogue entries
    const allDialogues: DialogueEntry[] = [];
    
    for (let groupIdx = 0; groupIdx < wordGroups.length; groupIdx++) {
      const group = wordGroups[groupIdx];
      const nextGroupStartMs = groupIdx < wordGroups.length - 1 
        ? wordGroups[groupIdx + 1][0].startMs 
        : null;
      
      const groupDialogues = generateGroupDialogues(
        group,
        groupIdx,
        wordGroups.length,
        nextGroupStartMs,
        style,
        adjustedFontSize
      );
      
      allDialogues.push(...groupDialogues);
    }
    
    // Sort dialogues by start time to ensure proper order
    allDialogues.sort((a, b) => a.startMs - b.startMs);
    
    // Fix any remaining overlaps
    for (let i = 1; i < allDialogues.length; i++) {
      const prev = allDialogues[i - 1];
      const curr = allDialogues[i];
      
      if (prev.endMs > curr.startMs) {
        prev.endMs = curr.startMs - 10;
      }
    }
    
    // Convert to ASS format
    let dialogueCount = 0;
    for (const dialogue of allDialogues) {
      const startTime = secToASS(dialogue.startMs / 1000);
      const endTime = secToASS(dialogue.endMs / 1000);
      
      ass += `Dialogue: 0,${startTime},${endTime},Default,,0,0,0,,${dialogue.text}\n`;
      dialogueCount++;
    }
    
    return ass;
  } catch (error) {
    logger.error("Error generating ASS subtitle:", error);
    throw new Error("Failed to generate subtitle file");
  }
}