#!/bin/bash

# Test script for the FFmpeg Captions API Preview endpoint
# Usage: ./test-api-preview.sh [video_file]

API_URL="http://localhost:3002/api/captions"
VIDEO_FILE=${1:-"../../assets/beast.mp4"}

echo "🎬 Testing FFmpeg Captions API - Preview Feature"
echo "=================================================="

# Test 1: Health check
echo "1. Testing health endpoint..."
health_response=$(curl -s "$API_URL/health")
health_success=$(echo "$health_response" | jq -r '.success')
if [ "$health_success" = "true" ]; then
    echo "✅ Health check passed"
else
    echo "❌ Health check failed"
    exit 1
fi
echo ""

# Test 2: Check if video file exists
if [ ! -f "$VIDEO_FILE" ]; then
    echo "❌ Video file '$VIDEO_FILE' not found"
    echo "Example: $0 beast.mp4 2.5"
    exit 1
fi

echo "2. Video file check..."
echo "✅ Using video file: $VIDEO_FILE"
echo ""

# Test 3: Generate preview frame
echo "3. Testing preview generation..."

# Sample transcription data with realistic timing
TRANSCRIPTION_DATA='{
    "preset": "custom",
    "customStyle": {
        "fontSize": 90,
        "uppercase": true,
        "fontFamily": "Montserrat",
        "fontWeight": 900,
        "textColor": "FFFFFF",
        "outlineColor": "000000",
        "outlineWidth": 4,
        "activeWordColor": "246ce0",
        "activeWordOutlineColor": "FFFFFF",
        "activeWordOutlineWidth": 4,
        "activeWordFontSize": 100,
        "backgroundColor": "000000",
        "backgroundOpacity": 0,
        "shadowColor": "000000",
        "shadowOpacity": 0,
        "activeWordShadowColor": "FF6B35",
        "activeWordShadowOpacity": 80,
        "position": "center",
        "positionOffset": 300
    },
    "transcriptionData": {
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
                    "text": " Im",
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
                    "startMs": 4639.999866485596,
                    "endMs": 4719.99979019165,
                    "timestampMs": 4679.999828338623
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
            "duration": 10.5,
            "language": "en",
            "metadata": {
                "service": "whisper-cpp",
                "model": "medium",
                "timestamp": "2025-06-07T19:05:15.430Z"
            }
        },
        "processingTime": 87795
    }
}'

echo "Generating preview frame ..."

# Generate preview
preview_response=$(curl -s -X POST \
    -F "video=@$VIDEO_FILE" \
    -F "data=$TRANSCRIPTION_DATA" \
    "$API_URL/preview" \
    --output "preview_frame.png" \
    -w "HTTP_CODE:%{http_code}\nTIME_TOTAL:%{time_total}s\nSIZE_DOWNLOAD:%{size_download}")

http_code=$(echo "$preview_response" | grep "HTTP_CODE:" | cut -d: -f2)
time_total=$(echo "$preview_response" | grep "TIME_TOTAL:" | cut -d: -f2)
size_download=$(echo "$preview_response" | grep "SIZE_DOWNLOAD:" | cut -d: -f2)

if [ "$http_code" = "200" ]; then
    echo "✅ Preview generation successful!"
    echo "📊 Response details:"
    echo "   - HTTP Status: $http_code"
    echo "   - Processing time: $time_total"
    echo "   - Image size: $size_download bytes"
    echo "   - Output file: preview_frame.png"

    # Check if the file actually exists and has content
    if [ -f "preview_frame.png" ] && [ -s "preview_frame.png" ]; then
        file_size=$(stat -f%z "preview_frame.png" 2>/dev/null || stat -c%s "preview_frame.png" 2>/dev/null)
        echo "   - Local file size: $file_size bytes"
        echo ""
        echo "🖼️  Preview image saved as: preview_frame.png"
        echo "   You can open it to see the caption preview!"
    else
        echo "❌ Preview file is empty or missing"
    fi
else
    echo "❌ Preview generation failed!"
    echo "   HTTP Status: $http_code"
    echo "   Check the server logs for more details"

    # Try to read error response if the file contains JSON
    if [ -f "preview_frame.png" ]; then
        error_content=$(cat "preview_frame.png" 2>/dev/null)
        echo "   Error response: $error_content"
        rm -f "preview_frame.png" # Remove the error file
    fi
fi

echo ""
echo "🎉 Preview API testing completed!"
echo ""
echo "📁 Generated files:"
ls -la preview*.png 2>/dev/null || echo "   No preview files generated"
echo ""
echo "💡 Tip: Open the preview images to see the caption styling!"
