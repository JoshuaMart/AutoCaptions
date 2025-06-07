#!/bin/bash

echo "🚀 Starting Transcription Service Setup..."
echo ""

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    exit 1
fi

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

# Check if FFmpeg is installed
if ! command -v ffmpeg &> /dev/null; then
    echo "❌ FFmpeg is not installed. Please install FFmpeg first."
    echo "   On macOS: brew install ffmpeg"
    echo "   On Ubuntu: sudo apt update && sudo apt install ffmpeg"
    echo "   On Windows: Download from https://ffmpeg.org/download.html"
    exit 1
fi

# Check if FFprobe is installed (usually comes with FFmpeg)
if ! command -v ffprobe &> /dev/null; then
    echo "❌ FFprobe is not installed. Please install FFmpeg (includes FFprobe)."
    exit 1
fi

echo "✅ FFmpeg and FFprobe are available"

# Remove old dependencies and install new ones
echo "📦 Installing updated dependencies..."
npm install

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo "⚙️  Creating .env file..."
    cp .env.example .env
    echo "✅ Created .env file. Please configure your settings in .env"
else
    echo "✅ .env file already exists"
fi

# Install example dependencies
echo "📦 Installing example dependencies..."
cd examples && npm install && cd ..

# Make scripts executable
chmod +x examples/test-curl.sh

echo ""
echo "✅ Setup completed!"
echo ""
echo "📋 Next steps:"
echo "1. Configure your .env file with appropriate settings"
echo "2. Start the service: npm run dev"
echo "3. Test with: node examples/test-api.js <audio-file>"
echo ""
echo "📚 Available commands:"
echo "   npm run dev     - Start development server"
echo "   npm run build   - Build for production"
echo "   npm start       - Start production server"
echo ""