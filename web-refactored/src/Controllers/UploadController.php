<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\FileManager;
use App\Core\Application; // To access session

class UploadController
{
    private FileManager $fileManager;

    public function __construct()
    {
        // In a more advanced setup with a Dependency Injection Container,
        // FileManager would typically be injected.
        $this->fileManager = new FileManager();
    }

    /**
     * Handles the file upload request.
     * Expects a file in the 'videoFile' field of the request (can be configured).
     *
     * @param Request $request The HTTP request object.
     * @param Response $response The HTTP response object.
     */
    public function handleUpload(Request $request, Response $response): void
    {
        $uploadFieldName = 'videoFile'; // Standardize or make configurable

        if (!$request->hasFile($uploadFieldName)) {
            $response->errorJson(
                'UPLOAD_NO_FILE_SPECIFIED',
                'No file was uploaded or the field name is incorrect.',
                ['expected_field' => $uploadFieldName],
                400 // Bad Request
            )->send();
            return;
        }

        $fileData = $request->file($uploadFieldName);

        // Basic check, though FileManager's validator will be more thorough
        if (empty($fileData) || !isset($fileData['tmp_name']) || $fileData['error'] === UPLOAD_ERR_NO_FILE) {
             $response->errorJson(
                'UPLOAD_INVALID_FILE_DATA',
                'Uploaded file data is invalid or missing critical information.',
                null,
                400
            )->send();
            return;
        }
        
        // FileManager::upload handles validation (type, size based on config) and moving the file
        $uploadResult = $this->fileManager->upload($fileData);

        if ($uploadResult['success']) {
            // Get additional metadata from request
            $duration = $request->input('duration');
            $fileSize = $request->input('fileSize');
            $originalName = $request->input('originalName');
            
            // Store essential file info in session for subsequent steps (e.g., transcription)
            $session = Application::getInstance()->session;
            $fileInfo = [
                'filePath' => $uploadResult['filePath'], // Absolute path on server
                'fileName' => $uploadResult['fileName'], // Sanitized, unique name
                'originalName' => $uploadResult['originalName'] ?: $originalName,
                'uploadTimestamp' => time(),
            ];
            
            // Add duration if provided
            if ($duration !== null && is_numeric($duration)) {
                $fileInfo['duration'] = (float)$duration;
            }
            
            // Add file size if provided
            if ($fileSize !== null && is_numeric($fileSize)) {
                $fileInfo['fileSize'] = (int)$fileSize;
            }
            
            $session->set('uploaded_file_info', $fileInfo);

            // Respond to the client
            $response->json(
                [
                    // Client might not need the absolute filePath directly,
                    // but an identifier or relative path if it were web-accessible.
                    // For now, providing a summary.
                    'uploadedFile' => [
                        'name' => $uploadResult['fileName'],
                        'original_name' => $uploadResult['originalName'],
                        // 'identifier' => session_id() // Or a generated file ID if not using session for next step ID
                    ]
                ],
                $uploadResult['message'] ?? 'File uploaded successfully and is ready for processing.',
                201 // Created
            )->send();
        } else {
            // Determine appropriate status code based on error type
            $statusCode = 422; // Unprocessable Entity for validation errors
            if (isset($uploadResult['errors']) && is_array($uploadResult['errors'])) {
                foreach ($uploadResult['errors'] as $errorMsg) {
                    if (stripos($errorMsg, 'failed to move') !== false || stripos($errorMsg, 'failed to create directory') !== false) {
                        $statusCode = 500; // Internal Server Error
                        break;
                    }
                }
            } elseif (!isset($uploadResult['errors'])) { // No specific errors, more like a system issue
                 $statusCode = 500;
            }

            $response->errorJson(
                'UPLOAD_PROCESSING_FAILED',
                $uploadResult['message'] ?? 'File upload could not be processed.',
                ['details' => $uploadResult['errors'] ?? 'An unknown error occurred during file processing.'],
                $statusCode
            )->send();
        }
    }
    
    /**
     * Handles the request to delete the currently session-tracked uploaded file.
     * This might be used if a user cancels an operation after uploading.
     *
     * @param Request $request
     * @param Response $response
     */
    public function deleteCurrentUpload(Request $request, Response $response): void
    {
        $session = Application::getInstance()->session;
        $uploadedFileInfo = $session->get('uploaded_file_info');

        if (!$uploadedFileInfo || empty($uploadedFileInfo['filePath'])) {
            $response->errorJson(
                'DELETE_NO_FILE_IN_SESSION',
                'No file information found in the current session to delete.',
                null,
                404 // Not Found or 400 Bad Request
            )->send();
            return;
        }

        $filePathToDelete = $uploadedFileInfo['filePath'];

        // Security check: ensure the path is within the allowed upload directory
        $defaultUploadPath = $this->fileManager->getDefaultUploadPath();
        
        // Normalize paths for comparison
        $realFilePathToDelete = realpath($filePathToDelete);
        $realDefaultUploadPath = realpath($defaultUploadPath);

        if (!$realFilePathToDelete || !$realDefaultUploadPath || !str_starts_with($realFilePathToDelete, $realDefaultUploadPath)) {
             $response->errorJson(
                'DELETE_FORBIDDEN_PATH',
                'Attempted to delete a file outside of the designated uploads directory.',
                null,
                403 // Forbidden
            )->send();
            return;
        }

        if ($this->fileManager->deleteFile($filePathToDelete)) {
            $session->remove('uploaded_file_info');
            $response->json(
                ['message' => 'File deleted successfully from server and session.'],
                'File deleted successfully.',
                200 // OK
            )->send();
        } else {
            // Log this server-side, client gets a generic message
            error_log("UploadController: Failed to delete file {$filePathToDelete}. It might have already been deleted or permissions issue.");
            $response->errorJson(
                'DELETE_OPERATION_FAILED',
                'Failed to delete the specified file on the server.',
                // Avoid exposing specific file paths in error details to client unless for debug
                // ['path_attempted' => $filePathToDelete], 
                500 // Internal Server Error
            )->send();
        }
    }

    /**
     * Récupère les informations du fichier actuellement téléversé depuis la session.
     *
     * @param Request $request
     * @param Response $response
     */
    public function getCurrentUpload(
        Request $request,
        Response $response
    ): void {
        $session = Application::getInstance()->session;
        $uploadedFileInfo = $session->get("uploaded_file_info");

        if ($uploadedFileInfo) {
            $response
                ->json(
                    $uploadedFileInfo,
                    "Current upload information retrieved successfully.",
                    200
                )
                ->send();
        } else {
            $response
                ->errorJson(
                    "UPLOAD_NOT_FOUND",
                    "No upload information found in the current session.",
                    null,
                    404
                )
                ->send();
        }
    }
}