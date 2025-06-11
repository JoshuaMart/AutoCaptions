// modules/api-client.js
// Client API pour communiquer avec le backend PHP refactorisé

export class ApiClient {
    constructor() {
        this.baseUrl = window.location.origin;
        this.defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest'
        };
        
        // Configuration
        this.config = {
            timeout: 30000, // 30 seconds
            retryAttempts: 3,
            retryDelay: 1000 // 1 second
        };
    }

    async request(method, endpoint, data = null, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const config = {
            method: method.toUpperCase(),
            headers: { ...this.defaultHeaders },
            ...options
        };

        // Add CSRF token if available
        const csrfToken = this.getCSRFToken();
        if (csrfToken) {
            config.headers['X-CSRF-Token'] = csrfToken;
        }

        // Handle different data types
        if (data) {
            if (data instanceof FormData) {
                // Don't set Content-Type for FormData, let browser set it with boundary
                config.body = data;
            } else if (typeof data === 'object') {
                config.headers['Content-Type'] = 'application/json';
                config.body = JSON.stringify(data);
            } else {
                config.body = data;
            }
        }

        try {
            console.log(`🌐 API Request: ${method} ${endpoint}`);
            
            const response = await this.fetchWithTimeout(url, config);
            return await this.handleResponse(response);
            
        } catch (error) {
            console.error(`🌐 API Error: ${method} ${endpoint}`, error);
            throw this.normalizeError(error);
        }
    }

    async fetchWithTimeout(url, config) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), this.config.timeout);
        
        try {
            const response = await fetch(url, {
                ...config,
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('Request timeout');
            }
            throw error;
        }
    }

    async handleResponse(response) {
        const contentType = response.headers.get('Content-Type') || '';
        
        try {
            if (contentType.includes('application/json')) {
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error?.message || data.error || `HTTP ${response.status}: ${response.statusText}`);
                }
                
                return data;
                
            } else if (contentType.includes('video/') || contentType.includes('image/')) {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return {
                    success: true,
                    blob: await response.blob(),
                    contentType: contentType
                };
                
            } else {
                const text = await response.text();
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return {
                    success: true,
                    text: text,
                    contentType: contentType
                };
            }
            
        } catch (error) {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            throw error;
        }
    }

    normalizeError(error) {
        if (error.message) {
            return new Error(error.message);
        }
        return new Error('Network request failed');
    }

    getCSRFToken() {
        // Try to get CSRF token from meta tag or cookie
        const metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            return metaToken.getAttribute('content');
        }
        
        // Fallback to cookie
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const [name, value] = cookie.trim().split('=');
            if (name === 'csrf_token') {
                return decodeURIComponent(value);
            }
        }
        
        return null;
    }

    // Convenience methods for common HTTP verbs
    async get(endpoint, options = {}) {
        return this.request('GET', endpoint, null, options);
    }

    async post(endpoint, data = null, options = {}) {
        return this.request('POST', endpoint, data, options);
    }

    async put(endpoint, data = null, options = {}) {
        return this.request('PUT', endpoint, data, options);
    }

    async patch(endpoint, data = null, options = {}) {
        return this.request('PATCH', endpoint, data, options);
    }

    async delete(endpoint, options = {}) {
        return this.request('DELETE', endpoint, null, options);
    }

    // Helper method to download files
    async downloadFile(endpoint, filename = null) {
        try {
            const response = await this.get(endpoint);
            
            if (response.blob) {
                const url = window.URL.createObjectURL(response.blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = filename || 'download';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                return true;
            }
            
            throw new Error('Invalid file response');
            
        } catch (error) {
            console.error('🌐 Download failed:', error);
            throw error;
        }
    }

    // Method to call microservices through the PHP proxy (similar to original API.call)
    async callService(service, endpoint, method = 'GET', data = null, files = null) {
        const url = `/api/proxy/${service}/${endpoint}`;
        
        if (files || (data && data instanceof FormData)) {
            const formData = data instanceof FormData ? data : new FormData();
            
            if (files) {
                Object.keys(files).forEach(key => {
                    formData.append(key, files[key]);
                });
            }
            
            return this.request(method, url, formData);
        }
        
        return this.request(method, url, data);
    }

    // Health check for all services
    async checkServicesHealth() {
        return this.get('/api/config/services/status');
    }

    // Configuration management
    async getServicesConfig() {
        return this.get('/api/config/services');
    }

    async updateServiceConfig(config) {
        return this.post('/api/config/services', config);
    }

    // Upload management
    async uploadFile(file) {
        const formData = new FormData();
        formData.append('videoFile', file);
        return this.post('/api/upload', formData);
    }

    async deleteUpload() {
        return this.delete('/api/upload');
    }

    // Transcription management
    async startTranscription(file, service = 'whisper-cpp') {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('service', service);
        return this.post('/api/transcription/start', formData);
    }

    async getCurrentTranscription() {
        return this.get('/api/transcription/current');
    }

    async clearTranscription() {
        return this.delete('/api/transcription/clear');
    }

    // Method to handle streaming responses (for video downloads)
    async streamResponse(endpoint, onProgress = null) {
        const response = await fetch(`${this.baseUrl}${endpoint}`, {
            headers: this.defaultHeaders
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        if (onProgress && response.body) {
            const reader = response.body.getReader();
            const contentLength = +response.headers.get('Content-Length');
            let receivedLength = 0;
            const chunks = [];

            while (true) {
                const { done, value } = await reader.read();
                
                if (done) break;
                
                chunks.push(value);
                receivedLength += value.length;
                
                if (contentLength) {
                    onProgress(receivedLength / contentLength);
                }
            }

            const blob = new Blob(chunks);
            return {
                success: true,
                blob: blob,
                contentType: response.headers.get('Content-Type')
            };
        }

        return this.handleResponse(response);
    }
}