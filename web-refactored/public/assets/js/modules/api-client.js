/**
 * ApiClient
 * A simple client for making HTTP requests to the backend API.
 */
export class ApiClient {
    constructor(baseApiUrl = '/api') {
        this.baseApiUrl = baseApiUrl.endsWith('/') ? baseApiUrl.slice(0, -1) : baseApiUrl;
    }

    /**
     * Constructs the full API URL.
     * @param {string} endpoint - The API endpoint (e.g., '/users').
     * @returns {string} The full API URL.
     */
    _buildUrl(endpoint) {
        return `${this.baseApiUrl}${endpoint.startsWith('/') ? endpoint : '/' + endpoint}`;
    }

    /**
     * Performs an HTTP request.
     * @param {string} endpoint - The API endpoint.
     * @param {string} method - The HTTP method (GET, POST, PUT, DELETE, etc.).
     * @param {object|FormData} [body=null] - The request body for POST, PUT, PATCH.
     * @param {object} [customHeaders={}] - Custom headers to include.
     * @returns {Promise<object>} A promise that resolves with the JSON response or rejects with an error.
     */
    async request(endpoint, method = 'GET', body = null, customHeaders = {}) {
        const url = this._buildUrl(endpoint);
        const options = {
            method: method.toUpperCase(),
            headers: {
                // 'X-Requested-With': 'XMLHttpRequest', // Common header for AJAX requests
                ...customHeaders, // Allow overriding default headers or adding new ones
            },
        };

        // Set Content-Type for JSON, unless it's FormData
        if (body && !(body instanceof FormData)) {
            if (!options.headers['Content-Type']) {
                options.headers['Content-Type'] = 'application/json';
            }
            options.body = JSON.stringify(body);
        } else if (body instanceof FormData) {
            // For FormData, 'Content-Type' is set automatically by the browser with the boundary.
            // Do not set it manually here.
            options.body = body;
        }

        // TODO: Add CSRF token handling if needed
        // const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        // if (csrfToken && ['POST', 'PUT', 'DELETE', 'PATCH'].includes(options.method)) {
        //     options.headers['X-CSRF-TOKEN'] = csrfToken;
        // }

        try {
            const response = await fetch(url, options);

            if (!response.ok) {
                let errorData;
                try {
                    // Try to parse error response as JSON
                    errorData = await response.json();
                } catch (e) {
                    // If not JSON, use text
                    errorData = { message: await response.text() || response.statusText };
                }
                // Construct a more informative error object
                const error = new Error(errorData.message || `HTTP error ${response.status}`);
                error.status = response.status;
                error.response = response; // Full response object
                error.data = errorData;    // Parsed error data from server
                throw error;
            }

            // Handle cases where the response might be empty (e.g., 204 No Content)
            if (response.status === 204) {
                return null; // Or return a specific success object: { success: true, data: null }
            }
            
            // Check content type before parsing as JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                // If not JSON, return text or blob as appropriate, or handle as error
                // For now, let's assume JSON is expected for most successful data responses.
                // If text is valid, then: return await response.text();
                // For this client, we'll primarily expect JSON.
                console.warn(`Response from ${url} was not JSON. Content-Type: ${contentType}`);
                return await response.text(); // Fallback to text if not JSON
            }

        } catch (error) {
            // Re-throw or handle network errors or previously thrown HTTP errors
            console.error(`ApiClient request failed: ${method} ${endpoint}`, error);
            throw error; // Propagate the error to the caller
        }
    }

    /**
     * Performs a GET request.
     * @param {string} endpoint - The API endpoint.
     * @param {object} [queryParams=null] - Object to be converted to query string.
     * @param {object} [headers={}] - Custom headers.
     * @returns {Promise<object>}
     */
    async get(endpoint, queryParams = null, headers = {}) {
        let urlWithParams = endpoint;
        if (queryParams) {
            const params = new URLSearchParams(queryParams);
            urlWithParams += `?${params.toString()}`;
        }
        return this.request(urlWithParams, 'GET', null, headers);
    }

    /**
     * Performs a POST request.
     * @param {string} endpoint - The API endpoint.
     * @param {object|FormData} body - The request body.
     * @param {object} [headers={}] - Custom headers.
     * @returns {Promise<object>}
     */
    async post(endpoint, body, headers = {}) {
        return this.request(endpoint, 'POST', body, headers);
    }

    /**
     * Performs a PUT request.
     * @param {string} endpoint - The API endpoint.
     * @param {object|FormData} body - The request body.
     * @param {object} [headers={}] - Custom headers.
     * @returns {Promise<object>}
     */
    async put(endpoint, body, headers = {}) {
        return this.request(endpoint, 'PUT', body, headers);
    }

    /**
     * Performs a DELETE request.
     * @param {string} endpoint - The API endpoint.
     * @param {object} [body=null] - Optional request body.
     * @param {object} [headers={}] - Custom headers.
     * @returns {Promise<object>}
     */
    async delete(endpoint, body = null, headers = {}) {
        return this.request(endpoint, 'DELETE', body, headers);
    }

    /**
     * Performs a PATCH request.
     * @param {string} endpoint - The API endpoint.
     * @param {object|FormData} body - The request body.
     * @param {object} [headers={}] - Custom headers.
     * @returns {Promise<object>}
     */
    async patch(endpoint, body, headers = {}) {
        return this.request(endpoint, 'PATCH', body, headers);
    }
}