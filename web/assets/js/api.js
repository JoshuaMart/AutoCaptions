/**
 * API Helper Functions for AutoCaptions
 */

const API = {
    /**
     * Make a proxied API call to a microservice
     */
    async call(service, endpoint, method = 'GET', data = null, files = null) {
        const url = `/api/proxy.php?service=${encodeURIComponent(service)}&endpoint=${encodeURIComponent(endpoint)}`;
        
        const options = {
            method: method,
            headers: {}
        };
        
        // Handle different data types
        if (method === 'POST' || method === 'PUT') {
            if (files) {
                // Handle file uploads with FormData
                const formData = new FormData();
                
                // Add files
                if (files) {
                    Object.keys(files).forEach(key => {
                        formData.append(key, files[key]);
                    });
                }
                
                // Add other data
                if (data && typeof data === 'object' && !(data instanceof FormData)) {
                    Object.keys(data).forEach(key => {
                        formData.append(key, data[key]);
                    });
                }
                
                options.body = formData;
                // Don't set Content-Type for FormData, let browser set it
            } else if (data) {
                if (typeof data === 'object') {
                    options.headers['Content-Type'] = 'application/json';
                    options.body = JSON.stringify(data);
                } else {
                    options.body = data;
                }
            }
        }
        
        try {
            console.log(`🔗 API Call: ${method} ${url}`);
            
            const response = await fetch(url, options);
            
            console.log(`📡 Response: ${response.status} ${response.statusText}`);
            
            // Handle different response types
            const contentType = response.headers.get('Content-Type') || '';
            
            if (contentType.includes('application/json')) {
                const result = await response.json();
                return {
                    ...result,
                    httpStatus: response.status
                };
            } else if (contentType.includes('video/') || contentType.includes('image/')) {
                return {
                    success: response.ok,
                    blob: await response.blob(),
                    contentType: contentType,
                    httpStatus: response.status
                };
            } else {
                return {
                    success: response.ok,
                    text: await response.text(),
                    contentType: contentType,
                    httpStatus: response.status
                };
            }
        } catch (error) {
            console.error('❌ API call failed:', error);
            throw new Error(`API call failed: ${error.message}`);
        }
    }
};

// Export for global access
window.API = API;
