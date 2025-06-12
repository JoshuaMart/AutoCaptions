<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\ServiceManager;
use App\Core\Application; // To access session
use CURLFile;

class TranscriptionController
{
    private ServiceManager $serviceManager;

    public function __construct()
    {
        $this->serviceManager = new ServiceManager();
    }

    /**
     * Initiates the transcription process for the currently uploaded file.
     *
     * @param Request $request The HTTP request object. Expected to contain options for transcription.
     * @param Response $response The HTTP response object.
     */
    public function startTranscription(
        Request $request,
        Response $response
    ): void {
        $session = Application::getInstance()->session;
        $uploadedFileInfo = $session->get("uploaded_file_info");

        if (!$uploadedFileInfo || empty($uploadedFileInfo["filePath"])) {
            $response
                ->errorJson(
                    "TRANSCRIPTION_NO_FILE",
                    "No uploaded file found in session to transcribe. Please upload a file first.",
                    null,
                    404 // Not Found or 400 Bad Request if upload is a prerequisite step always
                )
                ->send();
            return;
        }

        $filePath = $uploadedFileInfo["filePath"];
        if (!file_exists($filePath) || !is_readable($filePath)) {
            $session->remove("uploaded_file_info"); // Clean up stale session data
            $response
                ->errorJson(
                    "TRANSCRIPTION_FILE_UNREADABLE",
                    "The uploaded file is no longer accessible on the server.",
                    ["path_issue" => $filePath], // Be cautious exposing paths
                    500 // Internal Server Error
                )
                ->send();
            return;
        }

        // Get transcription options from the request (e.g., from JSON body or form data)
        // Default values can be set here or fetched from a config.
        $transcriptionServiceType = $request->input(
            "service",
            "openai-whisper"
        ); // 'openai-whisper' or 'whisper-cpp'
        $language = $request->input("language"); // Optional language code
        $translateToEnglish = $request->input("translateToEnglish", "false"); // Boolean string

        $serviceRequestData = [];
        if ($transcriptionServiceType) {
            $serviceRequestData["service"] = $transcriptionServiceType;
        }
        if ($language) {
            $serviceRequestData["language"] = $language;
        }
        // translateToEnglish is only for whisper-cpp if transcription service enforces it
        if (
            $transcriptionServiceType === "whisper-cpp" &&
            $translateToEnglish !== null
        ) {
            $serviceRequestData["translateToEnglish"] = $translateToEnglish;
        }

        // Prepare file for ServiceManager. It expects ['form_field_name' => '/path/to/file']
        // The transcription service API docs (transcriptions/README.md) specify 'file' as the field name.
        $filesToUpload = [
            "file" => $filePath, // ServiceManager will create CURLFile from path
        ];

        // Call the transcription service
        $transcriptionServiceResponse = $this->serviceManager->makeRequest(
            "transcriptions",
            "transcribe",
            "POST",
            $serviceRequestData, // Form data fields
            $filesToUpload // Files
        );

        if (
            $transcriptionServiceResponse["success"] &&
            isset($transcriptionServiceResponse["body"]["transcription"])
        ) {
            $transcriptionData =
                $transcriptionServiceResponse["body"]["transcription"];
            
            // Get processing time from the response
            $processingTime = $transcriptionServiceResponse["body"]["processingTime"] ?? null;

            // Store the successful transcription data in the session
            $session->set("transcription_data", [
                "captions" => $transcriptionData["captions"] ?? [],
                "duration" => $transcriptionData["duration"] ?? null,
                "language" => $transcriptionData["language"] ?? null,
                "metadata" => $transcriptionData["metadata"] ?? [],
                "processingTime" => $processingTime, // Store processing time at root level
                "original_file_name" => $uploadedFileInfo["originalName"],
                "processed_file_name" => $uploadedFileInfo["fileName"], // The name stored on server
                "timestamp" => time(),
            ]);

            // Clean up uploaded_file_info from session as it's now transcribed.
            // Or keep it if the original file path is needed for captioning services directly.
            // For now, let's assume captioning services might need the original file path.
            // $session->remove('uploaded_file_info'); // Or update it

            $response
                ->json(
                    [
                        "transcription" => $transcriptionData,
                        "message" => "Transcription completed successfully.",
                    ],
                    "Transcription successful.",
                    200
                )
                ->send();
        } else {
            // Log the full error from the service for debugging
            error_log(
                "TranscriptionController: Transcription service failed. Status: " .
                    ($transcriptionServiceResponse["statusCode"] ?? "N/A") .
                    ". Error: " .
                    ($transcriptionServiceResponse["error"] ??
                        "Unknown error") .
                    ". Body: " .
                    (is_string($transcriptionServiceResponse["body"])
                        ? $transcriptionServiceResponse["body"]
                        : json_encode($transcriptionServiceResponse["body"]))
            );

            $clientErrorMessage = "Failed to transcribe the audio/video.";
            if (
                isset($transcriptionServiceResponse["body"]["error"]["message"])
            ) {
                $clientErrorMessage =
                    $transcriptionServiceResponse["body"]["error"]["message"];
            } elseif (
                is_string($transcriptionServiceResponse["body"]) &&
                !empty($transcriptionServiceResponse["body"])
            ) {
                // if the body is a simple error string
                // $clientErrorMessage = $transcriptionServiceResponse['body']; // Could expose too much
            }

            $response
                ->errorJson(
                    "TRANSCRIPTION_SERVICE_ERROR",
                    $clientErrorMessage,
                    [
                        "service_status_code" =>
                            $transcriptionServiceResponse["statusCode"],
                        "service_error" =>
                            $transcriptionServiceResponse["error"],
                        // 'service_response_body' => $transcriptionServiceResponse['body'] // Potentially include for client-side debug if safe
                    ],
                    $transcriptionServiceResponse["statusCode"] ?? 500
                )
                ->send();
        }
    }

    /**
     * Retrieves the current transcription data from the session.
     *
     * @param Request $request
     * @param Response $response
     */
    public function getCurrentTranscription(
        Request $request,
        Response $response
    ): void {
        $session = Application::getInstance()->session;
        $transcriptionData = $session->get("transcription_data");

        if ($transcriptionData) {
            $response
                ->json(
                    ["transcription" => $transcriptionData],
                    "Current transcription data retrieved successfully.",
                    200
                )
                ->send();
        } else {
            $response
                ->errorJson(
                    "TRANSCRIPTION_NOT_FOUND",
                    "No transcription data found in the current session.",
                    null,
                    404
                )
                ->send();
        }
    }

    /**
     * Clears transcription-related data from the session.
     *
     * @param Request $request
     * @param Response $response
     */
    public function clearTranscriptionData(
        Request $request,
        Response $response
    ): void {
        $session = Application::getInstance()->session;
        $session->remove("transcription_data");
        // Optionally also remove 'uploaded_file_info' if it's tied to this transcription flow
        // $session->remove('uploaded_file_info');

        $response
            ->json(
                ["message" => "Transcription data cleared from session."],
                "Session cleared successfully.",
                200
            )
            ->send();
    }

    /**
     * Saves modified transcription data to the session.
     *
     * @param Request $request The HTTP request object containing the updated transcription data.
     * @param Response $response The HTTP response object.
     */
    public function saveTranscription(
        Request $request,
        Response $response
    ): void {
        $session = Application::getInstance()->session;
        
        // Get the current transcription data from session
        $currentTranscriptionData = $session->get("transcription_data");
        
        if (!$currentTranscriptionData) {
            $response
                ->errorJson(
                    "TRANSCRIPTION_NOT_FOUND",
                    "No existing transcription data found to update.",
                    null,
                    404
                )
                ->send();
            return;
        }
        
        // Get the updated transcription data from the request
        $updatedCaptions = $request->input("captions");
        
        if (!$updatedCaptions || !is_array($updatedCaptions)) {
            $response
                ->errorJson(
                    "VALIDATION_ERROR",
                    "Invalid captions data provided. Expected an array of caption objects.",
                    null,
                    400
                )
                ->send();
            return;
        }
        
        // Validate caption structure
        foreach ($updatedCaptions as $index => $caption) {
            if (!isset($caption['text']) || !isset($caption['startMs']) || !isset($caption['endMs'])) {
                $response
                    ->errorJson(
                        "VALIDATION_ERROR",
                        "Invalid caption structure at index {$index}. Each caption must have 'text', 'startMs', and 'endMs' properties.",
                        ["invalid_caption_index" => $index],
                        400
                    )
                    ->send();
                return;
            }
            
            // Validate timestamp values
            if (!is_numeric($caption['startMs']) || !is_numeric($caption['endMs']) || $caption['startMs'] < 0 || $caption['endMs'] <= $caption['startMs']) {
                $response
                    ->errorJson(
                        "VALIDATION_ERROR",
                        "Invalid timestamps at caption index {$index}. Start time must be non-negative and less than end time.",
                        ["invalid_caption_index" => $index],
                        400
                    )
                    ->send();
                return;
            }
        }
        
        // Update the transcription data with the new captions
        $currentTranscriptionData['captions'] = $updatedCaptions;
        $currentTranscriptionData['last_modified'] = time();
        
        // Recalculate duration based on the last caption
        if (!empty($updatedCaptions)) {
            $lastCaption = end($updatedCaptions);
            $currentTranscriptionData['duration'] = $lastCaption['endMs'] / 1000; // Convert to seconds
        }
        
        // Save the updated transcription data back to the session
        $session->set("transcription_data", $currentTranscriptionData);
        
        $response
            ->json(
                [
                    "transcription" => $currentTranscriptionData,
                    "message" => "Transcription saved successfully."
                ],
                "Transcription data updated successfully.",
                200
            )
            ->send();
    }
}
