import { execSync } from "child_process";

interface Font {
  family: string;
  variants: string[];
  category: string;
}

interface SystemFont {
  family: string;
  style: string;
  file: string;
}

const WEIGHT_NAMES = {
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

// Expected fonts that should be installed
const EXPECTED_FONTS = [
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
  private systemFonts: SystemFont[] = [];
  private availableFonts: Font[] = [];
  private initialized = false;

  constructor() {
    this.initializeFonts();
  }

  private initializeFonts(): void {
    try {
      // Get system fonts using fc-list
      const result = execSync(
        'fc-list --format="%{family}:%{style}:%{file}\n"',
        {
          encoding: "utf8",
          timeout: 5000,
        },
      );

      this.systemFonts = result
        .split("\n")
        .filter((line) => line.trim())
        .map((line) => {
          const [family, style, file] = line.split(":");
          return {
            family: family?.trim() || "",
            style: style?.trim() || "",
            file: file?.trim() || "",
          };
        })
        .filter((font) => font.family);

      // Match expected fonts with available system fonts
      this.availableFonts = EXPECTED_FONTS.filter((expectedFont) => {
        return this.systemFonts.some(
          (systemFont) =>
            systemFont.family
              .toLowerCase()
              .includes(expectedFont.family.toLowerCase()) ||
            expectedFont.family
              .toLowerCase()
              .includes(systemFont.family.toLowerCase()),
        );
      });

      this.initialized = true;
      console.log(
        `FontService initialized with ${this.availableFonts.length} available fonts`,
      );
    } catch (error) {
      console.error("Failed to initialize fonts:", error);
      this.availableFonts = [];
      this.initialized = true;
    }
  }

  getAllFonts(): Font[] {
    if (!this.initialized) {
      this.initializeFonts();
    }
    return this.availableFonts;
  }

  getFontsByCategory(category: string): Font[] {
    return this.getAllFonts().filter((font) => font.category === category);
  }

  getFont(family: string): Font | null {
    return this.getAllFonts().find((font) => font.family === family) || null;
  }

  getFontCategories(): string[] {
    const categories = new Set(this.getAllFonts().map((font) => font.category));
    return Array.from(categories);
  }

  isValidFont(family: string): boolean {
    return this.getAllFonts().some((font) => font.family === family);
  }

  getFontVariants(family: string) {
    const font = this.getFont(family);
    if (!font) {
      return [];
    }

    return font.variants.map((variant) => {
      const weight = parseInt(variant, 10);
      return {
        name:
          WEIGHT_NAMES[weight as keyof typeof WEIGHT_NAMES] ||
          `Weight ${weight}`,
        weight,
        style: "normal",
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
      return 400;
    }

    const availableWeights = font.variants.map((v) => parseInt(v, 10));
    return availableWeights.reduce((closest, current) => {
      return Math.abs(current - desiredWeight) <
        Math.abs(closest - desiredWeight)
        ? current
        : closest;
    });
  }
}

export const fontService = new FontService();
