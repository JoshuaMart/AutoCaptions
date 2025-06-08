#!/bin/bash

echo "🚀 Setting up FFmpeg Captions Service"
echo "====================================="

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed. Please install Node.js 18+ first."
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

# Create necessary directories
echo ""
echo "📁 Creating directories..."
mkdir -p uploads logs temp
echo "✅ Created uploads, logs, and temp directories"

# Compile TypeScript
echo ""
echo "🔨 Compiling TypeScript..."
npm run build

if [ $? -ne 0 ]; then
    echo "❌ TypeScript compilation failed"
    exit 1
fi

echo "✅ TypeScript compilation completed"

# Make scripts executable
chmod +x examples/test-api.sh

echo ""
echo "🎉 Setup completed successfully!"
echo ""
echo "📋 Next steps:"
echo "   1. Start the service: npm run dev"
echo "   2. Test the API: ./examples/test-api.sh"
echo "   3. Check the documentation in README.md"
echo ""
echo "🌐 The service will be available at: http://localhost:3002"
echo "📚 API documentation: http://localhost:3002/api/captions"
