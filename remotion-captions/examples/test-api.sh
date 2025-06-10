#!/bin/bash

# Test script for Remotion Captions API
# Usage: ./test-api.sh [video_file]

API_URL="http://localhost:3003"
VIDEO_FILE=${1:-"../../assets/beast.mp4"} # Default video file, can be overridden by argument

echo "🧪 Testing Remotion Captions API..."

# --- Health Check ---
echo "🔍 Checking if server is running..."
if ! curl -s "$API_URL/health" > /dev/null; then
    echo "❌ Server is not running. Start it with: npm run dev"
    exit 1
fi
echo "✅ Server is running"

# Test health endpoint (optional, just for verbose output)
echo "🔍 Testing health endpoint (detailed output):"
curl -s "$API_URL/health" | jq .
echo ""

# --- Create Test Data Files ---
echo "📝 Creating test transcription data (test_transcription.json)..."
cat > test_transcription.json << 'EOF'
{
  "success": true,
  "transcription": {
    "captions": [
        {
            "text": "Yeah,",
            "startMs": 939.9999976158142,
            "endMs": 1379.9999952316284,
            "timestampMs": 1159.9999964237213
        },
        {
            "text": " my",
            "startMs": 1440.000057220459,
            "endMs": 1720.0000286102295,
            "timestampMs": 1580.0000429153442
        },
        {
            "text": " earliest",
            "startMs": 1720.0000286102295,
            "endMs": 2339.9999141693115,
            "timestampMs": 2029.9999713897705
        },
        {
            "text": " years,",
            "startMs": 2339.9999141693115,
            "endMs": 2839.9999141693115,
            "timestampMs": 2589.9999141693115
        },
        {
            "text": " I'm",
            "startMs": 3119.999885559082,
            "endMs": 3980.0000190734863,
            "timestampMs": 3549.999952316284
        },
        {
            "text": " just",
            "startMs": 3980.0000190734863,
            "endMs": 4239.999771118164,
            "timestampMs": 4109.999895095825
        },
        {
            "text": " stubborn,",
            "startMs": 4239.999771118164,
            "endMs": 4480.000019073486,
            "timestampMs": 4359.999895095825
        },
        {
            "text": " man,",
            "startMs": 4659.999847412109,
            "endMs": 4719.99979019165,
            "timestampMs": 4689.99981880188
        },
        {
            "text": " I",
            "startMs": 4719.99979019165,
            "endMs": 4800.000190734863,
            "timestampMs": 4759.999990463257
        },
        {
            "text": " just",
            "startMs": 4800.000190734863,
            "endMs": 5000,
            "timestampMs": 4900.000095367432
        },
        {
            "text": " never",
            "startMs": 5000,
            "endMs": 5139.999866485596,
            "timestampMs": 5069.999933242798
        },
        {
            "text": " give",
            "startMs": 5139.999866485596,
            "endMs": 5360.000133514404,
            "timestampMs": 5250
        },
        {
            "text": " up.",
            "startMs": 5360.000133514404,
            "endMs": 5579.999923706055,
            "timestampMs": 5470.0000286102295
        }
    ],
    "duration": 4.0,
    "language": "english",
    "metadata": {
      "service": "openai-whisper",
      "model": "whisper-1",
      "timestamp": "2025-06-09T10:00:00.000Z"
    }
  },
  "processingTime": 1000
}
EOF

echo "📝 Creating test props data (test_props.json)..."
cat > test_props.json << 'EOF'
{
  "fontConfig": {
    "family": "Inter",
    "weight": "800"
  },
  "captionStyle": {
    "maxWidth": 0.8,
    "textColor": "white",
    "strokeColor": "black",
    "strokeWidth": 10,
    "activeWordColor": "white",
    "textPosition": "center",
    "textPositionOffset": 100,
    "wordPadding": 8,
    "activeWordBackgroundColor": "#FF5700",
    "activeWordBackgroundOpacity": 1,
    "activeWordBorderRadius": 6,
    "fontSize": 60
  }
}
EOF

echo "✅ Created test files: test_transcription.json, test_props.json"
echo ""

# --- Render Request ---
echo "🎬 Sending render request to $API_URL/render..."
# Send the POST request, store the response in a variable.
# Using 'tr -d '\n'' to remove newlines from JSON file content before passing to curl,
# which can sometimes cause issues with multi-line arguments in shell.
RENDER_RESPONSE=$(curl -s -X POST "$API_URL/render" \
  -F "video=@$VIDEO_FILE" \
  -F "transcription=$(cat test_transcription.json | tr -d '\n')" \
  -F "props=$(cat test_props.json | tr -d '\n')")

if [ -z "$RENDER_RESPONSE" ]; then
    echo "❌ No response received from render endpoint."
    exit 1
fi

echo "✅ Render request sent. Response received:"
echo "$RENDER_RESPONSE" | jq . # Pretty print the JSON response

# --- Extract Download URL ---
echo "⬇️ Extracting download URL from response..."
DOWNLOAD_URL=$(echo "$RENDER_RESPONSE" | jq -r '.downloadUrl')

if [ -z "$DOWNLOAD_URL" ] || [ "$DOWNLOAD_URL" == "null" ]; then
    echo "❌ Could not find 'downloadUrl' in the render response. Something went wrong."
    exit 1
fi

echo "✅ Download URL: $DOWNLOAD_URL"

# --- Download the Rendered Video ---
echo "⬇️ Downloading the rendered video..."
# Extract filename from the URL for saving
FILENAME="example.mp4"
curl -s "$DOWNLOAD_URL" -o $FILENAME

if [ $? -eq 0 ]; then
    echo "✅ Video downloaded successfully as $FILENAME"
else
    echo "❌ Failed to download the video."
fi

echo ""

# --- Cleanup ---
echo "🧹 Cleaning up test files..."
rm test_transcription.json test_props.json
echo "✅ Cleanup complete."
echo ""
echo "🎉 Test script finished."
