![Auto Captions](./assets/banner.png)

A comprehensive microservices-based solution for automatic video captioning, designed for 9:16 format videos (shorts). The system consists of four independent services that can be used together or separately, with a modern web interface for easy interaction.

## 🏗️ Architecture

```
Auto Captions
├── transcriptions/     # Audio/video transcription service
├── ffmpeg-captions/    # FFmpeg-based subtitle rendering
├── remotion-captions/  # Remotion-based video processing
├── web/                # Web interface for user interaction
├── setup.sh            # Global setup script
└── docker-compose.yml  # Docker orchestration
```

## 📦 Services Overview

### 🎤 Transcriptions Service
- **Port**: 3001
- **Purpose**: Extract audio from video/audio files and generate transcriptions
- **Technology**: TypeScript, OpenAI Whisper, Whisper.cpp
- **Documentation**: [`transcriptions/README.md`](transcriptions/README.md)

### 🎬 FFmpeg Captions Service
- **Port**: 3002
- **Purpose**: Generate captioned videos using FFmpeg with ASS subtitle styling
- **Technology**: TypeScript, FFmpeg, ASS subtitles
- **Documentation**: [`ffmpeg-captions/README.md`](ffmpeg-captions/README.md)

### 🎨 Remotion Captions Service
- **Port**: 3003
- **Purpose**: Create highly customizable captioned videos with Remotion
- **Technology**: TypeScript, Remotion, React-based styling
- **Documentation**: [`remotion-captions/README.md`](remotion-captions/README.md)

### 🌐 Web Interface
- **Port**: 80
- **Purpose**: User-friendly web interface for the entire caption generation workflow
- **Technology**: PHP, JavaScript, Tailwind CSS
- **Features**: File upload, transcription editing, service management, real-time preview
- **Documentation**: [`web/README.md`](web/README.md)

## Demo

<video src="https://github.com/user-attachments/assets/91247f4d-5a5b-462a-9589-7a46a7c94118" controls></video>

## 🚀 Quick Start

### Prerequisites

- **Node.js** 22+
- **npm** or **yarn**
- **FFmpeg** (required for all services)
- **PHP 8.4+** (for web interface)
- **Docker** & **Docker Compose** (for containerized deployment)

### Option 1: Docker Deployment (Recommended)

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd AutoCaptions
   ```

2. **Start all services**:
   ```bash
   docker-compose up -d
   ```

3. **Access the web interface**:
   ```
   http://localhost:80
   ```

### Option 2: Native Setup

1. **Clone the repository**:
   ```bash
   git clone <repository-url>
   cd AutoCaptions
   ```

2. **Run global setup**:
   ```bash
   chmod +x setup.sh
   ./setup.sh
   ```

3. **Start services individually**:
   ```bash
   # Terminal 1 - Transcriptions
   cd transcriptions && npm run dev

   # Terminal 2 - FFmpeg Captions
   cd ffmpeg-captions && npm run dev

   # Terminal 3 - Remotion Captions
   cd remotion-captions && npm run dev

   # Terminal 4 - Web Interface
   cd web && php -S localhost:80
   ```

## 🌐 Access Points

Once running, the services will be available at:

- **Web Interface**: http://localhost:80 *(Primary user interface)*
- **Transcriptions API**: http://localhost:3001
- **FFmpeg Captions API**: http://localhost:3002
- **Remotion Captions API**: http://localhost:3003

## 🎮 Usage Workflows

### Via Web Interface (Recommended)

1. **Open Web Interface**: Navigate to http://localhost:80
2. **Upload Video**: Drag and drop your 9:16 video file
3. **Generate Transcription**: AI-powered speech-to-text processing
4. **Edit Captions**: Fine-tune text, timing, and formatting
5. **Choose Rendering**: Select FFmpeg (fast) or Remotion (advanced)
6. **Customize Styling**: Fonts, colors, positioning, animations
7. **Download Result**: Get your captioned video

### Via Direct API Usage

```bash
# 1. Generate transcription
curl -X POST http://localhost:3001/api/transcribe \
  -F "file=@video.mp4" \
  -F "service=whisper-cpp"

# 2. Generate captioned video (FFmpeg)
curl -X POST http://localhost:3002/api/captions/generate \
  -H "Content-Type: application/json" \
  -d '{
    "data": {...},
    "video": "video.mp4"
  }'

# 3. Generate captioned video (Remotion)
curl -X POST http://localhost:3003/render \
  -H "Content-Type: application/json" \
  -d '{
    "video": "video.mp4",
    "transcription": {...},
    "props": {...}
  }'
```

## 🔧 Configuration

### Service Configuration

Each service has its own `.env` configuration file. After running setup, review and customize:

- `transcriptions/.env` - Whisper models and API keys
- `ffmpeg-captions/.env` - FFmpeg paths and output settings
- `remotion-captions/.env` - Remotion rendering configuration

### Web Interface Configuration

The web interface provides a settings panel to configure all service URLs:

1. Click the **gear icon** in the header
2. Update service URLs:
   - **Transcriptions**: `http://localhost:3001` (Docker: `http://transcriptions:3001`)
   - **FFmpeg Captions**: `http://localhost:3002` (Docker: `http://ffmpeg-captions:3002`)
   - **Remotion Captions**: `http://localhost:3003` (Docker: `http://remotion-captions:3003`)
3. Test connections and save

## 📊 Health Checks & Monitoring

### Service Status

All services include health check endpoints:

```bash
# Check individual services
curl http://localhost:3001/health    # Transcriptions
curl http://localhost:3002/health    # FFmpeg Captions
curl http://localhost:3003/health    # Remotion Captions
```

## 📄 License

  - `ffmpeg-captions` service is under the MIT License - see the [LICENSE](./ffmpeg-captions/LICENSE) file for details.
  - `transcriptions` service is under the MIT License - see the [LICENSE](./ffmpeg-captions/LICENSE) file for details.
  - `remotion-captions` service is under the [Remotion](https://github.com/remotion-dev) License - see the [LICENSE](./remotion-captions/LICENSE) file for details.
  - `web` service is under the MIT License - see the [LICENSE](./ffmpeg-captions/LICENSE) file for details.

## 🐛 Known bugs & Improvement to be made

- [ ] Include the Remotion service in the web service
- [ ] Convert videos to `webm` in the `remotion-captions` service rather than `h264` to avoid installing Google Chrome (and thus enable the ARM64 build)
- [ ] In the `transcriptions` service, add a fallback to whisper-cpp when openai-whisper is used but the API is down
- [ ] The captions generated by the `ffmpeg-captions` service may move during video playback.
  - [ ] [Incomplete Fix](https://github.com/JoshuaMart/AutoCaptions/commit/1f30908a74822072f54fae8f2ae0d563eead92c5)

---

**Built with ❤️**
