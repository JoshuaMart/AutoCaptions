# Remotion Captions API

REST API for generating captioned videos using Remotion. This service takes a video file and transcription data to create a video with animated captions.

## Features

- **Video Processing**: Automatic H.264 conversion if needed
- **Caption Rendering**: Uses Remotion for high-quality caption animations
- **File Management**: Automatic cleanup of temporary files
- **Download Links**: Secure download URLs for rendered videos

## API Endpoints

### POST /render

Render a video with captions.

**Request:**
- `Content-Type: multipart/form-data`
- `video`: Video file (MP4, MOV, AVI, MKV, WebM)
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

### GET /download/:uploadId

Download rendered video.

**Response:**
- Video file with `Content-Type: video/mp4`
- `Content-Disposition: attachment`

### GET /health

Health check endpoint.

**Response:**
```json
{
  "success": true,
  "service": "remotion-captions",
  "timestamp": "2025-06-09T10:00:00.000Z"
}
```

## Transcription Format

The transcription data should follow this format:

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

## Props Configuration

Configure the visual style of captions:

```json
{
  "fontConfig": {
    "family": "Inter",
    "weight": "800"
  },
  "captionStyle": {
    "maxWidth": 0.8,
    "textColor": "white",
    "strokeColor": "black",
    "strokeWidth": 10,
    "activeWordColor": "white",
    "textPosition": "center",
    "textPositionOffset": 0,
    "wordPadding": 8,
    "activeWordBackgroundColor": "#FF5700",
    "activeWordBackgroundOpacity": 1,
    "activeWordBorderRadius": 6,
    "fontSize": 40
  }
}
```

## Installation

1. Install dependencies:
```bash
npm install
```

2. Install Remotion dependencies:
```bash
cd remotion
npm install
```

3. Build TypeScript:
```bash
npm run build
```

4. Start the server:
```bash
npm start
```

For development:
```bash
npm run dev
```

## Configuration

Environment variables:

- `PORT`: Server port (default: 3000)

## File Management

- Uploaded videos are stored in `remotion/public/uploads/{uploadId}/`
- Files are automatically cleaned up after 60 minutes
- Temporary files are removed immediately after processing

## Requirements

- **Node.js** 18+
- **FFmpeg** installed and accessible in PATH
- **Remotion** configured in the `remotion/` directory

## Error Handling

The API returns structured error responses:

```json
{
  "success": false,
  "error": "Error message"
}
```

Common error codes:
- `400`: Bad request (missing files, invalid JSON)
- `404`: File not found or expired
- `500`: Server error (processing failed)

## Example Usage

```bash
curl -X POST http://localhost:3000/render \
  -F "video=@input.mp4" \
  -F "transcription=$(cat transcription.json)" \
  -F "props=$(cat props.json)"
```

## Limitations

- Max file size: 100MB
- Render timeout: 5 minutes
- Supported formats: MP4, MOV, AVI, MKV, WebM
- Files expire after 60 minutes
