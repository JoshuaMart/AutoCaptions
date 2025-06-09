# https://www.remotion.dev/docs/docker
FROM --platform=linux/amd64 node:22-bookworm-slim

# Install Chrome dependencies
RUN apt-get update
RUN apt install -y \
    libnss3 \
    libdbus-1-3 \
    libatk1.0-0 \
    libgbm-dev \
    libasound2 \
    libxrandr2 \
    libxkbcommon-dev \
    libxfixes3 \
    libxcomposite1 \
    libxdamage1 \
    libatk-bridge2.0-0 \
    libpango-1.0-0 \
    libcairo2 \
    libcups2 \
    ffmpeg

# Technique I found to make it work with H264 videos, otherwise is needed to convert the video to webm
# - const command = `ffmpeg -i "${inputPath}" -c:v libx264 -c:a copy -movflags +faststart "${outputPath}"`;
# + const command = `ffmpeg -i "${inputPath}" -c:v libvpx-vp9 -c:a libopus "${outputPath}"`;
RUN apt-get update && apt-get install -y gnupg wget && \
    wget -q -O - https://dl.google.com/linux/linux_signing_key.pub | gpg --dearmor > /usr/share/keyrings/google-chrome-keyring.gpg && \
    sh -c 'echo "deb [arch=amd64 signed-by=/usr/share/keyrings/google-chrome-keyring.gpg] http://dl.google.com/linux/chrome/deb/ stable main" > /etc/apt/sources.list.d/google-chrome.list' && \
    apt-get update && \
    apt-get install -y google-chrome-stable

WORKDIR /app

# Copy package files
COPY package.json ./
COPY remotion/package.json remotion/tsconfig.json remotion/remotion.config.ts ./remotion/
COPY remotion/src ./remotion/src

# Install dependencies
RUN npm i

# Install Remotion dependencies et Chrome
RUN cd remotion && \
    npm i && \
    npx remotion browser ensure && \
    cd ..

COPY . .

ENV REMOTION_CHROME_BINARY /usr/bin/google-chrome-stable

# Start the application
CMD ["npm", "start"]
