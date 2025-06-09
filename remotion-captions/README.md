# Remotion Captions - Minimal


## ✨ Features

- ✅ **TikTok-style captions** with word-by-word highlighting
- ✅ **Background highlight effects** with customizable colors and styling
- ✅ **Flexible positioning** - top, center, or bottom with custom offsets
- ✅ **Google Fonts integration** with dynamic loading
- ✅ **Fully customizable styling** via JSON props
- ✅ **Smooth animations** without layout shifts
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
    "family": "Inter",
    "weight": "800"
  },
  "captionStyle": {
    "maxWidth": 0.9,
    "textColor": "white",
    "strokeColor": "black",
    "strokeWidth": 3,
    "activeWordColor": "white",
    "textPosition": "bottom",
    "textPositionOffset": 0,
    "activeWordBackgroundColor": "#FF5700",
    "activeWordBackgroundOpacity": 1,
    "activeWordBorderRadius": 6,
    "wordPadding": 8,
    "fontSize": 80
  }
}
```

### Font Configuration

| Property | Type | Description | Example |
|----------|------|-------------|---------|
| `family` | string | Google Font family name | `"Inter"`, `"Montserrat"`, `"Roboto"` |
| `weight` | string | Font weight | `"400"`, `"700"`, `"800"`, `"900"` |

### Caption Styling

| Property | Type | Description | Default |
|----------|------|-------------|---------|
| `maxWidth` | number | Max width as % of video width (0.1-1.0) | `0.9` |
| `textColor` | string | Color of caption text | `"white"` |
| `strokeColor` | string | Color of text outline/border | `"black"` |
| `strokeWidth` | number | Width of text outline in pixels | `3` |
| `activeWordColor` | string | Color of currently active word | `"white"` |
| `textPosition` | string | Caption position: `"top"`, `"center"`, `"bottom"` | `"bottom"` |
| `textPositionOffset` | number | Position offset in pixels (positive/negative) | `0` |

### Background Highlight Effects

| Property | Type | Description | Default |
|----------|------|-------------|---------|
| `activeWordBackgroundColor` | string | Background color for active word | `undefined` |
| `activeWordBackgroundOpacity` | number | Background opacity (0-1) | `1` |
| `activeWordBorderRadius` | number | Border radius for background in pixels | `6` |
| `wordPadding` | number | Padding and spacing for all words in pixels | `8` |

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
    "endMs": 500,
    "timestampMs": 250
  },
  {
    "text": " world",
    "startMs": 500,
    "endMs": 1000,
    "timestampMs": 750
  }
]
```

**Important**: Include leading spaces in the `text` field for proper word separation.

## 📱 Popular Google Fonts for Captions

| Font Family | Best Weights | Style | Perfect For |
|-------------|--------------|-------|-------------|
| **Inter** | 600, 700, 800 | Modern, clean | TikTok-style highlights |
| **Montserrat** | 600, 700, 900 | Versatile, bold | Instagram content |
| **Oswald** | 400, 500, 600 | Condensed, impactful | Sports, action videos |
| **Roboto** | 500, 700, 900 | Clean, readable | Educational content |
| **Poppins** | 600, 700, 800 | Friendly, rounded | Lifestyle, vlog content |
| **Bebas Neue** | 400 | Bold, uppercase | Dramatic, cinematic |
| **Anton** | 400 | Extra bold, condensed | Headlines, impact |

## 🎯 Tips & Best Practices

### Background Highlights
- **Use high contrast colors** for active word backgrounds
- **`wordPadding` controls both spacing and background size** - start with 8-12px
- **Rounded corners (6-12px)** look more modern than sharp edges
- **Full opacity** usually works better than transparency for readability

### Font Selection
- **Bold weights (700-900)** work best for captions with backgrounds
- **Inter and Montserrat** are proven choices for highlight effects
- **Sans-serif fonts** are more readable on video

### Colors
- **White text + colored background** provides maximum contrast
- **Bright backgrounds** (#FF5700, #E91E63, #2196F3) grab attention
- **Test on different video backgrounds** to ensure readability

### Positioning
- Use **negative offsets** to move captions away from UI elements
- **Center position** works best for landscape videos
- **Bottom position** is ideal for TikTok/Instagram format
- Test different offsets to find the perfect placement

### Sizing
- `maxWidth: 0.9` (90%) prevents text overflow
- `strokeWidth: 2-4px` for modern look with backgrounds
- `wordPadding: 8-16px` for comfortable spacing

## 🏗️ Project Structure

```
remotion-captions-minimal/
├── src/
│   ├── CaptionedVideo/
│   │   ├── index.tsx         # Main component with font loading
│   │   └── CaptionPage.tsx   # Caption rendering with highlight effects
│   ├── Root.tsx               # Composition setup
│   └── index.ts               # Entry point
├── public/
│   └── tmp/
│       ├── test.mp4          # Sample video
│       └── test.json         # Sample captions
├── props.json                 # Default props with highlight config
└── package.json               # Dependencies
```

## 🎬 Caption Timing & Animation

### Automatic Grouping
Captions are automatically grouped using TikTok-style logic:
- Words within 1200ms are grouped together
- Smart page breaks for readability
- Smooth transitions between caption groups

## 🚀 Advanced Usage

### Custom Props via CLI
```bash
# Use custom props file
npx remotion render CaptionedVideo output.mp4 --props=./custom-props.json

# Override props inline
npx remotion render CaptionedVideo output.mp4 --props='{"captionStyle":{"activeWordBackgroundColor":"#FF0000"}}'
```

### Multiple Compositions
Add different styles for different platforms in `Root.tsx`:
```typescript
// TikTok version with orange highlights
<Composition id="TikTokVideo" /* ... */ />

// YouTube version with gold highlights
<Composition id="YouTubeVideo" /* ... */ />
```

## 📄 License

MIT License - Use freely for personal and commercial projects.

---

**Made with ❤️ using [Remotion](https://remotion.dev)**
