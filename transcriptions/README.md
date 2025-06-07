REST API service for transcribing audio and video files using OpenAI Whisper or Whisper.cpp.

## Features

- Support for both OpenAI Whisper API and local Whisper.cpp
- Audio extraction from video files using FFmpeg
- Multiple audio/video format support
- Configurable transcription options
- RESTful API with comprehensive error handling
- Automatic cleanup of temporary files

## Prerequisites

- **Node.js** (v22 or higher)
- **npm** or **yarn**
- **FFmpeg** (required for audio extraction and metadata)
  - macOS: `brew install ffmpeg`
  - Ubuntu: `sudo apt update && sudo apt install ffmpeg`
  - Windows: Download from [ffmpeg.org](https://ffmpeg.org/download.html)

## Installation

1. Clone the repository and navigate to the transcriptions directory
2. Install dependencies:
   ```bash
   npm install
   ```

3. Copy the environment configuration:
   ```bash
   cp .env.example .env
   ```

4. Configure your environment variables in `.env`:
   - Set `TRANSCRIPTION_SERVICE` to either `openai-whisper` or `whisper-cpp`
   - If using OpenAI Whisper, set your `OPENAI_API_KEY`
   - Configure other settings as needed

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `TRANSCRIPTION_SERVICE` | Service to use: `openai-whisper` or `whisper-cpp` | `whisper-cpp` |
| `OPENAI_API_KEY` | OpenAI API key (required for openai-whisper) | - |
| `WHISPER_CPP_VERSION` | Whisper.cpp version to install | `1.7.5` |
| `WHISPER_MODEL` | Whisper model to use | `medium` |
| `PORT` | Server port | `3001` |
| `MAX_FILE_SIZE` | Maximum file size in bytes | `524288000` (500MB) |

## Usage

### Development

```bash
npm run dev
```

### Production

```bash
npm run build
npm start
```

## API Endpoints

### POST /api/transcribe

Transcribes an uploaded audio or video file.

**Request:**
- Method: POST
- Content-Type: multipart/form-data
- Body:
  - `file`: Audio or video file (required)
  - `service`: Transcription service (`openai-whisper` or `whisper-cpp`)
  - `language`: Language code (optional)
  - `translateToEnglish`: Boolean string (`true`/`false`)  // Not available when OpenAI Whisper is used

**Response:**
```json
{
  "success": true,
  "transcription": {
    "captions": [
      {
        "text": "Hello world",
        "startInSeconds": 0.0,
        "endInSeconds": 1.5,
        "confidence": 0.95
      }
    ],
    "duration": 10.5,
    "language": "en",
    "metadata": {
      "service": "whisper-cpp",
      "model": "medium",
      "timestamp": "2023-12-07T10:30:00.000Z"
    }
  },
  "processingTime": 5000
}
```

### GET /api/services

Returns available transcription services.

**Response:**
```json
{
  "success": true,
  "data": {
    "available": ["whisper-cpp", "openai-whisper"],
    "default": "whisper-cpp"
  }
}
```

### GET /api/health

Health check endpoint.

**Response:**
```json
{
  "success": true,
  "status": "healthy",
  "timestamp": "2023-12-07T10:30:00.000Z",
  "uptime": 3600
}
```

## Supported File Formats

- **Audio:** MP3, WAV, MP4, AAC, OGG, WebM
- **Video:** MP4, MPEG, QuickTime, AVI, WebM

## Error Handling

The API returns appropriate HTTP status codes and error messages:

- `400 Bad Request`: Invalid file, unsupported format, or missing parameters
- `413 Payload Too Large`: File size exceeds maximum limit
- `500 Internal Server Error`: Transcription processing errors

## Architecture

```
src/
├── config/           # Configuration management
├── middleware/       # Express middleware
├── routes/          # API routes
├── services/        # Business logic services
├── types/           # TypeScript type definitions
└── utils/           # Utility functions
```

## Dependencies

- **Express.js**: Web framework
- **Multer**: File upload handling
- **FFmpeg**: Audio/video processing
- **@remotion/openai-whisper**: OpenAI Whisper integration
- **@remotion/install-whisper-cpp**: Whisper.cpp integration
- **Winston**: Logging

## License

MIT
