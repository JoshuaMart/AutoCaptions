import { useCallback, useEffect, useMemo, useState } from "react";
import {
  AbsoluteFill,
  CalculateMetadataFunction,
  cancelRender,
  continueRender,
  delayRender,
  getInputProps,
  getStaticFiles,
  OffthreadVideo,
  Sequence,
  useVideoConfig,
  watchStaticFile,
} from "remotion";
import { z } from "zod";
import { CaptionPage } from "./CaptionPage";
import { getVideoMetadata } from "@remotion/media-utils";
import { Caption, createTikTokStyleCaptions } from "@remotion/captions";
import { getAvailableFonts } from "@remotion/google-fonts";

// Dynamic font loading utility
const loadGoogleFont = async (fontFamily: string, weight: string) => {
  try {
    const availableFonts = getAvailableFonts();
    const fontInfo = availableFonts.find(
      (font) => font.fontFamily === fontFamily
    );
    
    if (!fontInfo) {
      console.warn(`Font "${fontFamily}" not found in Google Fonts. Using fallback.`);
      return { fontFamily: fontFamily, waitUntilDone: () => Promise.resolve() };
    }

    const fontModule = await fontInfo.load();
    const { fontFamily: loadedFontFamily, waitUntilDone } = fontModule.loadFont("normal", {
      weights: [weight],
      subsets: ["latin"],
    });
    
    await waitUntilDone();
    return { fontFamily: loadedFontFamily, waitUntilDone };
  } catch (error) {
    console.error(`Error loading font "${fontFamily}":`, error);
    return { fontFamily: fontFamily, waitUntilDone: () => Promise.resolve() };
  }
};

export interface CaptionStyle {
  maxWidth: number;
  textColor: string;
  strokeColor: string;
  strokeWidth: number;
  activeWordColor: string;
}

export interface FontConfig {
  family: string;
  weight: string;
}

export const captionedVideoSchema = z.object({
  src: z.string(),
  fontConfig: z.object({
    family: z.string().default("Inter"),
    weight: z.string().default("700"),
  }),
  captionStyle: z.object({
    maxWidth: z.number().min(0.1).max(1).default(0.9),
    textColor: z.string().default("white"),
    strokeColor: z.string().default("black"),
    strokeWidth: z.number().min(0).default(20),
    activeWordColor: z.string().default("orange"),
  }),
});

export type CaptionedVideoProps = z.infer<typeof captionedVideoSchema>;

export const calculateCaptionedVideoMetadata: CalculateMetadataFunction<
  CaptionedVideoProps
> = async ({ props }) => {
  const fps = 30;
  const metadata = await getVideoMetadata(props.src);

  return {
    fps,
    durationInFrames: Math.floor(metadata.durationInSeconds * fps),
  };
};

const getFileExists = (file: string) => {
  const files = getStaticFiles();
  console.log('Looking for file:', file);
  console.log('Available static files:', files.map(f => f.src));
  
  // Try both with and without 'public/' prefix
  const fileWithoutPrefix = file.replace(/^public\//, "");
  const fileExists = files.find((f) => {
    return f.src === file || f.src === fileWithoutPrefix;
  });
  
  console.log('File exists?', Boolean(fileExists));
  return Boolean(fileExists);
};

// How many milliseconds to show captions together
const SWITCH_CAPTIONS_EVERY_MS = 1200;

export const CaptionedVideo: React.FC<CaptionedVideoProps> = () => {
  const props = getInputProps<CaptionedVideoProps>();
  const { src, fontConfig, captionStyle } = props;
  
  const [subtitles, setSubtitles] = useState<Caption[]>([]);
  const [loadedFontFamily, setLoadedFontFamily] = useState<string>(fontConfig.family);
  const [handle] = useState(() => delayRender());
  const { fps } = useVideoConfig();

  const subtitlesFile = src
    .replace(/.mp4$/, ".json")
    .replace(/.mkv$/, ".json")
    .replace(/.mov$/, ".json")
    .replace(/.webm$/, ".json");

  const fetchSubtitles = useCallback(async () => {
    try {
      console.log('Loading subtitles from:', subtitlesFile);
      console.log('Loading font:', fontConfig.family, 'with weight:', fontConfig.weight);
      
      // Load font first
      const { fontFamily } = await loadGoogleFont(fontConfig.family, fontConfig.weight);
      setLoadedFontFamily(fontFamily);
      
      const res = await fetch(subtitlesFile);
      console.log('Fetch response status:', res.status);
      if (!res.ok) {
        throw new Error(`Failed to fetch ${subtitlesFile}: ${res.status} ${res.statusText}`);
      }
      const data = (await res.json()) as Caption[];
      console.log('Loaded subtitles:', data.length, 'captions');
      setSubtitles(data);
      continueRender(handle);
    } catch (e) {
      console.error('Error loading subtitles:', e);
      // Don't cancel render, just continue without subtitles
      setSubtitles([]);
      continueRender(handle);
    }
  }, [handle, subtitlesFile, fontConfig]);

  useEffect(() => {
    fetchSubtitles();

    const c = watchStaticFile(subtitlesFile, () => {
      fetchSubtitles();
    });

    return () => {
      c.cancel();
    };
  }, [fetchSubtitles, src, subtitlesFile]);

  const { pages } = useMemo(() => {
    return createTikTokStyleCaptions({
      combineTokensWithinMilliseconds: SWITCH_CAPTIONS_EVERY_MS,
      captions: subtitles ?? [],
    });
  }, [subtitles]);

  if (!getFileExists(subtitlesFile)) {
    console.log('Caption file check failed, but continuing with fetch...');
  }

  return (
    <AbsoluteFill style={{ backgroundColor: "white" }}>
      <AbsoluteFill>
        <OffthreadVideo
          style={{
            objectFit: "cover",
          }}
          src={src}
        />
      </AbsoluteFill>
      {pages.map((page, index) => {
        const nextPage = pages[index + 1] ?? null;
        const subtitleStartFrame = (page.startMs / 1000) * fps;
        const subtitleEndFrame = Math.min(
          nextPage ? (nextPage.startMs / 1000) * fps : Infinity,
          subtitleStartFrame + (SWITCH_CAPTIONS_EVERY_MS / 1000) * fps,
        );
        const durationInFrames = subtitleEndFrame - subtitleStartFrame;
        
        if (durationInFrames <= 0) {
          return null;
        }

        return (
          <Sequence
            key={index}
            from={subtitleStartFrame}
            durationInFrames={durationInFrames}
          >
            <CaptionPage page={page} captionStyle={captionStyle} fontFamily={loadedFontFamily} />
          </Sequence>
        );
      })}
    </AbsoluteFill>
  );
};
