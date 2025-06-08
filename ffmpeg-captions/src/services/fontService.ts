import { GoogleFont } from "../types";

// List of popular Google Fonts for captions
// This list can be extended or loaded from the Google Fonts API.
const POPULAR_FONTS: GoogleFont[] = [
  {
    family: "Arial Black",
    variants: ["400"],
    category: "sans-serif",
  },
  {
    family: "Inter",
    variants: ["400", "700", "900"],
    category: "sans-serif",
  },
  {
    family: "Roboto",
    variants: ["400", "700", "900"],
    category: "sans-serif",
  },
  {
    family: "Poppins",
    variants: ["400", "600", "700", "800", "900"],
    category: "sans-serif",
  },
  {
    family: "Montserrat",
    variants: ["400", "600", "700", "800", "900"],
    category: "sans-serif",
  },
  {
    family: "Open Sans",
    variants: ["400", "600", "700", "800"],
    category: "sans-serif",
  },
  {
    family: "Oswald",
    variants: ["400", "500", "600", "700"],
    category: "sans-serif",
  },
  {
    family: "Bebas Neue",
    variants: ["400"],
    category: "display",
  },
  {
    family: "Anton",
    variants: ["400"],
    category: "display",
  },
  {
    family: "Bangers",
    variants: ["400"],
    category: "display",
  },
  {
    family: "Impact",
    variants: ["400"],
    category: "sans-serif",
  },
  {
    family: "Lato",
    variants: ["400", "700", "900"],
    category: "sans-serif",
  },
  {
    family: "Source Sans Pro",
    variants: ["400", "600", "700", "900"],
    category: "sans-serif",
  },
  {
    family: "Nunito",
    variants: ["400", "600", "700", "800", "900"],
    category: "sans-serif",
  },
  {
    family: "Raleway",
    variants: ["400", "600", "700", "800", "900"],
    category: "sans-serif",
  },
];

export class FontService {
  getAllFonts(): GoogleFont[] {
    return POPULAR_FONTS;
  }

  getFontsByCategory(category: string): GoogleFont[] {
    return POPULAR_FONTS.filter((font) => font.category === category);
  }

  getFont(family: string): GoogleFont | null {
    return POPULAR_FONTS.find((font) => font.family === family) || null;
  }

  getFontCategories(): string[] {
    const categories = new Set(POPULAR_FONTS.map((font) => font.category));
    return Array.from(categories);
  }

  isValidFont(family: string): boolean {
    return POPULAR_FONTS.some((font) => font.family === family);
  }
}

export const fontService = new FontService();
