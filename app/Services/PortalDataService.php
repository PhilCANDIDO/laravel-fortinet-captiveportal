<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class PortalDataService
{
    const SESSION_KEY = 'fortigate_portal_data';
    const SESSION_TTL = 3600; // 1 hour

    /**
     * Decode and validate portal data from base64 JSON
     *
     * @param string $encodedData Base64 encoded JSON string
     * @return array|null Decoded portal data or null if invalid
     */
    public function decodePortalData(string $encodedData): ?array
    {
        try {
            // Decode base64
            $jsonData = base64_decode($encodedData, true);
            if ($jsonData === false) {
                Log::warning('Portal data: Failed to decode base64', ['data' => $encodedData]);
                return null;
            }

            // Parse JSON
            $data = json_decode($jsonData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Portal data: Failed to parse JSON', [
                    'error' => json_last_error_msg(),
                    'data' => $jsonData
                ]);
                return null;
            }

            // Log raw portal data if in debug mode
            if (config('app.debug') && config('logging.default') && config('logging.channels.' . config('logging.default') . '.level') === 'debug') {
                Log::debug('Portal data: Raw decoded data', [
                    'raw_data' => $data,
                    'json_pretty' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ]);
            }

            // Normalize the data before validation
            $data = $this->normalizePortalData($data);

            // Log normalized portal data if in debug mode
            if (config('app.debug') && config('logging.default') && config('logging.channels.' . config('logging.default') . '.level') === 'debug') {
                Log::debug('Portal data: Normalized data', [
                    'normalized_data' => $data,
                    'json_pretty' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                ]);
            }

            // Validate required fields
            if (!$this->validatePortalData($data)) {
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Portal data: Decoding error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Normalize portal data from FortiGate format to our standard format
     *
     * @param array $data Raw portal data
     * @return array Normalized portal data
     */
    protected function normalizePortalData(array $data): array
    {
        // Convert auth_post_url to auth_url
        if (isset($data['auth_post_url']) && !isset($data['auth_url'])) {
            if (isset($data['portal_url'])) {
                // For FortiGate, we need to use the portal_url as the base for auth
                // The portal_url format is: http://192.168.20.1:1000/fgtauth?MAGIC
                // We should post back to the same URL with the magic token preserved
                $data['auth_url'] = $data['portal_url'];
            }
        }
        
        // Extract magic from portal_url if not provided separately
        if (!isset($data['magic']) && isset($data['portal_url'])) {
            $parsedUrl = parse_url($data['portal_url']);
            if (isset($parsedUrl['query'])) {
                // The magic token is often the query parameter itself
                $data['magic'] = $parsedUrl['query'];
            }
        }
        
        // Map FortiGate field names to our standard format
        if (isset($data['magic_value']) && !isset($data['magic'])) {
            $data['magic'] = $data['magic_value'];
        }
        if (isset($data['redir_value']) && !isset($data['redirect_url'])) {
            $data['redirect_url'] = $data['redir_value'];
        }
        
        // Build form_fields from individual field IDs
        if (!isset($data['form_fields'])) {
            $data['form_fields'] = [];
            if (isset($data['username_id'])) {
                $data['form_fields']['username_field'] = $data['username_id'];
            }
            if (isset($data['password_id'])) {
                $data['form_fields']['password_field'] = $data['password_id'];
            }
            if (isset($data['magic_id'])) {
                $data['form_fields']['magic_field'] = $data['magic_id'];
            }
            if (isset($data['redir_id'])) {
                $data['form_fields']['redirect_field'] = $data['redir_id'];
            }
        }
        
        return $data;
    }

    /**
     * Validate portal data structure
     *
     * @param array $data Portal data to validate
     * @return bool
     */
    protected function validatePortalData(array $data): bool
    {
        $rules = [
            'portal_url' => 'required|string',
            'auth_url' => 'required|string',
            'magic' => 'nullable|string',
            'client_mac' => 'nullable|string',
            'client_ip' => 'nullable|string',
            'ap_mac' => 'nullable|string',
            'ssid' => 'nullable|string',
            'redirect_url' => 'nullable|string',
            'form_fields' => 'nullable|array',
            'form_fields.username_field' => 'nullable|string',
            'form_fields.password_field' => 'nullable|string',
            'form_fields.magic_field' => 'nullable|string',
            'form_fields.redirect_field' => 'nullable|string',
        ];

        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            Log::warning('Portal data: Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'data' => $data
            ]);
            return false;
        }

        return true;
    }

    /**
     * Store portal data in session
     *
     * @param array $portalData Portal data to store
     * @return void
     */
    public function storeInSession(array $portalData): void
    {
        Session::put(self::SESSION_KEY, $portalData);
        Session::put(self::SESSION_KEY . '_timestamp', time());
    }

    /**
     * Retrieve portal data from session
     *
     * @return array|null Portal data or null if not found/expired
     */
    public function getFromSession(): ?array
    {
        $data = Session::get(self::SESSION_KEY);
        $timestamp = Session::get(self::SESSION_KEY . '_timestamp');

        if (!$data || !$timestamp) {
            return null;
        }

        // Check if data has expired
        if ((time() - $timestamp) > self::SESSION_TTL) {
            $this->clearFromSession();
            return null;
        }

        return $data;
    }

    /**
     * Clear portal data from session
     *
     * @return void
     */
    public function clearFromSession(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::SESSION_KEY . '_timestamp');
    }

    /**
     * Generate FortiGate authentication URL with credentials
     *
     * @param array $portalData Portal data containing auth URL and form fields
     * @param string $username Username for authentication
     * @param string $password Password for authentication
     * @return string Authentication URL with parameters
     */
    public function generateAuthUrl(array $portalData, string $username, string $password): string
    {
        $authUrl = $portalData['auth_url'] ?? '';
        if (empty($authUrl)) {
            throw new \InvalidArgumentException('Auth URL is missing from portal data');
        }

        // Parse existing URL
        $urlParts = parse_url($authUrl);
        $queryParams = [];
        
        // Check if the URL already has a magic token in the query string (FortiGate format)
        // Format: http://192.168.20.1:1000/fgtauth?MAGIC_TOKEN
        if (isset($urlParts['query']) && !empty($urlParts['query'])) {
            // If query string doesn't contain '=', it's likely the magic token itself
            if (strpos($urlParts['query'], '=') === false) {
                // This is the magic token, preserve it as the first parameter
                $magicToken = $urlParts['query'];
                $queryParams[$magicToken] = '';  // Magic token is the key with no value
            } else {
                parse_str($urlParts['query'], $queryParams);
            }
        }

        // Add or override authentication parameters
        $formFields = $portalData['form_fields'] ?? [];
        
        // Username field (default to 'username' if not specified)
        $usernameField = $formFields['username_field'] ?? 'username';
        $queryParams[$usernameField] = $username;
        
        // Password field (default to 'password' if not specified)
        $passwordField = $formFields['password_field'] ?? 'password';
        $queryParams[$passwordField] = $password;
        
        // Magic token if present and not already in URL
        if (!empty($portalData['magic']) && !isset($queryParams[$portalData['magic']])) {
            $magicField = $formFields['magic_field'] ?? 'magic';
            // If magic field is 'magic', add it as a parameter
            if ($magicField === 'magic') {
                $queryParams[$magicField] = $portalData['magic'];
            }
        }
        
        // Redirect URL if present
        if (!empty($portalData['redirect_url'])) {
            $redirectField = $formFields['redirect_field'] ?? 'redir';  // FortiGate typically uses 'redir'
            $queryParams[$redirectField] = $portalData['redirect_url'];
        }

        // Add any additional parameters from portal data
        if (!empty($portalData['client_mac'])) {
            $queryParams['client_mac'] = $portalData['client_mac'];
        }
        if (!empty($portalData['client_ip'])) {
            $queryParams['client_ip'] = $portalData['client_ip'];
        }
        if (!empty($portalData['ap_mac'])) {
            $queryParams['ap_mac'] = $portalData['ap_mac'];
        }
        if (!empty($portalData['ssid'])) {
            $queryParams['ssid'] = $portalData['ssid'];
        }

        // Build the final URL
        $scheme = $urlParts['scheme'] ?? 'https';
        $host = $urlParts['host'] ?? '';
        $port = isset($urlParts['port']) ? ':' . $urlParts['port'] : '';
        $path = $urlParts['path'] ?? '/';
        
        // Build query string manually to preserve FortiGate format
        $queryString = '';
        $first = true;
        foreach ($queryParams as $key => $value) {
            if ($first) {
                $queryString .= $key;
                if ($value !== '') {
                    $queryString .= '=' . urlencode($value);
                }
                $first = false;
            } else {
                $queryString .= '&' . $key;
                if ($value !== '') {
                    $queryString .= '=' . urlencode($value);
                }
            }
        }
        
        $finalUrl = $scheme . '://' . $host . $port . $path . '?' . $queryString;
        
        // Log generated auth URL if in debug mode
        if (config('logging.channels.' . config('logging.default') . '.level') === 'debug') {
            Log::debug('Portal data: Generated auth URL', [
                'auth_url' => $finalUrl,
                'username' => $username,
                'query_params' => $queryParams
            ]);
        }
        
        return $finalUrl;
    }

    /**
     * Extract portal information for display
     *
     * @param array $portalData Portal data
     * @return array Display-friendly portal information
     */
    public function getPortalInfo(array $portalData): array
    {
        // Determine network type based on presence of SSID
        $networkType = 'wired'; // Default to wired
        $networkName = __('guest.wired_network');
        
        if (!empty($portalData['ssid'])) {
            $networkType = 'wireless';
            $networkName = $portalData['ssid'];
        }
        
        return [
            'network_type' => $networkType,
            'network_name' => $networkName,
            'ssid' => $portalData['ssid'] ?? null,  // Keep original SSID if present
            'client_ip' => $portalData['client_ip'] ?? 'N/A',
            'ap_mac' => $portalData['ap_mac'] ?? 'N/A',
            'has_auto_auth' => !empty($portalData['auth_url']),
            'portal_url' => $portalData['portal_url'] ?? null,
        ];
    }

    /**
     * Check if portal data contains auto-authentication capability
     *
     * @param array|null $portalData Portal data
     * @return bool
     */
    public function hasAutoAuth(?array $portalData): bool
    {
        return $portalData !== null && !empty($portalData['auth_url']);
    }

    /**
     * Sanitize portal data for storage or display
     *
     * @param array $portalData Portal data to sanitize
     * @return array Sanitized portal data
     */
    public function sanitizePortalData(array $portalData): array
    {
        $sanitized = [];
        
        // Sanitize URLs
        $urlFields = ['portal_url', 'auth_url', 'redirect_url'];
        foreach ($urlFields as $field) {
            if (isset($portalData[$field])) {
                $sanitized[$field] = filter_var($portalData[$field], FILTER_SANITIZE_URL);
            }
        }
        
        // Sanitize strings
        $stringFields = ['magic', 'client_mac', 'client_ip', 'ap_mac', 'ssid'];
        foreach ($stringFields as $field) {
            if (isset($portalData[$field])) {
                $sanitized[$field] = htmlspecialchars($portalData[$field], ENT_QUOTES, 'UTF-8');
            }
        }
        
        // Sanitize form fields
        if (isset($portalData['form_fields']) && is_array($portalData['form_fields'])) {
            $sanitized['form_fields'] = [];
            foreach ($portalData['form_fields'] as $key => $value) {
                $sanitized['form_fields'][htmlspecialchars($key)] = htmlspecialchars($value);
            }
        }
        
        return $sanitized;
    }
}