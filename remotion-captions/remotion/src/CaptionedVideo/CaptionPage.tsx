import React from "react";
import {
  AbsoluteFill,
  interpolate,
  spring,
  useCurrentFrame,
  useVideoConfig,
} from "remotion";
import { makeTransform, scale, translateY } from "@remotion/animation-utils";
import { TikTokPage } from "@remotion/captions";
import { CaptionStyle } from "./index";

const DEFAULT_FONT_SIZE = 80;

export const CaptionPage: React.FC<{
  readonly page: TikTokPage;
  readonly captionStyle: CaptionStyle;
  readonly fontFamily: string;
}> = ({ page, captionStyle, fontFamily }) => {
  const frame = useCurrentFrame();
  const { width, fps } = useVideoConfig();
  const timeInMs = (frame / fps) * 1000;

  const enterProgress = spring({
    frame,
    fps,
    config: {
      damping: 200,
    },
    durationInFrames: 5,
  });

  // Use fixed fontSize if provided, otherwise use default
  const fontSize = captionStyle.fontSize ?? DEFAULT_FONT_SIZE;
  const maxWidth = width * captionStyle.maxWidth;

  // Calculate position based on textPosition and offset
  const getPositionStyles = (): React.CSSProperties => {
    const baseStyles: React.CSSProperties = {
      justifyContent: "center",
      alignItems: "center",
      height: 150,
    };

    switch (captionStyle.textPosition) {
      case "top":
        return {
          ...baseStyles,
          top: 100 + captionStyle.textPositionOffset,
          bottom: undefined,
        };
      case "center":
        return {
          ...baseStyles,
          top: `calc(50% - 75px + ${captionStyle.textPositionOffset}px)`,
          bottom: undefined,
        };
      case "bottom":
      default:
        return {
          ...baseStyles,
          top: undefined,
          bottom: 350 - captionStyle.textPositionOffset,
        };
    }
  };

  const containerStyle = getPositionStyles();

  return (
    <AbsoluteFill style={containerStyle}>
      <div
        style={{
          fontSize,
          color: captionStyle.textColor,
          WebkitTextStroke: `${captionStyle.strokeWidth}px ${captionStyle.strokeColor}`,
          paintOrder: "stroke fill",
          transform: makeTransform([
            scale(interpolate(enterProgress, [0, 1], [0.8, 1])),
            translateY(interpolate(enterProgress, [0, 1], [50, 0])),
          ]),
          fontFamily: fontFamily,
          textTransform: "uppercase",
          fontWeight: "700",
          letterSpacing: "1px",
          lineHeight: "1.2",
          textAlign: "center",
          maxWidth: maxWidth,
          wordWrap: "break-word",
          overflowWrap: "break-word",
        }}
      >
        <span
          style={{
            transform: makeTransform([
              scale(interpolate(enterProgress, [0, 1], [0.8, 1])),
              translateY(interpolate(enterProgress, [0, 1], [50, 0])),
            ]),
            display: "inline",
          }}
        >
          {page.tokens.map((token, tokenIndex) => {
            const startRelativeToSequence = token.fromMs - page.startMs;
            const endRelativeToSequence = token.toMs - page.startMs;

            const isActive =
              startRelativeToSequence <= timeInMs &&
              endRelativeToSequence > timeInMs;

            // Helper function to convert hex color to rgba
            const hexToRgba = (hex: string, opacity: number) => {
              const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
              if (result) {
                const r = parseInt(result[1], 16);
                const g = parseInt(result[2], 16);
                const b = parseInt(result[3], 16);
                return `rgba(${r}, ${g}, ${b}, ${opacity})`;
              }
              return hex; // fallback if not hex
            };

            const hasBackground = isActive && captionStyle.activeWordBackgroundColor;
            const backgroundOpacity = captionStyle.activeWordBackgroundOpacity ?? 1;
            const borderRadius = captionStyle.activeWordBorderRadius ?? 6;
            const padding = captionStyle.wordPadding ?? 8;
            
            // Apply consistent spacing and padding to all words when background system is active
            const needsGlobalPadding = !!captionStyle.activeWordBackgroundColor;
            const wordSpacing = needsGlobalPadding ? Math.max(2, padding / 4) : 0;
            
            // Check if this token starts with a space (natural spacing)
            const startsWithSpace = token.text.startsWith(' ');
            const isFirstToken = tokenIndex === 0;

            return (
              <span
                key={token.fromMs}
                style={{
                  display: "inline-block",
                  whiteSpace: "pre-wrap",
                  color: isActive 
                    ? (hasBackground ? "white" : captionStyle.activeWordColor) 
                    : captionStyle.textColor,
                  fontWeight: isActive ? "800" : "700",
                  textTransform: "uppercase",
                  position: "relative",
                  // Apply consistent spacing to all words when background system is active
                  marginLeft: (needsGlobalPadding && !isFirstToken && !startsWithSpace) ? `${wordSpacing}px` : undefined,
                  marginRight: needsGlobalPadding ? `${wordSpacing}px` : undefined,
                  // Apply consistent padding to all words, background only on active
                  paddingLeft: needsGlobalPadding ? `${padding}px` : undefined,
                  paddingRight: needsGlobalPadding ? `${padding}px` : undefined,
                  paddingTop: needsGlobalPadding ? `${padding * 0.6}px` : undefined,
                  paddingBottom: needsGlobalPadding ? `${padding * 0.6}px` : undefined,
                  borderRadius: needsGlobalPadding ? `${borderRadius}px` : undefined,
                  // Background only on active word
                  ...(hasBackground && {
                    backgroundColor: captionStyle.activeWordBackgroundColor.startsWith('#') 
                      ? hexToRgba(captionStyle.activeWordBackgroundColor, backgroundOpacity)
                      : captionStyle.activeWordBackgroundColor,
                  }),
                }}
              >
                {token.text}
              </span>
            );
          })}
        </span>
      </div>
    </AbsoluteFill>
  );
};
