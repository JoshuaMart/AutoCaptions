#!/bin/bash

echo "🚀 Starting Transcription Service Setup..."
echo "====================================="

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js first."
    exit 1
fi

echo "✅ Node.js version: $(node --version)"

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    echo "❌ npm is not installed. Please install npm first."
    exit 1
fi

echo "✅ npm version: $(npm --version)"

# Check if FFmpeg is installed
if ! command -v ffmpeg &> /dev/null; then
    echo "⚠️  FFmpeg is not installed or not in PATH."
    echo "    Please install FFmpeg:"
    echo "     - macOS: brew install ffmpeg"
    echo "     - Ubuntu: sudo apt install ffmpeg"
    echo "     - Windows: Download from https://ffmpeg.org/"
    exit 1
fi

echo "✅ FFmpeg version: $(ffmpeg -version | head -n 1)"

# Check if FFprobe is installed
if ! command -v ffprobe &> /dev/null; then
    echo "⚠️  FFprobe is not installed or not in PATH."
    echo "   FFprobe usually comes with FFmpeg installation."
    exit 1
fi

echo "✅ FFprobe is available"

# Install dependencies
echo ""
echo "📦 Installing dependencies..."
npm install

if [ $? -ne 0 ]; then
    echo "❌ Failed to install dependencies"
    exit 1
fi

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    echo ""
    echo "⚙️  Creating .env file..."
    cp .env.example .env
    echo "✅ Created .env file from template"
    echo "   You can customize it if needed"
else
    echo "✅ .env file already exists"
fi

echo ""
echo "🎉 Setup completed successfully!"
echo ""
echo "📚 Available commands:"
echo "   npm run dev     - Start development server"
echo "   npm run build   - Build for production"
echo "   npm start       - Start production server"
echo ""
echo "🌐 The service will be available at: http://localhost:3001"
