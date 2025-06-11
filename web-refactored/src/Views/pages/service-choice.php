<?php
// Views/pages/service-choice.php

$pageTitle = "Choose Caption Service - AutoCaptions";
$pageDescription = "Select the rendering engine that best fits your needs";
?>

<div class="text-center mb-10">
    <h1 class="text-3xl font-bold text-gray-900 mb-2">Choose Your Caption Service</h1>
    <p class="text-lg text-gray-600">Select the rendering engine that best fits your needs</p>
</div>

<!-- Progress Steps -->
<div class="mb-8">
    <div class="flex items-center justify-center space-x-8">
        <!-- Step 1: Upload -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">✓</div>
            <span class="ml-2 text-sm font-medium text-green-600">Upload</span>
        </div>
        <div class="flex-1 h-px bg-green-600"></div>

        <!-- Step 2: Transcription -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">✓</div>
            <span class="ml-2 text-sm font-medium text-green-600">Transcription</span>
        </div>
        <div class="flex-1 h-px bg-green-600"></div>

        <!-- Step 3: Service Choice (Current) -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-medium">3</div>
            <span class="ml-2 text-sm font-medium text-blue-600">Service Choice</span>
        </div>
        <div class="flex-1 h-px bg-gray-300"></div>

        <!-- Step 4: Configuration -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">4</div>
            <span class="ml-2 text-sm font-medium text-gray-500">Configuration</span>
        </div>
        <div class="flex-1 h-px bg-gray-300"></div>

        <!-- Step 5: Generate -->
        <div class="flex items-center">
            <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-600 rounded-full text-sm font-medium">5</div>
            <span class="ml-2 text-sm font-medium text-gray-500">Generate</span>
        </div>
    </div>
</div>

<!-- Service Selection Cards -->
<div class="grid md:grid-cols-2 gap-8 mb-8">
    <!-- FFmpeg Service Card -->
    <div id="ffmpeg-card"
        class="service-card bg-white rounded-xl shadow-lg p-8 relative border-2 border-transparent transition-all cursor-pointer hover:border-blue-500"
        onclick="selectService('ffmpeg')">
        <!-- Fast badge -->
        <span class="absolute top-4 right-4 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700 flex items-center">
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z" clip-rule="evenodd"/></svg>
            Fast
        </span>
        <div class="mb-6 flex items-center">
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <div class="ml-4">
                <h3 class="text-xl font-bold text-gray-900">FFmpeg Captions</h3>
                <p class="text-sm text-gray-500">Fast & Efficient</p>
            </div>
        </div>
        <p class="text-gray-600 mb-4">Quick subtitle generation using FFmpeg with ASS styling. Perfect for simple, clean captions.</p>
        <ul class="mb-4 space-y-1">
            <li class="flex items-center text-green-700"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Lightning fast processing (30s - 2min)</li>
            <li class="flex items-center text-green-700"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Built-in presets & Google Fonts</li>
            <li class="flex items-center text-green-700"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Preview frame generation</li>
            <li class="flex items-center text-green-700"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Color & outline customization</li>
            <li class="flex items-center text-gray-400"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>Limited animation options</li>
        </ul>
        <div class="bg-orange-50 rounded-lg p-4">
            <h4 class="font-medium text-orange-900 mb-2">Best for:</h4>
            <ul class="text-sm text-orange-700 space-y-1">
                <li>• Quick turnaround projects</li>
                <li>• Simple, clean caption styles</li>
                <li>• Batch processing multiple videos</li>
                <li>• Educational or professional content</li>
            </ul>
        </div>
        <!-- Radio visually hidden, just for a11y, can link if needed -->
        <input type="radio" name="rendering-service" value="ffmpeg" class="hidden" aria-label="FFmpeg captions" />
    </div>

    <!-- Remotion Service Card -->
    <div id="remotion-card"
        class="service-card bg-white rounded-xl shadow-lg p-8 relative border-2 border-transparent transition-all opacity-60 pointer-events-none select-none">
        <!-- Overlay -->
        <div class="absolute inset-0 flex items-center justify-center bg-gray-400/60 rounded-xl z-10">
            <span class="text-white font-semibold text-lg">Not available yet</span>
        </div>
        <!-- Creative badge -->
        <span class="absolute top-4 right-4 px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700 flex items-center">
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
            Creative
        </span>
        <div class="mb-6 flex items-center">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 48 48" stroke="#8E24AA"><g><path d="M21.37 36C22.82 30.75 27.89 27 33.73 27.62C39.29 28.21 43.71 32.9 43.99 38.48C44.06 39.95 43.86 41.36 43.43 42.67C43.17 43.47 42.39 44 41.54 44H11.7584C6.71004 44 2.92371 39.3814 3.91377 34.4311L9.99994 4H21.9999L25.9999 11L17.43 17.13L14.9999 14"/><path d="M17.4399 17.13L22 34"/></g></svg>
            </div>
            <div class="ml-4">
                <h3 class="text-xl font-bold text-gray-900">Remotion Captions</h3>
                <p class="text-sm text-gray-500">Advanced & Customizable</p>
            </div>
        </div>
        <p class="text-gray-600 mb-4">Professional video captions with React-powered animations and advanced styling options.</p>
        <ul class="mb-4 space-y-1">
            <li class="flex items-center text-purple-700"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Word-by-word highlighting animations</li>
            <li class="flex items-center text-purple-700"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Custom background highlights</li>
            <li class="flex items-center text-purple-700"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>React-based styling system</li>
            <li class="flex items-center text-purple-700"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Platform-specific presets (TikTok, Instagram)</li>
            <li class="flex items-center text-gray-400"><svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>Slower processing (2-10min)</li>
        </ul>
        <div class="bg-purple-50 rounded-lg p-4">
            <h4 class="font-medium text-purple-900 mb-2">Best for:</h4>
            <ul class="text-sm text-purple-700 space-y-1">
                <li>• Social media content (TikTok, Instagram)</li>
                <li>• Creative projects with custom branding</li>
                <li>• Marketing videos requiring impact</li>
                <li>• Content with dynamic word highlights</li>
            </ul>
        </div>
        <input type="radio" name="rendering-service" value="remotion" class="hidden" aria-label="Remotion captions" disabled />
    </div>
</div>

<!-- Action Buttons -->
<div class="flex justify-between mt-6">
    <button type="button" onclick="window.history.back()" class="inline-flex items-center px-5 py-3 border border-gray-300 text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none">
        &larr; Back to Transcription
    </button>
    <button type="button" id="continue-btn" onclick="continueWithService()" disabled
        class="inline-flex items-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 disabled:bg-gray-400 disabled:cursor-not-allowed focus:outline-none">
        Continue to Configuration &rarr;
    </button>
</div>

<script>
let selectedService = null;

function selectService(service) {
    selectedService = service;
    // Highlight card
    document.getElementById('ffmpeg-card').classList.remove('ring-2','ring-blue-500','bg-blue-50');
    if(service === 'ffmpeg'){
        document.getElementById('ffmpeg-card').classList.add('ring-2','ring-blue-500','bg-blue-50');
    }
    // Enable continue button
    const btn = document.getElementById('continue-btn');
    btn.disabled = false;
    btn.classList.remove('bg-gray-400', 'disabled:cursor-not-allowed');
    btn.classList.add('bg-blue-600');
}

function continueWithService() {
    if (!selectedService) return;
    // Change this redirect if needed for your logic
    window.location.href = '/configuration?service=' + selectedService;
}
</script>
