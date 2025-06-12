<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;
use App\Services\ServiceManager;

class FFmpegController
{
    private Application $app;
    private ServiceManager $serviceManager;

    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->serviceManager = new ServiceManager($this->app->configManager);
    }

    /**
     * Show the FFmpeg configuration page
     */
    public function showConfiguration(Request $request, Response $response): void
    {
        // Check if we have transcription data in session or demo mode
        $transcriptionData = $this->app->session->get('transcription_data');
        $isDemo = $request->query('demo') === 'true';

        if (!$transcriptionData && !$isDemo) {
            // Redirect to transcription page if no data
            $response->redirect('/transcriptions');
            return;
        }

        // Check if service selected is ffmpeg
        $selectedService = $this->app->session->get('selected_service', 'ffmpeg');
        if ($selectedService !== 'ffmpeg') {
            $response->redirect('/service-choice');
            return;
        }

        $this->app->renderView('pages/ffmpeg-config', [
            'pageTitle' => 'FFmpeg Configuration - AutoCaptions',
            'pageDescription' => 'Customize your caption style and preview the result',
            'transcriptionData' => $transcriptionData,
            'isDemo' => $isDemo,
            'selectedService' => $selectedService
        ]);
    }

    /**
     * Get available presets from FFmpeg service
     */
    public function getPresets(Request $request, Response $response): void
    {
        try {
            $result = $this->serviceManager->makeRequest('ffmpeg-captions', 'presets', 'GET');
            if ($result['success'] && isset($result['body'])) {
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent(json_encode($result['body']));
                $response->send();
            } else {
                throw new \Exception('Invalid response from service');
            }
        } catch (\Exception $e) {
            $response->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVICE_ERROR',
                    'message' => 'Failed to load presets: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get specific preset details
     */
    public function getPreset(Request $request, Response $response): void
    {
        $presetName = $request->getRouteParam('preset');
        
        if (!$presetName) {
            $response->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST',
                    'message' => 'Preset name is required'
                ]
            ], 400);
            return;
        }

        try {
            $result = $this->serviceManager->makeRequest('ffmpeg-captions', 'preset_detail', 'GET', [], [], [], ['preset' => $presetName]);
            if ($result['success'] && isset($result['body'])) {
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent(json_encode($result['body']));
                $response->send();
            } else {
                throw new \Exception('Invalid response from service');
            }
        } catch (\Exception $e) {
            $response->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVICE_ERROR',
                    'message' => 'Failed to load preset details: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get available fonts from FFmpeg service
     */
    public function getFonts(Request $request, Response $response): void
    {
        try {
            $category = $request->query('category');
            $queryParams = $category ? ['category' => $category] : [];
            $result = $this->serviceManager->makeRequest('ffmpeg-captions', 'fonts', 'GET', [], [], $queryParams);
            if ($result['success'] && isset($result['body'])) {
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent(json_encode($result['body']));
                $response->send();
            } else {
                throw new \Exception('Invalid response from service');
            }
        } catch (\Exception $e) {
            $response->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVICE_ERROR',
                    'message' => 'Failed to load fonts: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get font variants for a specific font family
     */
    public function getFontVariants(Request $request, Response $response): void
    {
        $fontFamily = $request->getRouteParam('family');
        
        if (!$fontFamily) {
            $response->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_REQUEST',
                    'message' => 'Font family is required'
                ]
            ], 400);
            return;
        }

        try {
            $result = $this->serviceManager->makeRequest('ffmpeg-captions', 'font_variants', 'GET', [], [], [], ['family' => $fontFamily]);
            if ($result['success'] && isset($result['body'])) {
                $response->setHeader('Content-Type', 'application/json');
                $response->setContent(json_encode($result['body']));
                $response->send();
            } else {
                throw new \Exception('Invalid response from service');
            }
        } catch (\Exception $e) {
            $response->json([
                'success' => false,
                'error' => [
                    'code' => 'SERVICE_ERROR',
                    'message' => 'Failed to load font variants: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Generate preview frame
     */
    public function generatePreview(Request $request, Response $response): void
    {
        try {
            // Get video file from session
            $uploadData = $this->app->session->get('upload_data');
            if (!$uploadData) {
                throw new \Exception('No video file found in session');
            }

            $videoPath = $uploadData['path'];
            if (!file_exists($videoPath)) {
                throw new \Exception('Video file not found');
            }

            // Get configuration data from request
            $configData = $request->getBody();
            if (!$configData) {
                throw new \Exception('Configuration data is required');
            }

            // Get transcription data
            $transcriptionData = $this->app->session->get('transcription_data');
            if (!$transcriptionData) {
                throw new \Exception('No transcription data found');
            }

            // Prepare data for FFmpeg service
            $data = [
                'preset' => $configData['preset'] ?? 'custom',
                'customStyle' => $configData['customStyle'] ?? [],
                'transcriptionData' => $transcriptionData
            ];

            // Get optional parameters
            $timestamp = $request->query('timestamp');
            $position = $request->query('position', 'middle');

            $params = [];
            if ($timestamp) {
                $params['timestamp'] = $timestamp;
            }
            if ($position) {
                $params['position'] = $position;
            }

            // Call FFmpeg service with file upload
            $files = ['video' => $videoPath];
            $result = $this->serviceManager->makeRequest(
                'ffmpeg-captions',
                'preview',
                'POST',
                ['data' => json_encode($data)],
                $files,
                $params
            );

            // Return image directly
            if ($result['success'] && isset($result['body'])) {
                $response->setHeader('Content-Type', 'image/png');
                $response->setContent($result['body']);
                $response->send();
            } else {
                throw new \Exception('Failed to generate preview');
            }

        } catch (\Exception $e) {
            $response->json([
                'success' => false,
                'error' => [
                    'code' => 'PREVIEW_ERROR',
                    'message' => 'Failed to generate preview: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Generate final video with captions
     */
    public function generateVideo(Request $request, Response $response): void
    {
        try {
            // Get video file from session
            $uploadData = $this->app->session->get('upload_data');
            if (!$uploadData) {
                throw new \Exception('No video file found in session');
            }

            $videoPath = $uploadData['path'];
            if (!file_exists($videoPath)) {
                throw new \Exception('Video file not found');
            }

            // Get configuration data from request
            $configData = $request->getBody();
            if (!$configData) {
                throw new \Exception('Configuration data is required');
            }

            // Get transcription data
            $transcriptionData = $this->app->session->get('transcription_data');
            if (!$transcriptionData) {
                throw new \Exception('No transcription data found');
            }

            // Prepare data for FFmpeg service
            $data = [
                'preset' => $configData['preset'] ?? 'custom',
                'customStyle' => $configData['customStyle'] ?? [],
                'transcriptionData' => $transcriptionData
            ];

            // Call FFmpeg service with file upload
            $files = ['video' => $videoPath];
            $result = $this->serviceManager->makeRequest(
                'ffmpeg-captions',
                'generate',
                'POST',
                ['data' => json_encode($data)],
                $files
            );

            if ($result['success'] && isset($result['body'])) {
                // Store result info in session for result page
                $this->app->session->set('generation_result', [
                    'service' => 'ffmpeg',
                    'success' => true,
                    'size' => strlen($result['body']),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);

                // Return video file directly
                $response->setHeader('Content-Type', 'video/mp4');
                $response->setHeader('Content-Disposition', 'attachment; filename="captioned_video.mp4"');
                $response->setContent($result['body']);
                $response->send();
            } else {
                throw new \Exception('Failed to generate video');
            }

        } catch (\Exception $e) {
            $response->json([
                'success' => false,
                'error' => [
                    'code' => 'GENERATION_ERROR',
                    'message' => 'Failed to generate video: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Save current configuration to session
     */
    public function saveConfiguration(Request $request, Response $response): void
    {
        try {
            $configData = $request->getBody();
            
            if (!$configData) {
                throw new \Exception('Configuration data is required');
            }

            // Save to session
            $this->app->session->set('ffmpeg_config', $configData);

            $response->json([
                'success' => true,
                'message' => 'Configuration saved successfully'
            ]);

        } catch (\Exception $e) {
            $response->json([
                'success' => false,
                'error' => [
                    'code' => 'SAVE_ERROR',
                    'message' => 'Failed to save configuration: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get current configuration from session
     */
    public function getCurrentConfiguration(Request $request, Response $response): void
    {
        $config = $this->app->session->get('ffmpeg_config', []);
        
        $response->json([
            'success' => true,
            'config' => $config
        ]);
    }
}