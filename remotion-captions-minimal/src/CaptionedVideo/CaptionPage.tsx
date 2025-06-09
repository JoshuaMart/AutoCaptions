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

  const containerStyle: React.CSSProperties = {
    justifyContent: "center",
    alignItems: "center",
    top: undefined,
    bottom: 350,
    height: 150,
  };

  return (
    <AbsoluteFill style={containerStyle}>
      <div
        style={{
          fontSize,
          color: captionStyle.textColor,
          WebkitTextStroke: `${captionStyle.strokeWidth}px ${captionStyle.strokeColor}`,
          paintOrder: "stroke",
          transform: makeTransform([
            scale(interpolate(enterProgress, [0, 1], [0.8, 1])),
            translateY(interpolate(enterProgress, [0, 1], [50, 0])),
          ]),
          fontFamily: fontFamily,
          textTransform: "uppercase",
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

            return (
              <span
                key={token.fromMs}
                style={{
                  display: "inline",
                  whiteSpace: "pre",
                  color: isActive ? captionStyle.activeWordColor : captionStyle.textColor,
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
