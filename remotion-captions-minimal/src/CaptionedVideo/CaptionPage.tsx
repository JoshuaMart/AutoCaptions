import React from "react";
import {
  AbsoluteFill,
  interpolate,
  spring,
  useCurrentFrame,
  useVideoConfig,
} from "remotion";
import { fitText } from "@remotion/layout-utils";
import { makeTransform, scale, translateY } from "@remotion/animation-utils";
import { TikTokPage } from "@remotion/captions";
import { CaptionStyle } from "./index";

const DESIRED_FONT_SIZE = 120;

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

  const fittedText = fitText({
    fontFamily: fontFamily,
    text: page.text,
    withinWidth: width * captionStyle.maxWidth,
    textTransform: "uppercase",
  });

  const fontSize = Math.min(DESIRED_FONT_SIZE, fittedText.fontSize);

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
        }}
      >
        <span
          style={{
            transform: makeTransform([
              scale(interpolate(enterProgress, [0, 1], [0.8, 1])),
              translateY(interpolate(enterProgress, [0, 1], [50, 0])),
            ]),
          }}
        >
          {page.tokens.map((token) => {
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
            const padding = captionStyle.activeWordPadding ?? 8;

            return (
              <span
                key={token.fromMs}
                style={{
                  display: "inline",
                  whiteSpace: "pre",
                  color: isActive 
                    ? (hasBackground ? "white" : captionStyle.activeWordColor) 
                    : captionStyle.textColor,
                  fontWeight: isActive ? "800" : "700",
                  textTransform: "uppercase",
                  position: "relative",
                }}
              >
                {hasBackground && (
                  <span
                    style={{
                      position: "absolute",
                      top: "50%",
                      left: "50%",
                      transform: "translate(-50%, -50%)",
                      backgroundColor: captionStyle.activeWordBackgroundColor.startsWith('#') 
                        ? hexToRgba(captionStyle.activeWordBackgroundColor, backgroundOpacity)
                        : captionStyle.activeWordBackgroundColor,
                      borderRadius: `${borderRadius}px`,
                      width: `calc(100% + ${padding * 2}px)`,
                      height: `calc(100% + ${padding}px)`,
                      zIndex: -1,
                    }}
                  />
                )}
                {token.text}
              </span>
            );
          })}
        </span>
      </div>
    </AbsoluteFill>
  );
};
