<?php
/**
 * Settings Modal Component
 * Modal for configuring service URLs
 */

require_once __DIR__ . '/../config/services.php';
$services = getServicesConfig();
?>

<!-- Settings Modal -->
<div id="settings-modal" 
     class="fixed inset-0 z-50 overflow-y-auto hidden"
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    
    <!-- Background overlay -->
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
             onclick="closeSettingsModal()"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            
            <!-- Modal header -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                        Service Configuration
                    </h3>
                    <button type="button" 
                            onclick="closeSettingsModal()"
                            class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Service URL Configuration Form -->
                <form id="services-config-form" class="space-y-4">
                    <?php foreach ($services as $serviceKey => $serviceInfo): ?>
                    <div class="space-y-2">
                        <label for="service-<?= $serviceKey ?>" 
                               class="block text-sm font-medium text-gray-700">
                            <?= htmlspecialchars($serviceInfo['name']) ?>
                            <span class="text-gray-500 text-xs ml-1">
                                (<?= htmlspecialchars($serviceInfo['description']) ?>)
                            </span>
                        </label>
                        <div class="relative">
                            <input type="url" 
                                   id="service-<?= $serviceKey ?>"
                                   name="services[<?= $serviceKey ?>][url]"
                                   value="<?= htmlspecialchars($serviceInfo['url']) ?>"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                   placeholder="http://localhost:3001"
                                   required>
                            <!-- Status indicator for this service -->
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <div class="w-2 h-2 rounded-full bg-gray-400" 
                                     data-config-status="<?= $serviceKey ?>"></div>
                            </div>
                        </div>
                        <!-- Hidden field for service name -->
                        <input type="hidden" 
                               name="services[<?= $serviceKey ?>][name]" 
                               value="<?= htmlspecialchars($serviceInfo['name']) ?>">
                        <input type="hidden" 
                               name="services[<?= $serviceKey ?>][health_endpoint]" 
                               value="<?= htmlspecialchars($serviceInfo['health_endpoint']) ?>">
                        <input type="hidden" 
                               name="services[<?= $serviceKey ?>][description]" 
                               value="<?= htmlspecialchars($serviceInfo['description']) ?>">
                    </div>
                    <?php endforeach; ?>

                    <!-- Test Connection Button -->
                    <div class="pt-2">
                        <button type="button" 
                                onclick="testAllConnections()"
                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Test All Connections
                        </button>
                    </div>
                </form>
            </div>

            <!-- Modal footer -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="submit" 
                        form="services-config-form"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                    Save Configuration
                </button>
                <button type="button" 
                        onclick="resetToDefaults()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200">
                    Reset to Defaults
                </button>
                <button type="button" 
                        onclick="closeSettingsModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm transition-colors duration-200">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error notifications -->
<div id="notification" 
     class="fixed top-4 right-4 z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 max-w-sm">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div id="notification-icon"></div>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-gray-900" id="notification-title"></p>
                <p class="text-sm text-gray-500" id="notification-message"></p>
            </div>
        </div>
    </div>
</div>
