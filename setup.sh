#!/bin/bash

echo "🚀 Auto Captions - Global Setup"
echo "================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_section() {
    echo ""
    echo -e "${BLUE}$1${NC}"
    echo "$(printf '%.0s-' {1..50})"
}

# Check system requirements
print_section "🔍 Checking System Requirements"

# Check if Node.js is installed
if ! command -v node &> /dev/null; then
    print_error "Node.js is not installed. Please install Node.js 22+ first."
    exit 1
fi
print_success "Node.js version: $(node --version)"

# Check if npm is installed
if ! command -v npm &> /dev/null; then
    print_error "npm is not installed. Please install npm first."
    exit 1
fi
print_success "npm version: $(npm --version)"

# Check if FFmpeg is installed
if ! command -v ffmpeg &> /dev/null; then
    print_warning "FFmpeg is not installed or not in PATH."
    echo "    Please install FFmpeg:"
    echo "     - macOS: brew install ffmpeg"
    echo "     - Ubuntu: sudo apt install ffmpeg"
    echo "     - Windows: Download from https://ffmpeg.org/"
    exit 1
fi
print_success "FFmpeg version: $(ffmpeg -version | head -n 1)"

# Check if FFprobe is installed
if ! command -v ffprobe &> /dev/null; then
    print_warning "FFprobe is not installed or not in PATH."
    echo "   FFprobe usually comes with FFmpeg installation."
    exit 1
fi
print_success "FFprobe is available"

# Services array
services=("transcriptions" "ffmpeg-captions" "remotion-captions")
failed_services=()

print_section "📦 Setting up services"

# Setup each service
for service in "${services[@]}"; do
    print_info "Setting up $service service..."

    if [ -d "$service" ]; then
        cd "$service"

        # Check if setup.sh exists and is executable
        if [ -f "setup.sh" ]; then
            chmod +x setup.sh

            # Run the setup script
            if ./setup.sh; then
                print_success "$service setup completed"
            else
                print_error "$service setup failed"
                failed_services+=("$service")
            fi
        else
            print_error "setup.sh not found in $service directory"
            failed_services+=("$service")
        fi

        cd ..
    else
        print_error "$service directory not found"
        failed_services+=("$service")
    fi
    echo ""
done

# Summary
print_section "📋 Setup Summary"

if [ ${#failed_services[@]} -eq 0 ]; then
    print_success "All services setup completed successfully!"
    echo ""
    echo "🎉 Auto Captions is ready to use!"
    echo ""
    echo "📚 Next steps:"
    echo "   1. Review .env files in each service directory"
    echo "   2. Start services individually or use Docker Compose"
    echo ""
    echo "🚀 Available services:"
    echo "   • Transcriptions API    - http://localhost:3001"
    echo "   • FFmpeg Captions API   - http://localhost:3002"
    echo "   • Remotion Captions API - http://localhost:3003"
    echo ""
    echo "🐳 Docker commands:"
    echo "   docker-compose up -d    - Start all services"
    echo "   docker-compose down     - Stop all services"
    echo "   docker-compose logs     - View logs"
else
    print_error "Setup failed for the following services:"
    for service in "${failed_services[@]}"; do
        echo "   - $service"
    done
    echo ""
    echo "Please check the error messages above and fix the issues."
    exit 1
fi
