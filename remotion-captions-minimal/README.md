# Remotion Captions - Minimal

A minimal, production-ready Remotion project for generating videos with fully customizable captions, flexible positioning, and Google Fonts support.

## ✨ Features

- ✅ **TikTok-style captions** with word-by-word highlighting
- ✅ **Flexible positioning** - top, center, or bottom with custom offsets
- ✅ **Google Fonts integration** with dynamic loading
- ✅ **Fully customizable styling** via JSON props
- ✅ **Minimal codebase** - only essential files

## 🚀 Quick Start

### 1. Installation

```bash
cd remotion-captions-minimal
npm install
```

### 2. Prepare your files

Place your files in the `public/` folder:
- **Video**: `public/your-video.mp4`
- **Captions**: `public/your-video.json` (same name, .json extension)

### 3. Configure and render

```bash
npx remotion render CaptionedVideo output.mp4 --props=./props.json
```

## 📋 Props Configuration

### Basic Structure

```json
{
  "src": "public/your-video.mp4",
  "fontConfig": {
    "family": "Montserrat",
    "weight": "900"
  },
  "captionStyle": {
    "maxWidth": 0.9,
    "textColor": "white",
    "strokeColor": "black",
    "strokeWidth": 20,
    "activeWordColor": "orange",
    "textPosition": "bottom",
    "textPositionOffset": 0
  }
}
```

### Font Configuration

| Property | Type | Description | Example |
|----------|------|-------------|---------|
| `family` | string | Google Font family name | `"Inter"`, `"Montserrat"`, `"Roboto"` |
| `weight` | string | Font weight | `"400"`, `"700"`, `"900"` |

### Caption Styling

| Property | Type | Description | Default |
|----------|------|-------------|---------|
| `maxWidth` | number | Max width as % of video width (0.1-1.0) | `0.9` |
| `textColor` | string | Color of caption text | `"white"` |
| `strokeColor` | string | Color of text outline/border | `"black"` |
| `strokeWidth` | number | Width of text outline in pixels | `20` |
| `activeWordColor` | string | Color of currently active word | `"orange"` |
| `textPosition` | string | Caption position: `"top"`, `"center"`, `"bottom"` | `"bottom"` |
| `textPositionOffset` | number | Position offset in pixels (positive/negative) | `0` |

### Text Positioning Examples

```json
{
  "captionStyle": {
    "textPosition": "bottom",
    "textPositionOffset": -100
  }
}
```

**Common use cases:**
- **TikTok/Instagram**: `"bottom"` + `textPositionOffset: -100` (avoids UI overlap)
- **YouTube Shorts**: `"center"` + `textPositionOffset: 0` (centered)

## 📁 Caption File Format

Create a JSON file with the same name as your video:

```json
[
  {
    "text": " Hello",
    "startMs": 0,
    "endMs": 500
  },
  {
    "text": " world",
    "startMs": 500,
    "endMs": 1000
  }
]
```

**Important**: Include leading spaces in the `text` field for proper word separation.

## 📱 Popular Google Fonts for Captions

| Font Family | Best Weights | Style |
|-------------|--------------|-------|
| **Montserrat** | 600, 700, 900 | Modern, clean |
| **Oswald** | 400, 500, 600 | Condensed, impactful |
| **Roboto** | 500, 700, 900 | Versatile, readable |
| **Inter** | 500, 600, 700 | Clean, professional |
| **Poppins** | 600, 700, 800 | Friendly, rounded |
| **Bebas Neue** | 400 | Bold, uppercase |
| **Anton** | 400 | Extra bold, condensed |

## 🎯 Tips & Best Practices

### Font Selection
- **Bold weights (700-900)** work best for captions
- **Condensed fonts** (Oswald, Bebas Neue) save space
- **Sans-serif fonts** are more readable on video

### Colors
- High contrast: White text + Black stroke
- Neon effects: Bright text + Dark stroke
- Brand colors: Match your brand palette

### Positioning
- Use **negative offsets** to move captions away from UI elements
- **Center position** works best for landscape videos
- **Bottom position** is ideal for TikTok/Instagram format
- Test different offsets to find the perfect placement

### Sizing
- `maxWidth: 0.9` (90%) prevents text overflow
- `strokeWidth: 15-25px` for good readability
- Larger stroke for smaller fonts

## 🏗️ Project Structure

```
remotion-captions-minimal/
├── src/
│   ├── CaptionedVideo/
│   │   ├── index.tsx         # Main component with font loading
│   │   └── CaptionPage.tsx   # Caption rendering logic
│   ├── Root.tsx               # Composition setup
│   └── index.ts               # Entry point
├── public/
│   ├── test.mp4               # Sample video
│   └── test.json              # Sample captions
├── props.json                 # Default props
└── package.json               # Dependencies
```

## 🎬 Caption Timing

Captions are automatically grouped using TikTok-style logic:
- Words within 1200ms are grouped together
- Smooth enter animations (scale + slide)
- Active word highlighting follows audio timing
- Automatic page breaks for readability

## 📄 License

MIT License - Use freely for personal and commercial projects.

---

**Made with ❤️ using [Remotion](https://remotion.dev)**
