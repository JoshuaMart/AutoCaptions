# AutoCaptions Web Interface

A modern web interface for the AutoCaptions microservices ecosystem. Built with PHP and vanilla JavaScript, providing an intuitive user experience for video caption generation.

⚠️ **Development Notice**: This web interface is a proof-of-concept MVP and is not intended for production use. The code quality is rough and serves solely as a demonstration layer for interacting with the backend services.

## 🎯 Overview

The web interface serves as the frontend orchestrator for all AutoCaptions services, offering:

- **File Upload & Management**: Drag-and-drop video upload with validation
- **Transcription Editing**: Real-time caption editing with timestamp management
- **Service Configuration**: Dynamic service URL configuration and health monitoring
- **Multi-Service Support**: Choose between FFmpeg and Remotion rendering engines

## 🏗️ Architecture

```
web/
├── api/                    # Backend API endpoints
│   ├── health.php         # Service health checks
│   ├── proxy.php          # Microservice proxy
│   ├── save-upload.php    # File upload handler
│   ├── generate-video.php # Video generation
│   └── ...
├── assets/
│   └── js/
│       ├── main.js        # Core application logic
│       └── api.js         # API communication helpers
├── components/            # Reusable PHP components
│   ├── header.php        # Navigation header
│   └── settings-modal.php # Service configuration modal
├── config/
│   └── services.php      # Service configuration management
├── pages/                 # Application pages
│   ├── transcription.php # Caption editing interface
│   ├── service-choice.php # Service selection
│   ├── ffmpeg-config.php  # FFmpeg styling options
│   └── result.php         # Final result display
├── uploads/              # Temporary file storage
├── index.php            # Main upload page
└── Dockerfile           # Container configuration
```

## 📋 Prerequisites

- **PHP 8.4+** with Apache
- **Docker** (recommended for deployment)
- **AutoCaptions Services**:
  - Transcriptions service
  - FFmpeg Captions service
  - Remotion Captions service

## 🔧 Installation

### Docker Deployment

1. **Build the container**:
   ```bash
   docker build -t autocaptions-web .
   ```

2. **Run with docker-compose** (from project root):
   ```bash
   docker-compose up -d web
   ```

3. **Access the interface**:
   ```
   http://localhost
   ```

## ⚙️ Configuration

### Service URLs

Configure backend service URLs through the web interface:

1. Click the **gear icon** in the header
2. Update service URLs as needed:
   - **Transcriptions**: `http://localhost:3001` (or Docker: `http://transcriptions:3001`)
   - **FFmpeg Captions**: `http://localhost:3002` (or Docker: `http://ffmpeg-captions:3002`)
   - **Remotion Captions**: `http://localhost:3003` (or Docker: `http://remotion-captions:3003`)
3. Test connections and save

### PHP Configuration

Key settings in `php.ini` or Docker:

```ini
upload_max_filesize = 500M
post_max_size = 500M
memory_limit = 512M
max_execution_time = 300
```

### File Storage

- **Uploads Directory**: `web/uploads/` (auto-created)
- **Temporary Files**: Cleaned up automatically
- **Session Storage**: Used for workflow state management

## 🎮 Usage

### Basic Workflow

1. **Upload Video**
   - Navigate to the homepage
   - Drag-and-drop a video file or click to browse
   - Supported formats: MP4, MOV, AVI, MKV, WebM
   - Maximum size: 500MB

2. **Generate Transcription**
   - Click "Generate Transcription"
   - Wait for AI processing to complete
   - Review automatically generated captions

3. **Edit Captions**
   - Modify text, timing, and structure
   - Use tools to split/merge captions
   - Validate timestamps automatically

4. **Choose Rendering Service**
   - **FFmpeg**: Fast rendering with ASS styling
   - **Remotion**: Advanced customization with React

5. **Configure Styling**
   - Customize fonts, colors, positioning
   - Preview changes in real-time
   - Export with chosen settings

6. **Download Result**
   - Generate final video with captions
   - Download in preferred format
   - Share or use in your projects

## 🎨 Customization

### Styling

The interface uses **Tailwind CSS** for styling. Key customization points:

- **Colors**: Modify color schemes in `index.php` and page templates
- **Layout**: Adjust responsive breakpoints and grid layouts
- **Components**: Customize modal dialogs and form elements

### Functionality

- **Upload Validation**: Modify file type/size limits in `assets/js/main.js`
- **Service Configuration**: Add new services in `config/services.php`
- **Workflow Steps**: Customize the multi-step process in page templates

## 🔒 Security

### File Upload Security

- **Type Validation**: Strict MIME type checking
- **Size Limits**: Configurable maximum file sizes
- **Storage Isolation**: Uploads stored outside web root when possible
- **Cleanup**: Automatic temporary file removal

### API Security

- **Input Validation**: All user inputs validated and sanitized
- **CSRF Protection**: Session-based request validation
- **Service Proxy**: Controlled access to backend services
- **Error Handling**: Secure error messages without system disclosure

## 🚨 Troubleshooting

### Common Issues

**"Service Unavailable" Errors**
```bash
# Check service status
docker-compose ps

# Check service logs
docker-compose logs transcriptions
docker-compose logs ffmpeg-captions
docker-compose logs remotion-captions
```

**File Upload Failures**
```bash
# Check PHP configuration
php -i | grep upload

# Check directory permissions
ls -la web/uploads/

# Increase PHP limits
echo "upload_max_filesize = 1G" >> /usr/local/etc/php/conf.d/uploads.ini
```

**Transcription Processing Timeouts**
```bash
# Increase PHP execution time
echo "max_execution_time = 600" >> /usr/local/etc/php/conf.d/uploads.ini

# Check service health
curl http://localhost:3001/health
```

### Debug Mode

Enable debug output by adding to your service configuration:

```php
<?php
// Add to config/services.php
define('DEBUG_MODE', true);
?>
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
