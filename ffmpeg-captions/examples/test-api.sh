#!/bin/bash

# Test script for the FFmpeg Captions API
# Usage: ./test-api.sh [video_file]

API_URL="http://localhost:3002/api/captions"
VIDEO_FILE=${1:-"../../assets/beast.mp4"}

echo "🚀 Testing FFmpeg Captions API"
echo "================================"

# Test 1: Health check
echo "1. Testing health endpoint..."
curl -s "$API_URL/health" | jq '.success'
echo ""

# Test 2: Get presets
echo "2. Testing presets endpoint..."
curl -s "$API_URL/presets" | jq '.success'
echo ""

# Test 3: Get specific preset
echo "3. Testing specific preset..."
curl -s "$API_URL/presets/custom" | jq '.success'
echo ""

# Test 4: Get fonts
echo "4. Testing fonts endpoint..."
curl -s "$API_URL/fonts" | jq '.success'
echo ""

# Test 5: Generate captions (if video file e   xists)
if [ -f "$VIDEO_FILE" ]; then
    echo "5. Testing caption generation with $VIDEO_FILE..."

    # Sample transcription data
    TRANSCRIPTION_DATA='{
        "preset": "custom",
        "customStyle": {
            "fontSize": 90,
            "uppercase": true,
            "fontFamily": "Montserrat",
            "fontWeight": 900,
            "textColor": "FFFFFF",
            "outlineColor": "000000",
            "activeWordColor": "246ce0",
            "activeWordOutlineColor": "FFFFFF",
            "activeWordOutlineWidth": 4,
            "backgroundColor": "000000",
            "backgroundOpacity": 0,
            "shadowColor": "000000",
            "shadowOpacity": 0,
            "activeWordShadowColor": "FF6B35",
            "activeWordShadowOpacity": 0
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
                "language": "fr",
                "metadata": {
                    "service": "whisper-cpp",
                    "model": "medium",
                    "timestamp": "2025-06-07T19:05:15.430Z"
                }
            },
            "processingTime": 87795
        }
    }'

    curl -s -X POST \
        -F "video=@$VIDEO_FILE" \
        -F "data=$TRANSCRIPTION_DATA" \
        "$API_URL/generate" \
        --output "result.mp4" \
        -w "HTTP Status: %{http_code}\nTime: %{time_total}s\n"

    echo "✅ Caption generation completed!"
else
    echo "5. Skipping caption generation - no video file found"
    echo "   To test with a video: $0 your-video.mp4"
fi

echo ""
echo "🎉 API testing completed!"
