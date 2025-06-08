import { GoogleFont, FontVariant } from "../types";

// Mapping of font weights to their display names
const WEIGHT_NAMES: { [key: number]: string } = {
  100: "Thin",
  200: "Extra Light",
  300: "Light",
  400: "Regular",
  500: "Medium",
  600: "Semi Bold",
  700: "Bold",
  800: "Extra Bold",
  900: "Black",
};

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
    variants: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
    category: "sans-serif",
  },
  {
    family: "Roboto",
    variants: ["100", "300", "400", "500", "700", "900"],
    category: "sans-serif",
  },
  {
    family: "Poppins",
    variants: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
    category: "sans-serif",
  },
  {
    family: "Montserrat",
    variants: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
    category: "sans-serif",
  },
  {
    family: "Open Sans",
    variants: ["300", "400", "500", "600", "700", "800"],
    category: "sans-serif",
  },
  {
    family: "Oswald",
    variants: ["200", "300", "400", "500", "600", "700"],
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
    variants: ["100", "300", "400", "700", "900"],
    category: "sans-serif",
  },
  {
    family: "Source Sans Pro",
    variants: ["200", "300", "400", "600", "700", "900"],
    category: "sans-serif",
  },
  {
    family: "Nunito",
    variants: ["200", "300", "400", "500", "600", "700", "800", "900"],
    category: "sans-serif",
  },
  {
    family: "Raleway",
    variants: ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
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

  getFontVariants(family: string): FontVariant[] {
    const font = this.getFont(family);
    if (!font) {
      return [];
    }

    return font.variants.map((variant) => {
      const weight = parseInt(variant, 10);
      return {
        name: WEIGHT_NAMES[weight] || `Weight ${weight}`,
        weight,
        style: "normal", // For now, we only support normal style
      };
    });
  }

  isValidFontWeight(family: string, weight: number): boolean {
    const font = this.getFont(family);
    if (!font) {
      return false;
    }
    return font.variants.includes(weight.toString());
  }

  getClosestFontWeight(family: string, desiredWeight: number): number {
    const font = this.getFont(family);
    if (!font) {
      return 400; // Default to regular
    }

    const availableWeights = font.variants.map((v) => parseInt(v, 10));
    
    // Find the closest weight
    return availableWeights.reduce((closest, current) => {
      return Math.abs(current - desiredWeight) < Math.abs(closest - desiredWeight)
        ? current
        : closest;
    });
  }
}

export const fontService = new FontService();
