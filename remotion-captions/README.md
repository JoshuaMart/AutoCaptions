--- Converto to H264 if H265
```
ffmpeg -i video.mp4 -c:v libx264 -c:a copy -movflags +faststart ffmpeg_video.mp4
```

```
npx remotion render CaptionedVideo out/video.mp4 --props='{"src":"public/test.mp4"}'
```
