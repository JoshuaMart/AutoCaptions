# AutoCaptions Web Interfac

A modern, production-ready web interface for the AutoCaptions microservices ecosystem. Built with PHP 8.4 and modern JavaScript architecture, providing a robust and scalable user experience for video caption generation.

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
├── src/                           # Application source code
│   ├── Controllers/               # Request handlers
│   │   ├── ConfigController.php   # Service configuration
│   │   ├── FFmpegController.php   # FFmpeg service integration
│   │   ├── TranscriptionController.php # Transcription management
│   │   └── UploadController.php   # File upload handling
│   ├── Core/                      # Framework core components
│   │   ├── Application.php        # Main application class
│   │   ├── Request.php           # HTTP request wrapper
│   │   ├── Response.php          # HTTP response wrapper
│   │   ├── Router.php            # URL routing system
│   │   ├── Security.php          # Security utilities
│   │   ├── Session.php           # Session management
│   │   └── Validator.php         # Input validation
│   ├── Services/                  # Business logic services
│   │   ├── ConfigManager.php     # Configuration management
│   │   ├── FileManager.php       # File operations
│   │   └── ServiceManager.php    # Microservice communication
│   └── Views/                     # Templates and layouts
│       ├── layouts/
│       │   └── main.php          # Main layout template
│       └── pages/
│           ├── home.php          # Upload page
│           ├── transcriptions.php # Caption editing
│           ├── service-choice.php # Service selection
│           └── ffmpeg-config.php  # FFmpeg configuration
├── public/                        # Web-accessible files
│   ├── assets/
│   │   ├── css/
│   │   │   └── app.css           # Application styles
│   │   └── js/
│   │       ├── app.js            # Main application
│   │       └── modules/          # JavaScript modules
│   │           ├── api-client.js
│   │           ├── file-upload.js
│   │           ├── service-status.js
│   │           └── ...
│   ├── index.php                 # Application entry point
│   └── .htaccess                 # Apache configuration
├── config/                        # Configuration files
│   ├── app.php                   # Application settings
│   ├── security.php              # Security configuration
│   └── services.php              # Service definitions
├── storage/                       # Data storage
│   ├── logs/                     # Application logs
│   ├── sessions/                 # Session files
│   ├── temp/                     # Temporary files
│   └── uploads/                  # Uploaded files
└── Dockerfile                    # Container configuration
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

### Application Configuration

Edit configuration files in `config/`:

**`config/app.php`** - Application settings:
```php
return [
    'name' => 'AutoCaptions Web',
    'env' => 'development',
    'debug' => true,
    'timezone' => 'UTC',
    // ...
];
```

**`config/security.php`** - Security settings:
```php
return [
    'csrf' => ['enabled' => true],
    'headers' => ['X-Frame-Options' => 'DENY'],
    'upload_security' => ['max_file_size_bytes' => 500 * 1024 * 1024],
    // ...
];
```

### Debug Mode

Enable debug mode in `config/app.php`:

```php
return [
    'debug' => true,
    'env' => 'development',
    // ...
];
```

### Logging

Application logs are written to `storage/logs/app.log`:

```bash
# Monitor logs in real-time
tail -f storage/logs/app.log

# Check for errors
grep ERROR storage/logs/app.log
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
