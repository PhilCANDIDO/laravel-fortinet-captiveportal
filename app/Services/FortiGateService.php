<?php

namespace App\Services;

use App\Exceptions\FortiGateApiException;
use App\Exceptions\FortiGateConnectionException;
use App\Models\FortiGateSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Client\PendingRequest;
use Exception;

class FortiGateService
{
    protected string $apiUrl;
    protected ?string $apiToken;
    protected array $config;
    protected FortiGateSettings $settings;
    protected ?string $circuitBreakerKey = 'fortigate_circuit_breaker';
    protected array $metrics = [
        'requests' => 0,
        'successes' => 0,
        'failures' => 0,
        'total_response_time' => 0,
    ];

    public function __construct()
    {
        // Load settings from database
        $this->settings = FortiGateSettings::current();
        
        // Use database settings
        $this->config = $this->settings->toConfig();
        
        // If no database settings, fallback to config file
        if (empty($this->config['api_url'])) {
            $this->config = config('fortigate', []);
        }
        
        $this->apiUrl = rtrim($this->config['api_url'] ?? '', '/');
        $this->apiToken = $this->config['api_token'] ?? null;
    }
    
    /**
     * Check if FortiGate service is properly configured and active
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiUrl) 
            && !empty($this->apiToken) 
            && $this->settings->is_active;
    }

    /**
     * Create a new user in FortiGate
     */
    public function createUser(array $userData): array
    {
        if (!$this->isConfigured()) {
            throw new FortiGateConnectionException('FortiGate service is not configured');
        }
        
        $endpoint = '/cmdb/user/local';
        
        // Step 1: Create the user without groups
        $payload = [
            'name' => $userData['username'],
            'passwd' => $userData['password'],
            'status' => $userData['status'] ?? 'enable',
            'type' => 'password',
            'two-factor' => 'disable',
            'email-to' => $userData['email'] ?? '',
        ];

        if (isset($userData['expires_at'])) {
            $payload['expiry-date'] = $userData['expires_at'];
        }

        $response = $this->request('POST', $endpoint, $payload);
        
        // Step 2: Add user to group if configured
        if (!empty($this->settings->user_group)) {
            try {
                $this->addUserToGroup($userData['username'], $this->settings->user_group);
            } catch (\Exception $e) {
                Log::warning("User created but could not add to group: {$e->getMessage()}");
            }
        }

        return $response;
    }
    
    /**
     * Add a user to a FortiGate group
     */
    public function addUserToGroup(string $username, string $groupName): array
    {
        if (!$this->isConfigured()) {
            throw new FortiGateConnectionException('FortiGate service is not configured');
        }
        
        // First, get the current group members
        $groupEndpoint = "/cmdb/user/group/{$groupName}";
        
        try {
            // Get existing group data
            $groupData = $this->request('GET', $groupEndpoint);
            $currentMembers = $groupData['results'][0]['member'] ?? [];
            
            // Add new user to members list
            $currentMembers[] = ['name' => $username];
            
            // Update the group with new member list
            $updatePayload = [
                'member' => $currentMembers
            ];
            
            return $this->request('PUT', $groupEndpoint, $updatePayload);
            
        } catch (\Exception $e) {
            // If group doesn't exist, try to create it with the user
            Log::warning("Could not get group {$groupName}, attempting to create: {$e->getMessage()}");
            
            $createPayload = [
                'name' => $groupName,
                'member' => [
                    ['name' => $username]
                ]
            ];
            
            return $this->request('POST', '/cmdb/user/group', $createPayload);
        }
    }

    /**
     * Update an existing user in FortiGate
     */
    public function updateUser(string $username, array $userData): array
    {
        if (!$this->isConfigured()) {
            throw new FortiGateConnectionException('FortiGate service is not configured');
        }
        
        $endpoint = "/cmdb/user/local/{$username}";
        
        $payload = [];
        
        if (isset($userData['password'])) {
            $payload['passwd'] = $userData['password'];
        }
        
        if (isset($userData['status'])) {
            $payload['status'] = $userData['status'];
        }
        
        if (isset($userData['email'])) {
            $payload['email-to'] = $userData['email'];
        }
        
        if (isset($userData['expires_at'])) {
            $payload['expiry-date'] = $userData['expires_at'];
        }

        $response = $this->request('PUT', $endpoint, $payload);
        
        // Handle groups separately if provided
        if (isset($userData['groups']) && !empty($userData['groups'])) {
            // Add user to specified groups
            foreach ($userData['groups'] as $group) {
                if (isset($group['name'])) {
                    try {
                        $this->addUserToGroup($username, $group['name']);
                    } catch (\Exception $e) {
                        Log::warning("Could not add user to group {$group['name']}: {$e->getMessage()}");
                    }
                }
            }
        }

        return $response;
    }

    /**
     * Remove user from group before deletion
     */
    public function removeUserFromGroup(string $username, string $groupName = null): bool
    {
        if (!$this->isConfigured()) {
            return false;
        }
        
        // Use configured group if not specified
        if (empty($groupName)) {
            $groupName = $this->settings->user_group;
        }
        
        // If no group is configured, nothing to remove
        if (empty($groupName)) {
            return true;
        }
        
        try {
            // Get current group members
            $groupEndpoint = "/cmdb/user/group/{$groupName}";
            $groupData = $this->request('GET', $groupEndpoint);
            $currentMembers = $groupData['results'][0]['member'] ?? [];
            
            // Remove the user from the members list
            $updatedMembers = array_filter($currentMembers, function($member) use ($username) {
                return $member['name'] !== $username;
            });
            
            // Update the group with the new member list
            $updatePayload = [
                'member' => array_values($updatedMembers) // Reset array keys
            ];
            
            $this->request('PUT', $groupEndpoint, $updatePayload);
            return true;
            
        } catch (Exception $e) {
            // Log the error but don't fail the deletion process
            Log::warning("Failed to remove user from group {$groupName}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Delete a user from FortiGate
     */
    public function deleteUser(string $username): bool
    {
        if (!$this->isConfigured()) {
            throw new FortiGateConnectionException('FortiGate service is not configured');
        }
        
        // First deauthenticate any active sessions for this user
        $this->deauthenticateUser($username);
        
        // Then try to remove user from group
        $this->removeUserFromGroup($username);
        
        // Finally delete the user
        $endpoint = "/cmdb/user/local/{$username}";
        
        try {
            $this->request('DELETE', $endpoint);
            return true;
        } catch (Exception $e) {
            if ($e instanceof FortiGateApiException && $e->getHttpStatusCode() === 404) {
                // User doesn't exist, consider it as successfully deleted
                return true;
            }
            throw $e;
        }
    }

    /**
     * Get user information from FortiGate
     */
    public function getUser(string $username): ?array
    {
        $endpoint = "/cmdb/user/local/{$username}";
        
        try {
            $response = $this->request('GET', $endpoint);
            return $response['results'][0] ?? null;
        } catch (FortiGateApiException $e) {
            if ($e->getHttpStatusCode() === 404) {
                return null;
            }
            throw $e;
        }
    }

    /**
     * Get all users from FortiGate
     */
    public function getAllUsers(): array
    {
        $endpoint = '/cmdb/user/local';
        
        $response = $this->request('GET', $endpoint);
        return $response['results'] ?? [];
    }

    /**
     * Get active user sessions
     * NOTE: This endpoint may not be available in all FortiGate versions
     */
    public function getActiveSessions(): array
    {
        // Return empty array for now as we get sessions per user
        return [];
    }

    /**
     * Get sessions for a specific user using the FortiGate v7.6.x endpoint
     */
    public function getUserSessions(string $username): array
    {
        if (!$this->isConfigured()) {
            return [];
        }
        
        try {
            // Use the monitor/user/firewall endpoint with username filter
            $endpoint = '/monitor/user/firewall/';
            $url = $this->apiUrl . $endpoint;
            
            // Add filter parameter for the specific username
            $client = $this->getHttpClient();
            $response = $client->get($url, [
                'filter' => 'username=@' . $username
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['results']) && is_array($data['results'])) {
                    Log::debug("Found " . count($data['results']) . " active sessions for user {$username}");
                    return $data['results'];
                }
            }
        } catch (Exception $e) {
            Log::debug("Could not get sessions for user {$username}: " . $e->getMessage());
        }
        
        return [];
    }

    /**
     * Terminate user session (legacy method)
     */
    public function terminateSession(string $username, ?string $ipAddress = null): bool
    {
        $endpoint = '/monitor/user/kick';
        
        $payload = [
            'username' => $username,
        ];
        
        if ($ipAddress) {
            $payload['ip'] = $ipAddress;
        }

        try {
            $this->request('POST', $endpoint, $payload);
            
            // Clear session cache
            if ($this->config['cache']['enabled']) {
                Cache::forget($this->getCacheKey('sessions'));
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('Failed to terminate session', [
                'username' => $username,
                'ip' => $ipAddress,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
    
    /**
     * Deauthenticate user - terminates all active firewall sessions
     * Uses the FortiGate v7.6.x specific endpoints
     */
    public function deauthenticateUser(string $username): bool
    {
        if (!$this->isConfigured()) {
            return true;
        }
        
        try {
            // First, get all active sessions for this user
            $sessions = $this->getUserSessions($username);
            
            if (empty($sessions)) {
                Log::info("No active sessions to deauthenticate for user {$username}");
                return true;
            }
            
            // Deauthenticate each session
            $deauthEndpoint = '/monitor/user/firewall/deauth';
            $deauthCount = 0;
            
            foreach ($sessions as $session) {
                try {
                    // Build the deauth payload based on session data
                    $payload = [
                        'user_type' => 'firewall',
                        'id' => $session['id'] ?? 0,
                        'ip' => $session['ipaddr'] ?? '',
                        'ip_version' => $session['src_type'] ?? 'ip4',
                        'method' => $session['method'] ?? 'Firewall'
                    ];
                    
                    // Add vdom parameter if available
                    $params = [];
                    if (isset($session['vdom'])) {
                        $params['vdom'] = $session['vdom'];
                    } else {
                        $params['vdom'] = 'root'; // Default vdom
                    }
                    
                    $url = $this->apiUrl . $deauthEndpoint . '?' . http_build_query($params);
                    $client = $this->getHttpClient();
                    $response = $client->post($url, $payload);
                    
                    if ($response->successful()) {
                        $deauthCount++;
                        Log::info("Successfully deauthenticated session for user {$username}", [
                            'ip' => $payload['ip'],
                            'session_id' => $payload['id']
                        ]);
                    } else {
                        Log::warning("Failed to deauthenticate session for user {$username}", [
                            'ip' => $payload['ip'],
                            'response' => $response->body()
                        ]);
                    }
                } catch (Exception $e) {
                    Log::warning("Error deauthenticating session for user {$username}: " . $e->getMessage());
                }
            }
            
            if ($deauthCount > 0) {
                Log::info("Deauthenticated {$deauthCount} session(s) for user {$username}");
            }
            
            // Clear session cache
            if ($this->config['cache']['enabled']) {
                Cache::forget($this->getCacheKey('sessions'));
            }
            
            return true;
        } catch (Exception $e) {
            Log::error("Failed to deauthenticate user {$username}: " . $e->getMessage());
            // Return true anyway to not block disable/delete operations
            return true;
        }
    }
    
    /**
     * Execute a CLI command via FortiGate API
     * 
     * @param string $command The CLI command to execute
     * @return string|false The command output or false on failure
     */
    public function executeCliCommand(string $command): string|false
    {
        if (!$this->isConfigured()) {
            return false;
        }
        
        // Try different CLI/SSH endpoints - some require different methods
        $attempts = [
            // Standard monitor endpoints
            ['method' => 'POST', 'endpoint' => '/monitor/system/console', 'payload' => ['command' => $command]],
            ['method' => 'POST', 'endpoint' => '/monitor/cli/execute', 'payload' => ['command' => $command]],
            ['method' => 'POST', 'endpoint' => '/monitor/web-ui/script', 'payload' => ['script' => $command]],
            
            // Try with different API versions
            ['method' => 'POST', 'endpoint' => '/api/v2/monitor/system/cli', 'payload' => ['command' => $command]],
            ['method' => 'POST', 'endpoint' => '/api/v2/cmdb/system/console', 'payload' => ['command' => $command]],
            
            // Try GET with command in query
            ['method' => 'GET', 'endpoint' => '/monitor/system/cli', 'query' => ['command' => $command]],
        ];
        
        foreach ($attempts as $attempt) {
            try {
                $url = $this->apiUrl . $attempt['endpoint'];
                $client = $this->getHttpClient();
                
                if ($attempt['method'] === 'POST') {
                    $response = $client->post($url, $attempt['payload'] ?? []);
                } else {
                    $response = $client->get($url, $attempt['query'] ?? []);
                }
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Extract output from response (format may vary)
                    $output = $data['results'] ?? $data['output'] ?? $data['response'] ?? $data['data'] ?? '';
                    
                    Log::info("CLI command executed successfully", [
                        'command' => $command,
                        'endpoint' => $attempt['endpoint'],
                        'output' => substr((string)$output, 0, 200) // Log first 200 chars
                    ]);
                    
                    return is_array($output) ? implode("\n", $output) : (string)$output;
                }
            } catch (Exception $e) {
                // Only log at debug level to avoid spam
                Log::debug("CLI endpoint {$attempt['endpoint']} failed: " . substr($e->getMessage(), 0, 100));
                continue;
            }
        }
        
        Log::debug("No CLI endpoint available for command execution");
        return false;
    }
    
    /**
     * Try to deauthenticate a specific session
     */
    protected function tryDeauthSession(string $username, array $session): void
    {
        $endpoints = [
            '/monitor/user/deauth',
            '/monitor/user/kick',
            '/monitor/firewall/deauth',
        ];
        
        foreach ($endpoints as $endpoint) {
            try {
                $payload = ['username' => $username];
                
                // Add IP if available
                if (isset($session['ip'])) {
                    $payload['ip'] = $session['ip'];
                } elseif (isset($session['src_ip'])) {
                    $payload['ip'] = $session['src_ip'];
                } elseif (isset($session['client_ip'])) {
                    $payload['ip'] = $session['client_ip'];
                }
                
                $this->request('POST', $endpoint, $payload);
                Log::info("Deauthenticated session for user {$username} using {$endpoint}", $payload);
                break;
            } catch (Exception $e) {
                Log::debug("Failed to deauth session using {$endpoint}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Check if a user exists in FortiGate
     */
    public function userExists(string $username): bool
    {
        return $this->getUser($username) !== null;
    }

    /**
     * Enable a user account
     */
    public function enableUser(string $username): bool
    {
        try {
            $this->updateUser($username, ['status' => 'enable']);
            return true;
        } catch (Exception $e) {
            Log::error("Failed to enable user {$username}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Disable a user account
     */
    public function disableUser(string $username): bool
    {
        try {
            // First deauthenticate any active sessions for this user
            $this->deauthenticateUser($username);
            
            // Then disable the account
            $this->updateUser($username, ['status' => 'disable']);
            return true;
        } catch (Exception $e) {
            Log::error("Failed to disable user {$username}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Make HTTP request to FortiGate API with retry logic
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        // Check circuit breaker
        if ($this->isCircuitOpen()) {
            throw new FortiGateConnectionException('Circuit breaker is open');
        }

        $url = $this->apiUrl . $endpoint;
        $attempts = 0;
        $lastException = null;
        $startTime = microtime(true);

        while ($attempts < $this->config['retry']['max_attempts']) {
            try {
                $response = $this->executeRequest($method, $url, $data);

                $this->recordSuccess(microtime(true) - $startTime);

                return $this->handleResponse($response, $endpoint);

            } catch (Exception $e) {
                $lastException = $e;
                $attempts++;

                // Don't retry for certain errors that won't succeed on retry
                if ($e instanceof FortiGateApiException) {
                    $statusCode = $e->getHttpStatusCode();
                    $apiResponse = $e->getApiResponse();

                    // 404 = Resource not found (expected when checking if user exists)
                    if ($statusCode === 404) {
                        throw $e;
                    }

                    // Error -5 = Duplicate entry (user already exists)
                    // Don't retry, just fail immediately
                    if (isset($apiResponse['error']) && $apiResponse['error'] == -5) {
                        throw $e;
                    }
                }

                if ($attempts < $this->config['retry']['max_attempts']) {
                    $delay = $this->calculateRetryDelay($attempts);

                    Log::warning('FortiGate API request failed, retrying', [
                        'attempt' => $attempts,
                        'delay' => $delay,
                        'endpoint' => $endpoint,
                        'error' => $e->getMessage(),
                    ]);

                    usleep($delay * 1000);
                }
            }
        }

        $this->recordFailure(microtime(true) - $startTime);

        throw $lastException ?? new FortiGateApiException('Request failed after all retries');
    }

    /**
     * Execute the actual HTTP request
     */
    protected function executeRequest(string $method, string $url, array $data = []): Response
    {
        $client = $this->getHttpClient();
        
        if ($this->config['logging']['log_requests']) {
            Log::debug('FortiGate API Request', [
                'method' => $method,
                'url' => $url,
                'data' => $method !== 'GET' ? $data : null,
            ]);
        }

        $response = match (strtoupper($method)) {
            'GET' => $client->get($url, $data),
            'POST' => $client->post($url, $data),
            'PUT' => $client->put($url, $data),
            'DELETE' => $client->delete($url),
            default => throw new FortiGateApiException("Unsupported HTTP method: {$method}"),
        };

        if ($this->config['logging']['log_responses']) {
            Log::debug('FortiGate API Response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
        }

        return $response;
    }

    /**
     * Get configured HTTP client
     */
    protected function getHttpClient(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])
        ->timeout($this->config['timeout'])
        ->withOptions([
            'verify' => $this->config['verify_ssl'],
        ])
        ->throw(function ($response, $e) {
            if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                $exception = new FortiGateConnectionException($e->getMessage());
                $exception->setIsTimeout(str_contains($e->getMessage(), 'timeout'));
                $exception->setIsNetworkError(true);
                throw $exception;
            }
        });
    }

    /**
     * Handle API response
     */
    protected function handleResponse(Response $response, string $endpoint): array
    {
        if (!$response->successful()) {
            $exception = new FortiGateApiException(
                "FortiGate API error: {$response->status()}"
            );
            
            $exception->setApiEndpoint($endpoint)
                      ->setHttpStatusCode($response->status())
                      ->setApiResponse($response->json());
            
            throw $exception;
        }

        $data = $response->json();
        
        if (isset($data['status']) && $data['status'] !== 'success') {
            throw new FortiGateApiException(
                "FortiGate API returned error: " . ($data['message'] ?? 'Unknown error')
            );
        }

        return $data;
    }

    /**
     * Calculate retry delay with exponential backoff
     */
    protected function calculateRetryDelay(int $attempt): int
    {
        $delay = $this->config['retry']['initial_delay'] * 
                 pow($this->config['retry']['multiplier'], $attempt - 1);
        
        return min($delay, $this->config['retry']['max_delay']);
    }

    /**
     * Check if circuit breaker is open
     */
    protected function isCircuitOpen(): bool
    {
        if (!$this->config['circuit_breaker']) {
            return false;
        }

        $state = Cache::get($this->circuitBreakerKey, [
            'failures' => 0,
            'last_failure' => null,
            'state' => 'closed',
        ]);

        if ($state['state'] === 'open') {
            $recoveryTime = $state['last_failure'] + $this->config['circuit_breaker']['recovery_time'];
            
            if (time() > $recoveryTime) {
                // Move to half-open state
                $state['state'] = 'half-open';
                Cache::put($this->circuitBreakerKey, $state, 3600);
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Record successful request
     */
    protected function recordSuccess(float $responseTime): void
    {
        $this->metrics['requests']++;
        $this->metrics['successes']++;
        $this->metrics['total_response_time'] += $responseTime;

        $state = Cache::get($this->circuitBreakerKey, [
            'failures' => 0,
            'successes' => 0,
            'last_failure' => null,
            'state' => 'closed',
        ]);

        if ($state['state'] === 'half-open') {
            $state['successes']++;
            
            if ($state['successes'] >= $this->config['circuit_breaker']['success_threshold']) {
                // Close the circuit
                $state = [
                    'failures' => 0,
                    'successes' => 0,
                    'last_failure' => null,
                    'state' => 'closed',
                ];
            }
            
            Cache::put($this->circuitBreakerKey, $state, 3600);
        }
    }

    /**
     * Record failed request
     */
    protected function recordFailure(float $responseTime): void
    {
        $this->metrics['requests']++;
        $this->metrics['failures']++;
        $this->metrics['total_response_time'] += $responseTime;

        $state = Cache::get($this->circuitBreakerKey, [
            'failures' => 0,
            'successes' => 0,
            'last_failure' => null,
            'state' => 'closed',
        ]);

        $state['failures']++;
        $state['last_failure'] = time();

        if ($state['failures'] >= $this->config['circuit_breaker']['failure_threshold']) {
            // Open the circuit
            $state['state'] = 'open';
            
            Log::warning('FortiGate circuit breaker opened', [
                'failures' => $state['failures'],
            ]);
        }

        Cache::put($this->circuitBreakerKey, $state, 3600);
    }

    /**
     * Get cache key
     */
    protected function getCacheKey(string $type, ...$params): string
    {
        $prefix = $this->config['cache']['prefix'];
        $key = implode(':', array_merge([$prefix, $type], $params));
        return $key;
    }

    /**
     * Get service metrics
     */
    public function getMetrics(): array
    {
        $avgResponseTime = $this->metrics['requests'] > 0 
            ? $this->metrics['total_response_time'] / $this->metrics['requests']
            : 0;

        return [
            'total_requests' => $this->metrics['requests'],
            'successful_requests' => $this->metrics['successes'],
            'failed_requests' => $this->metrics['failures'],
            'average_response_time' => round($avgResponseTime, 3),
            'success_rate' => $this->metrics['requests'] > 0 
                ? round(($this->metrics['successes'] / $this->metrics['requests']) * 100, 2)
                : 0,
        ];
    }

    /**
     * Health check for FortiGate API
     */
    public function healthCheck(): array
    {
        try {
            $startTime = microtime(true);
            $users = $this->getAllUsers();
            $responseTime = microtime(true) - $startTime;
            
            return [
                'status' => 'healthy',
                'response_time' => round($responseTime, 3),
                'api_url' => $this->apiUrl,
                'circuit_breaker' => Cache::get($this->circuitBreakerKey, ['state' => 'closed'])['state'],
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'api_url' => $this->apiUrl,
                'circuit_breaker' => Cache::get($this->circuitBreakerKey, ['state' => 'closed'])['state'],
            ];
        }
    }
}