FROM node:lts-alpine3.22

# Install FFmpeg
RUN apk add --no-cache ffmpeg git cmake build-base alpine-sdk g++ \
    && rm -rf /var/cache/apk/*

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm i && \
    npm cache clean --force

# Copy source code
COPY . .

# Build the application
RUN npm run build

# Start the application
CMD ["npm", "start"]
