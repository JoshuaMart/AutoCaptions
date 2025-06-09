#!/bin/bash

echo "🚀 Starting Remotion Captions API..."

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    echo "❌ Node.js is not installed"
    exit 1
fi

# Check if FFmpeg is installed
if ! command -v ffmpeg &> /dev/null; then
    echo "❌ FFmpeg is not installed"
    exit 1
fi

# Check if dependencies are installed
if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
fi

# Check if remotion dependencies are installed
if [ ! -d "remotion/node_modules" ]; then
    echo "📦 Installing Remotion dependencies..."
    cd remotion && npm install && cd ..
fi

# Build TypeScript
echo "🔨 Building TypeScript..."
npm run build

# Start server
echo "🌟 Starting server..."
npm start
