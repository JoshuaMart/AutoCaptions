![Auto Captions](https://github.com/user-attachments/assets/68e17f05-8b26-4b19-9ae0-2bd719da77aa)

A comprehensive microservices-based solution for automatic video captioning, designed for 9:16 format videos (shorts). The system consists of three independent services that can be used together or separately.

## 🏗️ Architecture

```
Auto Captions
├── transcriptions/     # Audio/video transcription service
├── ffmpeg-captions/    # FFmpeg-based subtitle rendering
├── remotion-captions/  # Remotion-based video processing
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

## Demo

<video src="https://github.com/user-attachments/assets/91247f4d-5a5b-462a-9589-7a46a7c94118" controls></video>

## 🚀 Quick Start

### Prerequisites

- **Node.js** 22+
- **npm** or **yarn**
- **FFmpeg** (required for all services)
- **Docker** & **Docker Compose** (for containerized deployment)

### Option 1: Native Setup

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
   ```

### Option 2: Docker Deployment (recommanded)

1. **Start all services**:
   ```bash
   docker-compose up -d
   ```

2. **View logs**:
   ```bash
   docker-compose logs -f
   ```

3. **Stop services**:
   ```bash
   docker-compose down
   ```

## 🌐 API Endpoints

Once running, the services will be available at:

- **Transcriptions API**: http://localhost:3001
- **FFmpeg Captions API**: http://localhost:3002
- **Remotion Captions API**: http://localhost:3003

## 🔧 Configuration

Each service has its own `.env` configuration file. After running setup, review and customize:

- `transcriptions/.env`
- `ffmpeg-captions/.env`
- `remotion-captions/.env`

## 📊 Health Checks

All services include health check endpoints:

```bash
curl http://localhost:3001/health  # Transcriptions
curl http://localhost:3002/health  # FFmpeg Captions
curl http://localhost:3003/health  # Remotion Captions
```

## 🐳 Docker Commands

```bash
# Build and start all services
docker-compose up --build -d

# View service status
docker-compose ps

# View logs for specific service
docker-compose logs transcriptions
docker-compose logs ffmpeg-captions
docker-compose logs remotion-captions
```
