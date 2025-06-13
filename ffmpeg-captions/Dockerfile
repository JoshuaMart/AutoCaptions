FROM node:lts-alpine3.22

# Install FFmpeg and fonts
RUN apk add --no-cache ffmpeg \
    curl \
    unzip \
    && rm -rf /var/cache/apk/*

# Create fonts directory
RUN mkdir -p /usr/share/fonts/truetype/google-fonts

# Download and install Fonts used by the service
RUN fonts="montserrat inter roboto poppins open-sans:opensans oswald-4:oswald bebas-neue anton bangers lato source-sans-pro nunito raleway-5:raleway" && \
    for font in $fonts; do \
    url_name=$(echo "$font" | cut -d':' -f1) && \
    dir_name=$(echo "$font" | cut -d':' -f2) && \
    [ "$dir_name" = "$url_name" ] || true && \
    echo "Installing font: $url_name -> $dir_name" && \
    curl -L "https://font.download/dl/font/${url_name}.zip" -o "/tmp/${dir_name}.zip" && \
    unzip "/tmp/${dir_name}.zip" -d "/tmp/${dir_name}" && \
    find "/tmp/${dir_name}" -name "*.ttf" -exec cp {} /usr/share/fonts/truetype/google-fonts/ \; && \
    rm -rf "/tmp/${dir_name}.zip" "/tmp/${dir_name}" || exit 1; \
    done && \
    fc-cache -fv

# Set working directory
WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies
RUN npm ci && \
    npm cache clean --force

# Copy source code
COPY . .

# Build the application
RUN npm run build

# Create necessary directories with proper permissions
RUN mkdir -p /app/uploads /app/temp && \
    chmod 755 /app/uploads /app/temp

# Start the application
CMD ["npm", "start"]
