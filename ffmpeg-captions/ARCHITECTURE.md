# FFmpeg Captions Service Architecture

## Overview

The FFmpeg Captions service is a TypeScript REST API that transforms transcriptions into embedded video captions. It's part of the AutoCaptions ecosystem and integrates with the transcription service.

## Project Structure

```
ffmpeg-captions/
├── src/
│   ├── config/              # Application configuration
│   │   └── index.ts
│   ├── middleware/          # Express middleware
│   │   ├── upload.ts        # File upload handling
│   │   └── errorHandler.ts  # Centralized error handling
│   ├── routes/              # Endpoint definitions
│   │   └── captions.ts      # Main API routes
│   ├── services/            # Business logic
│   │   ├── assGenerator.ts  # ASS file generation
│   │   ├── captionService.ts # Main caption service
│   │   ├── ffmpegService.ts # FFmpeg interface
│   │   ├── fontService.ts   # Google Fonts management
│   │   └── presetService.ts # Style preset management
│   ├── types/               # TypeScript definitions
│   │   └── index.ts
│   ├── utils/               # Utilities
│   │   ├── logger.ts        # Winston logging
│   │   └── videoValidator.ts # Video validation
│   └── index.ts             # Application entry point
├── presets/                 # Preset JSON files
│   └── simple.json
├── examples/                # Example and test scripts
│   ├── test-api.sh
│   ├── test-node.js
│   └── package.json
├── temp/                    # Temporary files
├── logs/                    # Log files
└── uploads/                 # Temporary uploaded files
```

## Processing Flow

### 1. Upload and Validation
```
Client → Multer → VideoValidator → FileSystem
```
- 9:16 format validation
- Duration verification (≤ 3min)
- Size control (≤ 500MB)

### 2. Style Configuration
```
Request → PresetService → StyleMerger → Validation
```
- Load requested preset
- Merge with customizations
- Parameter validation

### 3. Subtitle Generation
```
Captions → ASS Generator → Temp File → FFmpeg → Output Video/Preview
```
- Convert timestamps to ASS format
- Group words by line
- Apply styles (colors, fonts, positions)
- Embed with FFmpeg (video) or extract frame (preview)

### 4. Streaming and Cleanup
```
Output Video → HTTP Stream → Client
                ↓
            Cleanup Service
```

## Main Services

### CaptionService
**Responsibility:** Complete process orchestration
- Input data validation
- Service coordination
- Error handling
- Resource cleanup

### ASS Generator
**Responsibility:** ASS subtitle file generation
- Timestamp conversion
- Intelligent word grouping
- Visual style application
- Video resolution adaptation

### FFmpegService
**Responsibility:** FFmpeg interface
- Temporary ASS file creation
- FFmpeg command execution for video processing
- FFmpeg command execution for preview frame generation
- Buffer management for large files
- Automatic cleanup

### PresetService
**Responsibility:** Predefined style management
- Dynamic preset loading
- Customization validation
- Configuration merging

### FontService
**Responsibility:** Font management
- Popular Google Fonts listing
- Font name validation
- Categorization

## Error Handling

### Error Levels
1. **Validation**: Format, size, duration errors
2. **Processing**: FFmpeg errors, corrupted files
3. **System**: I/O errors, memory, disk space

### Recovery Strategies
- **No retry**: Fast failure to avoid overload
- **Systematic cleanup**: Temporary file deletion
- **Detailed logging**: Complete operation traceability

## Extensibility

### Adding New Presets
1. Create a JSON file in `/presets/`
2. Restart the service (automatic loading)
3. Preset is immediately available via API

### New Style Parameters
1. Extend the `CaptionStyle` interface
2. Update the `ASS Generator`
3. Add validation in `PresetService`

### Supporting New Formats
1. Extend allowed MIME types
2. Update validation
3. Test FFmpeg compatibility

## AutoCaptions Integration

### Preview Feature
The preview endpoint allows users to:
- Generate a single frame with captions at any timestamp
- Test caption styling before processing the full video
- Provide instant visual feedback in UI applications
- Validate caption positioning and appearance

**Preview Process:**
1. Upload video file and caption configuration
2. Specify timestamp via query parameter
3. Generate ASS subtitle file
4. Extract single frame with FFmpeg using `-ss` and `-vframes 1`
5. Apply subtitle overlay and return PNG image
6. Automatic cleanup of temporary files

### With Transcription Service
- Standardized exchange format (JSON)
- Compatible timestamps
- Preserved metadata

### With Web Service
- Standard REST API
- Unified error handling
- Real-time UI streaming

## Monitoring and Observability

### Structured Logs
```
[timestamp] [level] message
2025-06-07T19:05:15.430Z [INFO]: Processing caption generation request for file: video.mp4
2025-06-07T19:05:18.123Z [INFO]: Generated ASS subtitle with 45 captions in 8 groups
2025-06-07T19:05:22.456Z [INFO]: Video processing completed in 7033ms. Output size: 15728640 bytes
```

### Business Metrics
- Number of videos processed per hour
- File size distribution
- Average processing time per video minute
- Usage rate of different presets

### Health Check
- Endpoint: `GET /api/captions/health`
- Checks: FFmpeg available, accessible directories
- Timeout: 5 seconds
