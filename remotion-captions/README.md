# Remotion Captions API

TypeScript API for generating captioned videos using Remotion. This service takes a video file and transcription data to create video captions with customizable effects.

## ✨ Features

- 🎨 **Fully customizable styling** - colors, fonts, positioning, effects
- 🔄 **Automatic H.264 conversion** for optimal compatibility
- 🌐 **Google Fonts integration** with dynamic loading
- 🧹 **Automatic file cleanup** and management

## 🚀 Quick Start

### Installation

1. **Install API dependencies:**
```bash
npm install
```

2. **Install Remotion dependencies:**
```bash
cd remotion
npm install
cd ..
```

3. **Start the development server:**
```bash
npm run dev
```

## 📋 Examples

The `examples/` directory contains a test script to help you get started:
```
cd examples
bash test-api.sh
```

## 📡 API Endpoints

### `GET /`

Get service information and available endpoints.

**Response:**
```json
{
  "name": "Remotion Captions Service API",
  "version": "1.0.0",
  "description": "REST API for generating captioned videos with Remotion",
  "status": "running",
  "timestamp": "2025-06-09T10:00:00.000Z",
  "endpoints": {
    "render": "POST /render",
    "download": "GET /download/:uploadId",
    "health": "GET /health"
  }
}
```

### `POST /render`

Render a video with captions.

**Request:**
- `Content-Type: multipart/form-data`
- `video`: Video file (MP4, MOV, AVI, MKV, WebM, max 100MB)
- `transcription`: JSON string with transcription data
- `props`: JSON string with render configuration

**Response:**
```json
{
  "success": true,
  "downloadUrl": "http://localhost:3000/download/uuid",
  "renderTime": 5432
}
```

### `GET /download/:uploadId`

Download rendered video (expires after 60 minutes).

**Response:** Video file with `Content-Type: video/mp4`

### `GET /health`

Health check endpoint.

**Response:**
```json
{
  "success": true,
  "service": "remotion-captions",
  "timestamp": "2025-06-09T10:00:00.000Z"
}
```

## 📋 Transcription Format

The transcription data should follow this format from the transcription service:

```json
{
  "success": true,
  "transcription": {
    "captions": [
      {
        "text": "Hello",
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
    ],
    "duration": 10.5,
    "language": "english",
    "metadata": {
      "service": "openai-whisper",
      "model": "whisper-1",
      "timestamp": "2025-06-09T10:00:00.000Z"
    }
  },
  "processingTime": 1500
}
```

**Important:** Include leading spaces in the `text` field for proper word separation.

## 🎨 Props Configuration

Configure the visual style of your captions:

### Basic Structure

```json
{
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
    "textPositionOffset": -100,
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
|----------|------|-------------|----------|
| `family` | string | Google Font family name | `"Inter"`, `"Montserrat"`, `"Roboto"` |
| `weight` | string | Font weight | `"400"`, `"700"`, `"800"`, `"900"` |

### Caption Styling Options

| Property | Type | Description | Default |
|----------|------|-------------|----------|
| `maxWidth` | number | Max width as % of video width (0.1-1.0) | `0.9` |
| `textColor` | string | Color of caption text | `"white"` |
| `strokeColor` | string | Color of text outline/border | `"black"` |
| `strokeWidth` | number | Width of text outline in pixels | `3` |
| `activeWordColor` | string | Color of currently active word | `"white"` |
| `textPosition` | string | Caption position: `"top"`, `"center"`, `"bottom"` | `"bottom"` |
| `textPositionOffset` | number | Position offset in pixels (+ or -) | `0` |
| `fontSize` | number | Font size in pixels | `40` |

### Background Highlight Effects

| Property | Type | Description | Default |
|----------|------|-------------|----------|
| `activeWordBackgroundColor` | string | Background color for active word | `undefined` |
| `activeWordBackgroundOpacity` | number | Background opacity (0-1) | `1` |
| `activeWordBorderRadius` | number | Border radius for background in pixels | `6` |
| `wordPadding` | number | Padding and spacing for all words in pixels | `8` |

## 📱 Platform-Specific Presets

### TikTok/Instagram Style
```json
{
  "fontConfig": { "family": "Inter", "weight": "800" },
  "captionStyle": {
    "textPosition": "bottom",
    "textPositionOffset": -100,
    "activeWordBackgroundColor": "#FF5700",
    "fontSize": 80,
    "wordPadding": 12
  }
}
```

### YouTube Shorts Style
```json
{
  "fontConfig": { "family": "Montserrat", "weight": "700" },
  "captionStyle": {
    "textPosition": "center",
    "activeWordBackgroundColor": "#FFD700",
    "fontSize": 72,
    "wordPadding": 10
  }
}
```

### Educational/Clean Style
```json
{
  "fontConfig": { "family": "Roboto", "weight": "600" },
  "captionStyle": {
    "textPosition": "bottom",
    "textColor": "white",
    "strokeWidth": 2,
    "fontSize": 48,
    "wordPadding": 6
  }
}
```

## 🎯 Popular Google Fonts for Captions

| Font Family | Best Weights | Style | Perfect For |
|-------------|--------------|-------|-------------|
| **Inter** | 600, 700, 800 | Modern, clean | TikTok-style highlights |
| **Montserrat** | 600, 700, 900 | Versatile, bold | Instagram content |
| **Oswald** | 400, 500, 600 | Condensed, impactful | Sports, action videos |
| **Roboto** | 500, 700, 900 | Clean, readable | Educational content |
| **Poppins** | 600, 700, 800 | Friendly, rounded | Lifestyle, vlog content |
| **Bebas Neue** | 400 | Bold, uppercase | Dramatic, cinematic |
| **Anton** | 400 | Extra bold, condensed | Headlines, impact |

## 💡 Tips & Best Practices

### Background Highlights
- **Use high contrast colors** for active word backgrounds
- **`wordPadding` controls both spacing and background size** - start with 8-12px
- **Rounded corners (6-12px)** look more modern than sharp edges
- **Full opacity** usually works better than transparency for readability

### Font Selection
- **Bold weights (700-900)** work best for captions with backgrounds
- **Inter and Montserrat** are proven choices for highlight effects
- **Sans-serif fonts** are more readable on video

### Colors & Positioning
- **White text + colored background** provides maximum contrast
- **Bright backgrounds** (#FF5700, #E91E63, #2196F3) grab attention
- Use **negative offsets** to move captions away from UI elements
- **Test on different video backgrounds** to ensure readability

## 🔧 Example Usage

### Using cURL

```bash
curl -X POST http://localhost:3000/render \
  -F "video=@input.mp4" \
  -F "transcription=$(cat transcription.json)" \
  -F "props=$(cat props.json)"
```

### File Processing Flow

1. **Upload**: Video + transcription + props received
2. **Validation**: File type and format validation
3. **Conversion**: Automatic H.264 conversion if needed
4. **Processing**: Extract captions array and generate props
5. **Rendering**: Execute Remotion render with optimized settings
6. **Response**: Return download URL for rendered video
7. **Cleanup**: Automatic file cleanup after 60 minutes

### Generated Files Structure

```
public/uploads/{uploadId}/
├── original.mp4          # Original uploaded video
├── video.mp4            # Processed video (H.264)
├── video.json           # Extracted captions array
├── props.json           # Complete Remotion props
└── output.mp4           # Final rendered video
```

## ⚙️ Configuration

### Environment Variables

```bash
# Server Configuration
PORT=3003

# File Processing
MAX_FILE_SIZE_MB=100
CLEANUP_TIMEOUT_MINUTES=60

# FFmpeg
FFMPEG_TIMEOUT_MS=30000

# Development
NODE_ENV=development
```

## 🔍 Error Handling

The API returns structured error responses:

```json
{
  "success": false,
  "error": "Error message"
}
```

**Common error codes:**
- `400`: Bad request (missing files, invalid JSON, unsupported format)
- `404`: File not found or expired
- `413`: File too large (>100MB)
- `500`: Server error (processing failed, FFmpeg error, Remotion error)

## 📊 Performance & Limits

- **Max file size**: 100MB
- **Supported formats**: MP4, MOV, AVI, MKV, WebM
- **Render timeout**: 5 minutes
- **File retention**: 60 minutes
- **Concurrent renders**: Multiple uploads supported
- **Auto H.264 conversion**: Ensures compatibility

## 🚀 Deployment

### Requirements

- **Node.js** 22+
- **npm** or **yarn**
- **FFmpeg** (required for audio extraction and metadata)
  - macOS: `brew install ffmpeg`
  - Ubuntu: `sudo apt update && sudo apt install ffmpeg`
  - Windows: Download from [ffmpeg.org](https://ffmpeg.org/download.html)

### Integration with Other Services

This API works seamlessly with:
- **Transcription Service**: Provides the required transcription format
- **FFmpeg Captions**: Alternative subtitle approach
- **Frontend applications**: Via REST API

## 📄 License

This project is licensed is under the [Remotion](https://github.com/remotion-dev) License - see the [LICENSE](./remotion-captions/LICENSE) file for details.
