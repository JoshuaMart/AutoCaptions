import { Composition, staticFile } from "remotion";
import {
  CaptionedVideo,
  calculateCaptionedVideoMetadata,
  captionedVideoSchema,
} from "./CaptionedVideo";

export const RemotionRoot: React.FC = () => {
  return (
    <Composition
      id="CaptionedVideo"
      component={CaptionedVideo}
      calculateMetadata={calculateCaptionedVideoMetadata}
      schema={captionedVideoSchema}
      width={1080}
      height={1920}
      defaultProps={{
        src: staticFile("test.mp4"),
        fontConfig: {
          family: "Inter",
          weight: "800",
        },
        captionStyle: {
          maxWidth: 0.9,
          textColor: "white",
          strokeColor: "black",
          strokeWidth: 3,
          activeWordColor: "white",
          textPosition: "bottom",
          textPositionOffset: 0,
          activeWordBackgroundColor: "#FF5700",
          activeWordBackgroundOpacity: 1,
          activeWordBorderRadius: 6,
          activeWordPadding: 8,
        },
      }}
    />
  );
};
