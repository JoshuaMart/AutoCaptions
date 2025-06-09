# Remotion Captions API Architecture

## Overview

The Remotion Captions API is a REST service that generates captioned videos by combining uploaded video files with transcription data using Remotion's rendering capabilities.

## Architecture

```
remotion-captions/
├── src/                          # API REST TypeScript
│   ├── controllers/              # Request handlers
│   ├── middleware/               # Express middleware
│   ├── services/                 # Business logic
│   ├── types/                    # TypeScript definitions
│   └── utils/                    # Utility functions
├── remotion/                     # Remotion project
│   ├── src/                      # Remotion components
│   └── public/uploads/           # Upload storage
└── package.json                  # API dependencies
```

## Data Flow

1. **Upload**: Client uploads video + transcription + props
2. **Processing**: Video is checked and converted to H.264 if needed
3. **Props Generation**: Create props.json with correct file paths
4. **Rendering**: Execute Remotion render command
5. **Response**: Return download URL for rendered video
6. **Cleanup**: Schedule automatic file cleanup

## Services

### VideoService
- **Purpose**: Handle video file processing and conversion
- **Key Methods**:
  - `getVideoMetadata()`: Extract video codec information using ffprobe
  - `convertToH264()`: Convert non-H.264 videos using ffmpeg
  - `processUploadedVideo()`: Complete video processing pipeline

### RenderService
- **Purpose**: Orchestrate Remotion rendering
- **Key Methods**:
  - `renderVideo()`: Execute full render pipeline
  - `generatePropsFile()`: Create props.json with correct paths
  - `executeRender()`: Run Remotion CLI command
  - `scheduleCleanup()`: Automatic file cleanup

## File Management

### Upload Flow
```
1. File uploaded to: remotion/public/uploads/temp/{uuid}.ext
2. Moved to: remotion/public/uploads/{uploadId}/original.mp4
3. Processed to: remotion/public/uploads/{uploadId}/video.mp4
4. Props created: remotion/public/uploads/{uploadId}/props.json
5. Rendered to: remotion/public/uploads/{uploadId}/output.mp4
```

### Cleanup Strategy
- **Immediate**: Temporary files cleaned after processing
- **Scheduled**: Rendered files cleaned after 60 minutes
- **Periodic**: Hourly cleanup of expired uploads
- **Error Handling**: Cleanup on processing failures

## Integration Pointsc

### With Transcription Service
- Accepts transcription JSON from the transcription service
- Uses caption timing data for Remotion animation
- Validates transcription format and completeness

### With Remotion
- Generates props.json compatible with existing Remotion components
- Executes render via CLI in correct working directory
- Handles render errors and timeouts

### With FFmpeg
- Video metadata extraction using ffprobe
- H.264 conversion with optimal settings
- FastStart flag for web streaming

## Error Handling

### Client Errors (4xx)
- Missing or invalid files
- Malformed JSON data
- Unsupported video formats
- File size limits exceeded

### Server Errors (5xx)
- FFmpeg processing failures
- Remotion render errors
- File system issues
- External service timeouts

## Performance Considerations

### Concurrency
- Multiple renders can run simultaneously
- Each upload gets unique directory
- No shared state between requests

### Resource Management
- Configurable file size limits
- Render timeouts prevent hanging processes
- Automatic cleanup prevents disk usage growth

### Scalability
- Stateless API design
- File-based communication with Remotion
- Easy horizontal scaling potential

## Security

### File Validation
- MIME type checking with fallback to extension
- File size limits
- Path traversal prevention

### Cleanup
- Automatic file expiration
- Secure file deletion
- No permanent storage of user content

## Configuration

### Environment Variables
- `PORT`: API server port
- Custom timeout and size limits
- Development vs production settings

### Static Configuration
- Allowed MIME types
- FFmpeg conversion settings
- Remotion CLI parameters
