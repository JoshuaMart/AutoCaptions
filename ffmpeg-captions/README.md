# FFmpeg Captions API

TypeScript REST API for generating video captions using FFmpeg and ASS subtitles.

## Features

- Caption generation with different style presets
- Support for 9:16 videos (vertical format)
- Advanced style customization (font, color, position, etc.)
- Automatic video file validation
- Support for popular Google Fonts
- Automatic cleanup of temporary files

## Prerequisites

- **Node.js** (v22 or higher)
- **npm** or **yarn**
- **FFmpeg** (required for audio extraction and metadata)
  - macOS: `brew install ffmpeg`
  - Ubuntu: `sudo apt update && sudo apt install ffmpeg`
  - Windows: Download from [ffmpeg.org](https://ffmpeg.org/download.html)

## Installation

1. Clone the repository and navigate to the `ffmpeg-captions` directory
2. Install dependencies:
   ```bash
   npm install
   ```

3. Copy the environment configuration:
   ```bash
   cp .env.example .env
   ```

4. Configure your environment variables in the `.env` file

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `PORT` | Server port | `3001` |
| `NODE_ENV` | | `development` |
| `MAX_FILE_SIZE` | Maximum file size in bytes | `524288000` |
| `UPLOAD_DIR` | Upload files folder | `./uploads` |
| `TEMP_DIR` | Transition folder | `./temp` |
| `LOG_LEVEL` | Log message level | `info` |
| `LOG_FILE` | File where logs are stored | `./logs/captions.log` |

## Endpoints

### GET /api/captions/health
Service health check.

**Response:**
```json
{
  "success": true,
  "message": "FFmpeg Captions Service is running",
  "timestamp": "2025-06-07T19:05:15.430Z"
}
```

### GET /api/captions/presets
Lists all available presets.

**Response:**
```json
{
  "success": true,
  "presets": [
    {
      "name": "custom",
      "displayName": "Custom",
      "description": "Fully customizable caption style with all available parameters"
    }
  ]
}
```

### GET /api/captions/presets/:name
Details of a specific preset with customizable parameters.

**Response:**
```json
{
  "success": true,
  "preset": {
    "name": "custom",
    "displayName": "Custom",
    "description": "Fully customizable caption style with all available parameters",
    "defaults": {
      "fontFamily": "Inter",
      "fontSize": 80,
      "fontWeight": 700,
      "uppercase": false,
      "textColor": "FFFFFF",
      "outlineColor": "000000",
      "outlineWidth": 4,
      "activeWordColor": "FFFF00",
      "activeWordOutlineWidth": 4,
      "position": "center",
      "positionOffset": 300,
      "backgroundColor": "000000",
      "backgroundOpacity": 0,
      "activeWordBackgroundColor": "FF6B35",
      "activeWordBackgroundOpacity": 80
    },
    "customizable": [
      {
        "key": "fontFamily",
        "type": "font",
        "label": "Font Family"
      },
      {
        "key": "fontSize",
        "type": "number",
        "label": "Font Size",
        "min": 40,
        "max": 150
      },
      {
        "key": "fontWeight",
        "type": "number",
        "label": "Font Weight",
        "min": 100,
        "max": 900
      },
      {
        "key": "activeWordBackgroundColor",
        "type": "color",
        "label": "Active Word Background Color"
      },
      {
        "key": "activeWordBackgroundOpacity",
        "type": "number",
        "label": "Active Word Background Opacity (%)",
        "min": 0,
        "max": 100
      }
    ]
  }
}
```

### GET /api/captions/fonts
Lists all available fonts.

**Optional parameters:**
- `category`: Filter by category (sans-serif, display, etc.)

**Response:**
```json
{
  "success": true,
  "fonts": [
    {
      "family": "Arial Black",
      "variants": ["400"],
      "category": "sans-serif"
    },
    {
      "family": "Inter",
      "variants": ["100", "200", "300", "400", "500", "600", "700", "800", "900"],
      "category": "sans-serif"
    }
  ],
  "categories": ["sans-serif", "display"]
}
```

### GET /api/captions/fonts/:family/variants
Get available font variants (weights) for a specific font family.

**Parameters:**
- `family`: Font family name (e.g., "Montserrat", "Inter")

**Response:**
```json
{
  "success": true,
  "family": "Montserrat",
  "variants": [
    {
      "name": "Thin",
      "weight": 100,
      "style": "normal"
    },
    {
      "name": "Light",
      "weight": 300,
      "style": "normal"
    },
    {
      "name": "Regular",
      "weight": 400,
      "style": "normal"
    },
    {
      "name": "Medium",
      "weight": 500,
      "style": "normal"
    },
    {
      "name": "Semi Bold",
      "weight": 600,
      "style": "normal"
    },
    {
      "name": "Bold",
      "weight": 700,
      "style": "normal"
    },
    {
      "name": "Extra Bold",
      "weight": 800,
      "style": "normal"
    },
    {
      "name": "Black",
      "weight": 900,
      "style": "normal"
    }
  ]
}
```

### POST /api/captions/generate
Generates a video with embedded captions.

**Parameters:**
- `video`: Video file (multipart/form-data)
- `data`: JSON containing configuration (can be in body or form-data)

**JSON format:**
```json
{
  "preset": "custom",
  "customStyle": {
    "fontSize": 90,
    "fontWeight": 800,
    "activeWordColor": "FF0000",
    "activeWordBackgroundColor": "FFFF00",
    "activeWordBackgroundOpacity": 70
  },
  "transcriptionData": {
    "success": true,
    "transcription": {
      "captions": [
        {
          "text": "Hello",
          "startInSeconds": 0.12,
          "endInSeconds": 0.8
        },
        {
          "text": "world",
          "startInSeconds": 0.8,
          "endInSeconds": 1.0
        }
      ],
      "duration": 10.5,
      "language": "en",
      "metadata": {
        "service": "whisper-cpp",
        "model": "medium",
        "timestamp": "2025-06-07T19:05:15.430Z"
      }
    },
    "processingTime": 87795
  }
}
```

**Response:**
- Success: Video file streaming (Content-Type: video/mp4)
- Error: JSON with error details

## File Validation

The API automatically validates:
- 9:16 video format (vertical)
- Maximum duration of 3 minutes
- Maximum size of 500MB
- Supported formats: MP4, MOV, AVI, MKV, WebM

## Preset Structure

Presets are stored in `/presets/*.json` and allow defining:

```json
{
  "name": "preset-name",
  "displayName": "Display Name",
  "description": "Style description",
  "defaults": {
    "fontFamily": "Arial Black",
    "fontSize": 80,
    "textColor": "FFFFFF",
    "outlineColor": "000000",
    "outlineWidth": 5,
    "activeWordColor": "FFFF00",
    "activeWordOutlineWidth": 5,
    "position": "bottom-center",
    "marginBottom": 150,
    "marginHorizontal": 20,
    "bold": true,
    "uppercase": true
  },
  "customizable": [
    {
      "key": "fontSize",
      "type": "number",
      "label": "Size",
      "min": 40,
      "max": 120
    }
  ]
}
```

## Positioning System

The API uses a flexible positioning system with two parameters:

### Base position (`position`)
- `"top"`: Positions near the top of the screen (10% from top)
- `"center"`: Positions at the center of the screen (50% from top)
- `"bottom"`: Positions near the bottom of the screen (15% from bottom)

### Vertical offset (`positionOffset`)
Value in pixels to fine-tune the position:
- **Positive value**: Moves downward
- **Negative value**: Moves upward
- **Zero**: No offset (exact base position)

### Examples
```json
{
  "position": "center",
  "positionOffset": 300
}
// → Screen center + 300px downward (classic "bottom" style)

{
  "position": "bottom",
  "positionOffset": 100
}
// → Bottom position + 100px upward (more centered)

{
  "position": "top",
  "positionOffset": -50
}
// → Top position - 50px upward (even higher)
```

## Customizable Parameter Types

- `font`: Google Fonts selection
- `number`: Numeric value with min/max
- `color`: Hexadecimal color code (6 characters)
- `select`: Choice from predefined options
- `boolean`: true/false value

## Usage Example with curl

```bash
# Health check
curl http://localhost:3002/api/captions/health

# List presets
curl http://localhost:3002/api/captions/presets

# Generate captions
curl -X POST \
  -F "video=@video.mp4" \
  -F "data={\"preset\":\"simple\",\"transcriptionData\":{\"success\":true,\"transcription\":{\"captions\":[{\"text\":\"Hello\",\"startInSeconds\":0.5,\"endInSeconds\":1.0}]}}}" \
  http://localhost:3002/api/captions/generate \
  --output result.mp4
```

## Logs

Logs are saved in `./logs/captions.log` and include:
- HTTP requests
- Processing errors
- Performance information
- Temporary file cleanup

## Error Handling

The API automatically handles:
- Uploaded file validation
- FFmpeg errors
- Temporary file cleanup
- Processing timeouts
- Memory errors

## Security

- Helmet protection enabled
- Strict file type validation
- Upload size limitation
- Automatic temporary file cleanup
- Access logging

## Performance

- Streaming processing for large files
- Automatic cleanup after sending
- Font size optimization based on resolution
- Optimized buffer for FFmpeg (50MB)
