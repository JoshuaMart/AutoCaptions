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

// ===== SMART LINGUISTIC GROUPING FUNCTIONS =====

/**
 * Detects if a word ends with an apostrophe (contraction start)
 */
function isContractionStart(text: string): boolean {
  return /[\w]+['']$/.test(text.trim());
}

/**
 * Detects if a word starts with a lowercase letter (contraction end)
 */
function isContractionEnd(text: string): boolean {
  return /^[a-z]+/.test(text.trim());
}

/**
 * Detects if a word contains a hyphen (compound word)
 */
function isHyphenatedWord(text: string): boolean {
  return /\w+-\w+/.test(text.trim());
}

/**
 * Detects if a word ends a sentence (contains period, exclamation, etc.)
 */
function isSentenceEnd(text: string): boolean {
  return /[.!?]/.test(text.trim());
}

/**
 * Detects if a word is punctuation only (no letters)
 */
function isPunctuationOnly(text: string): boolean {
  return /^[.!?,:;]+$/.test(text.trim());
}

/**
 * Calculate the "weight" of a word for grouping purposes
 */
function getWordWeight(caption: Caption): number {
  const text = caption.text.trim();
  
  // Contraction parts (like "I'" or "m") count as 0.5 words
  if (isContractionStart(text) || isContractionEnd(text)) {
    return 0.5;
  }
  
  // Hyphenated compound words count as 1 word
  if (isHyphenatedWord(text)) {
    return 1;
  }
  
  // Regular words
  return 1;
}

/**
 * Merge separated contractions (I' + m = I'm)
 */
function mergeContractions(captions: Caption[]): Caption[] {
  const mergedCaptions: Caption[] = [];
  
  for (let i = 0; i < captions.length; i++) {
    const current = captions[i];
    const next = captions[i + 1];
    
    // If current word ends with apostrophe and next starts with lowercase
    if (next && isContractionStart(current.text) && isContractionEnd(next.text)) {
      // Merge the two words
      const mergedCaption: Caption = {
        text: current.text + next.text,
        startMs: current.startMs,
        endMs: next.endMs || next.startMs + 300,
        confidence: Math.min(current.confidence || 1, next.confidence || 1)
      };
      
      mergedCaptions.push(mergedCaption);
      i++; // Skip next word as it was merged
      
      logger.info(`Contraction merged: "${current.text}" + "${next.text}" = "${mergedCaption.text}"`);
    } else {
      mergedCaptions.push(current);
    }
  }
  
  return mergedCaptions;
}

/**
 * Smart word grouping with linguistic rules - CORRECTED VERSION
 */
function createSmartWordGroups(
  captions: Caption[],
  maxGroupDuration: number = 2500,
  maxWordsPerGroup: number = 5,
): Caption[][] {
  // Step 1: Merge contractions
  const processedCaptions = mergeContractions(captions);
  
  logger.info(`Smart grouping: ${captions.length} original words -> ${processedCaptions.length} words after merging`);
  
  const groups: Caption[][] = [];
  let currentGroup: Caption[] = [];
  let currentGroupWeight = 0;
  let groupStartTime = 0;

  for (let i = 0; i < processedCaptions.length; i++) {
    const caption = processedCaptions[i];
    const wordWeight = getWordWeight(caption);
    
    if (currentGroup.length === 0) {
      // First word of the group
      currentGroup.push(caption);
      currentGroupWeight = wordWeight;
      groupStartTime = caption.startMs;
    } else {
      // Calculate duration if we add this word
      const groupDuration = (caption.endMs || caption.startMs + 300) - groupStartTime;
      const projectedWeight = currentGroupWeight + wordWeight;
      
      // Check if we exceed limits (WITHOUT considering sentence endings)
      const exceedsLimits = (
        projectedWeight > maxWordsPerGroup ||
        groupDuration > maxGroupDuration
      );
      
      if (exceedsLimits) {
        // End current group and start new one
        groups.push([...currentGroup]);
        currentGroup = [caption];
        currentGroupWeight = wordWeight;
        groupStartTime = caption.startMs;
      } else {
        // Add to current group
        currentGroup.push(caption);
        currentGroupWeight += wordWeight;
      }
    }
    
    // AFTER adding the word, check if it ends a sentence
    if (isSentenceEnd(caption.text)) {
      // Force end of group after this word
      groups.push([...currentGroup]);
      logger.info(`Group ended by sentence terminator: "${caption.text}"`);
      
      // Reset for next group
      currentGroup = [];
      currentGroupWeight = 0;
      groupStartTime = 0;
    }
  }

  // Add final group if not empty
  if (currentGroup.length > 0) {
    groups.push(currentGroup);
  }

  logger.info(`Initial grouping completed: ${groups.length} groups created`);

  // Step 2: Post-process to merge singleton punctuation groups
  const finalGroups = mergePunctuationGroups(groups);
  
  logger.info(`After punctuation merging: ${finalGroups.length} final groups`);
  
  // Log final statistics
  finalGroups.forEach((group, idx) => {
    const totalWeight = group.reduce((sum, caption) => sum + getWordWeight(caption), 0);
    const duration = (group[group.length - 1].endMs || group[group.length - 1].startMs + 300) - group[0].startMs;
    const text = group.map(c => c.text).join(' ');
    logger.info(`Final group ${idx + 1}: "${text}" (${totalWeight} words, ${duration}ms)`);
  });

  return finalGroups;
}

/**
 * Merge singleton punctuation groups with previous group
 */
function mergePunctuationGroups(groups: Caption[][]): Caption[][] {
  if (groups.length <= 1) return groups;
  
  const mergedGroups: Caption[][] = [];
  
  for (let i = 0; i < groups.length; i++) {
    const currentGroup = groups[i];
    
    // If it's a single-word group with sentence ending and there's a previous group
    if (currentGroup.length === 1 && 
        isSentenceEnd(currentGroup[0].text) && 
        mergedGroups.length > 0) {
      
      // Merge with previous group
      const previousGroup = mergedGroups[mergedGroups.length - 1];
      previousGroup.push(...currentGroup);
      
      logger.info(`Punctuation group merged: "${currentGroup[0].text}" added to previous group`);
    } else {
      // Add group as is
      mergedGroups.push([...currentGroup]);
    }
  }
  
  return mergedGroups;
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
 * Main ASS generation function with smart linguistic grouping
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

    // Use smart linguistic grouping
    const wordGroups = createSmartWordGroups(
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
    
    logger.info(`ASS generated with ${dialogueCount} dialogues and ${wordGroups.length} smart linguistic groups`);
    
    return ass;
  } catch (error) {
    logger.error("Error generating ASS subtitle:", error);
    throw new Error("Failed to generate subtitle file");
  }
}
