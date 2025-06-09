<?php
/**
 * Header Component
 * Displays AutoCaptions logo, service status, and settings
 */

require_once __DIR__ . "/../config/services.php";

$services = getServicesConfig();
?>

<header class="bg-white shadow-sm border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Logo and Title -->
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6"fill="#ffffff" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M20 4H4c-1.103 0-2 .897-2 2v12c0 1.103.897 2 2 2h16c1.103 0 2-.897 2-2V6c0-1.103-.897-2-2-2zm-9 6H8v4h3v2H8c-1.103 0-2-.897-2-2v-4c0-1.103.897-2 2-2h3v2zm7 0h-3v4h3v2h-3c-1.103 0-2-.897-2-2v-4c0-1.103.897-2 2-2h3v2z"></path></g></svg>
                        </div>
                        <h1 class="ml-3 text-xl font-bold text-gray-900">AutoCaptions</h1>
                    </div>
                </div>

                <!-- Breadcrumb -->
                <nav class="ml-8 hidden md:flex" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2 text-sm text-gray-500">
                        <li><a href="/" class="hover:text-gray-700">Upload</a></li>
                        <li><span class="text-gray-300">/</span></li>
                        <li class="text-gray-900">Video Processing</li>
                    </ol>
                </nav>
            </div>

            <!-- Service Status and Settings -->
            <div class="flex items-center space-x-4">
                <!-- Service Status Indicators -->
                <div class="flex items-center space-x-3">
                    <?php foreach ($services as $serviceKey => $serviceInfo): ?>
                    <div class="flex items-center space-x-1"
                         data-service="<?= htmlspecialchars($serviceKey) ?>"
                         title="<?= htmlspecialchars(
                             $serviceInfo["description"]
                         ) ?>">
                        <!-- Status indicator (will be updated by JavaScript) -->
                        <div class="w-2 h-2 rounded-full bg-gray-400 service-status-dot transition-colors duration-200"
                             data-service-dot="<?= htmlspecialchars(
                                 $serviceKey
                             ) ?>"></div>
                        <span class="text-xs text-gray-600 hidden sm:inline">
                            <?= htmlspecialchars($serviceInfo["name"]) ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Overall Status -->
                <div class="flex items-center space-x-2 px-3 py-1 rounded-full bg-gray-100" id="overall-status">
                    <div class="w-2 h-2 rounded-full bg-gray-400" id="overall-status-dot"></div>
                    <span class="text-xs font-medium text-gray-600" id="overall-status-text">Checking...</span>
                </div>

                <!-- Settings Button -->
                <button type="button"
                        onclick="openSettingsModal()"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors duration-200"
                        title="Configure service URLs">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Service Status Details (Hidden by default, shown on hover) -->
<div id="service-status-tooltip"
     class="fixed z-50 px-3 py-2 text-xs text-white bg-gray-900 rounded-lg shadow-lg opacity-0 pointer-events-none transition-opacity duration-200"
     style="display: none;">
    <div id="tooltip-content"></div>
</div>
