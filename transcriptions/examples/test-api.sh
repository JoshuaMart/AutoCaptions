#!/bin/bash

# Test script for Transcription API
# Usage: ./test-api.sh [video_file]

API_BASE_URL="http://localhost:3001/api"
VIDEO_FILE=${1:-"../../assets/beast.mp4"}

echo "=== Transcription Service API Test ==="
echo ""

# 1. Health check
echo "1. Testing health endpoint..."
curl -s "$API_BASE_URL/health" | jq '.'
echo ""

# 2. Get available services
echo "2. Getting available services..."
curl -s "$API_BASE_URL/services" | jq '.'
echo ""

# 3. Transcribe a file (replace with your audio/video file)
echo "3. Transcribing file: $VIDEO_FILE"
echo "   This may take a while depending on file length..."

curl -X POST \
     -F "file=@$VIDEO_FILE" \
     -F "service=openai-whisper" \
     -F "language=auto" \
     -F "translateToEnglish=false" \
     "$API_BASE_URL/transcribe" | jq '.'

echo ""
echo "=== Test completed ==="
