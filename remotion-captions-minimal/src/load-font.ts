import { loadFont } from "@remotion/google-fonts/Inter";

const { fontFamily, waitUntilDone } = loadFont("normal", {
  weights: ["400", "700", "900"],
});

export const TheBoldFont = fontFamily;

export const loadProjectFont = async () => {
  await waitUntilDone();
};
