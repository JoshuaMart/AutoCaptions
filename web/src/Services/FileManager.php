<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Validator;
use App\Core\Security; // For sanitizeString if needed for filenames explicitly

class FileManager
{
    private string $defaultUploadPath;
    private array $allowedMimeTypes;
    private int $maxFileSize;
    private bool $sanitizeFilenames;

    public function __construct()
    {
        $app = Application::getInstance();
        $uploadSecurityConfig = $app->getConfig('security.upload_security', []);

        $this->defaultUploadPath = $uploadSecurityConfig['default_upload_path'] ?? WEB_REFACTORED_ROOT . '/storage/uploads';
        $this->allowedMimeTypes = $uploadSecurityConfig['allowed_mime_types'] ?? [];
        $this->maxFileSize = $uploadSecurityConfig['max_file_size_bytes'] ?? 50 * 1024 * 1024; // Default 50MB
        $this->sanitizeFilenames = $uploadSecurityConfig['sanitize_filenames'] ?? true;

        // Ensure default upload path exists
        if (!is_dir($this->defaultUploadPath)) {
            $this->createDirectory($this->defaultUploadPath);
        }
    }

    /**
     * Handles the upload of a file.
     *
     * @param array $fileData The file data from $_FILES superglobal (e.g., $_FILES['my_file']).
     * @param string|null $destinationPath Optional custom destination directory relative to defaultUploadPath or absolute.
     * @param string|null $customFileName Optional custom name for the file (without extension).
     * @return array ['success' => bool, 'message' => string, 'filePath' => string|null, 'fileName' => string|null, 'originalName' => string|null, 'errors' => array|null]
     */
    public function upload(array $fileData, ?string $destinationPath = null, ?string $customFileName = null): array
    {
        // 1. Validate the file upload using App\Core\Validator
        $validator = Validator::file($fileData, $fileData['name'] ?? 'Uploaded file')
            ->required() // Checks for UPLOAD_ERR_NO_FILE and other basic upload errors
            ->allowedTypes($this->allowedMimeTypes)
            ->maxSize($this->bytesToHuman($this->maxFileSize));

        if (!$validator->validate()) {
            return [
                'success' => false,
                'message' => 'File validation failed.',
                'filePath' => null,
                'fileName' => null,
                'originalName' => $fileData['name'] ?? null,
                'errors' => $validator->getErrors()
            ];
        }

        // If validation passed, UPLOAD_ERR_OK is implied for further processing.
        $originalName = $fileData['name'];
        $tmpName = $fileData['tmp_name'];

        // 2. Generate a secure and unique filename
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = $customFileName ?: pathinfo($originalName, PATHINFO_FILENAME);
        
        if ($this->sanitizeFilenames) {
            $baseName = $this->sanitizeFileName($baseName);
        }
        $finalFileName = $this->generateUniqueFilename($baseName, $extension);

        // 3. Determine final destination path
        $uploadDir = $this->defaultUploadPath;
        if ($destinationPath !== null) {
            // Check if $destinationPath is absolute or relative
            if (str_starts_with($destinationPath, '/') || preg_match('/^[A-Za-z]:\\\\/', $destinationPath)) { // Absolute path
                $uploadDir = rtrim($destinationPath, '/\\');
            } else { // Relative to default upload path
                $uploadDir = rtrim($this->defaultUploadPath, '/\\') . DIRECTORY_SEPARATOR . trim($destinationPath, '/\\');
            }
        }

        if (!is_dir($uploadDir)) {
            if (!$this->createDirectory($uploadDir)) {
                return [
                    'success' => false,
                    'message' => "Failed to create destination directory: {$uploadDir}",
                    'filePath' => null,
                    'fileName' => $finalFileName,
                    'originalName' => $originalName,
                    'errors' => ["Failed to create directory {$uploadDir}"]
                ];
            }
        }
        
        $finalFilePath = $uploadDir . DIRECTORY_SEPARATOR . $finalFileName;

        // 4. Move the uploaded file
        if (move_uploaded_file($tmpName, $finalFilePath)) {
            // Optionally set permissions
            // chmod($finalFilePath, 0644);
            return [
                'success' => true,
                'message' => 'File uploaded successfully.',
                'filePath' => $finalFilePath,
                'fileName' => $finalFileName,
                'originalName' => $originalName,
                'errors' => null
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to move uploaded file.',
                'filePath' => null,
                'fileName' => $finalFileName,
                'originalName' => $originalName,
                'errors' => ['move_uploaded_file failed. Check permissions and paths.']
            ];
        }
    }

    /**
     * Sanitizes a filename by removing potentially harmful characters.
     *
     * @param string $filename The original filename (without extension).
     * @return string The sanitized filename.
     */
    public function sanitizeFileName(string $filename): string
    {
        // Remove characters that are not alphanumeric, dash, underscore, or dot.
        $filename = preg_replace('/[^A-Za-z0-9_.-]+/', '-', $filename);
        // Remove leading/trailing dashes or underscores
        $filename = trim($filename, '-_');
        // Reduce multiple consecutive dashes/underscores to a single one
        $filename = preg_replace('/([-|_])+/', '$1', $filename);
        
        if (empty($filename)) {
            return 'untitled';
        }
        return $filename;
    }

    /**
     * Generates a unique filename, appending a suffix if a file with the same name exists.
     *
     * @param string $baseName The base name of the file (e.g., "document").
     * @param string $extension The file extension (e.g., "pdf").
     * @param string|null $directory The directory to check for existing files. Defaults to defaultUploadPath.
     * @return string The unique filename.
     */
    public function generateUniqueFilename(string $baseName, string $extension, ?string $directory = null): string
    {
        $targetDirectory = $directory ?: $this->defaultUploadPath;
        $extension = !empty($extension) ? '.' . strtolower($extension) : '';
        $filename = $baseName . $extension;
        $counter = 1;

        while (file_exists($targetDirectory . DIRECTORY_SEPARATOR . $filename)) {
            $filename = $baseName . '_' . $counter . $extension;
            $counter++;
        }
        return $filename;
    }

    /**
     * Deletes a file.
     *
     * @param string $filePath The absolute path to the file.
     * @return bool True on success, false on failure.
     */
    public function deleteFile(string $filePath): bool
    {
        if (file_exists($filePath) && is_file($filePath)) {
            if (unlink($filePath)) {
                return true;
            } else {
                error_log("FileManager: Failed to delete file: {$filePath}. Check permissions.");
                return false;
            }
        }
        error_log("FileManager: File not found or is not a file: {$filePath}");
        return false;
    }

    /**
     * Deletes a directory recursively.
     *
     * @param string $directoryPath The absolute path to the directory.
     * @return bool True on success, false on failure.
     */
    public function deleteDirectory(string $directoryPath): bool
    {
        if (!is_dir($directoryPath)) {
            error_log("FileManager: Directory not found: {$directoryPath}");
            return false;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directoryPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir() && !$item->isLink()) {
                if (!@rmdir($item->getRealPath())) {
                    error_log("FileManager: Failed to remove directory {$item->getRealPath()}");
                    return false;
                }
            } else {
                if (!@unlink($item->getRealPath())) {
                     error_log("FileManager: Failed to remove file {$item->getRealPath()}");
                    return false;
                }
            }
        }

        if (!@rmdir($directoryPath)) {
            error_log("FileManager: Failed to remove main directory {$directoryPath}");
            return false;
        }
        
        return true;
    }


    /**
     * Creates a directory.
     *
     * @param string $path The absolute path of the directory to create.
     * @param int $permissions The permissions for the directory (octal).
     * @param bool $recursive Allows the creation of nested directories.
     * @return bool True on success or if directory already exists, false on failure.
     */
    public function createDirectory(string $path, int $permissions = 0775, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }
        if (@mkdir($path, $permissions, $recursive)) {
            return true;
        }
        error_log("FileManager: Failed to create directory: {$path}. Error: " . error_get_last()['message'] ?? 'Unknown error');
        return false;
    }

    /**
     * Gets the MIME type of a file.
     *
     * @param string $filePath The path to the file.
     * @return string|false The MIME type or false on failure.
     */
    public function getMimeType(string $filePath): string|false
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }
        if (function_exists('mime_content_type')) {
            return mime_content_type($filePath);
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            return $mime;
        }
        return false; // Unable to determine MIME type
    }

    /**
     * Converts bytes to a human-readable string (e.g., 1024 to 1KB).
     * Used internally for setting max size for Validator.
     *
     * @param int $bytes
     * @return string
     */
    private function bytesToHuman(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . $units[$i];
    }

    /**
     * Get the default upload path.
     * @return string
     */
    public function getDefaultUploadPath(): string
    {
        return $this->defaultUploadPath;
    }
}