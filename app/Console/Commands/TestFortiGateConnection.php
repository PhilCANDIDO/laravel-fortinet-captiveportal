<?php

namespace App\Console\Commands;

use App\Services\FortiGateService;
use App\Models\FortiGateSettings;
use Illuminate\Console\Command;

class TestFortiGateConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fortigate:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test FortiGate API connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing FortiGate connection...');
        
        try {
            // Get current settings
            $settings = FortiGateSettings::current();
            
            $this->table(['Setting', 'Value'], [
                ['API URL', $settings->api_url],
                ['User Group', $settings->user_group],
                ['SSL Verification', $settings->verify_ssl ? 'Yes' : 'No'],
                ['Service Active', $settings->is_active ? 'Yes' : 'No'],
                ['Last Test', $settings->last_connection_test ? $settings->last_connection_test->format('Y-m-d H:i:s') : 'Never'],
            ]);
            
            if (!$settings->is_active) {
                $this->warn('FortiGate service is currently disabled. Enable it in the admin panel.');
                return 1;
            }
            
            $this->info('Attempting to connect to FortiGate API...');
            
            // Test connection
            $service = new FortiGateService();
            $health = $service->healthCheck();
            
            if ($health['status'] === 'healthy') {
                $this->info('✓ Connection successful!');
                $this->table(['Metric', 'Value'], [
                    ['Status', $health['status']],
                    ['Response Time', $health['response_time'] . ' seconds'],
                    ['API URL', $health['api_url']],
                    ['Circuit Breaker', $health['circuit_breaker']],
                ]);
                
                // Try to get users list
                $this->info('Attempting to retrieve users list...');
                try {
                    $users = $service->getAllUsers();
                    $this->info('✓ Successfully retrieved ' . count($users) . ' users from FortiGate');
                    
                    if (count($users) > 0 && $this->confirm('Would you like to see the first 5 users?')) {
                        $displayUsers = array_slice($users, 0, 5);
                        foreach ($displayUsers as $user) {
                            $this->line('- ' . ($user['name'] ?? 'Unknown'));
                        }
                    }
                } catch (\Exception $e) {
                    $this->warn('Could not retrieve users: ' . $e->getMessage());
                }
                
                // Display metrics
                $metrics = $service->getMetrics();
                $this->info('Service Metrics:');
                $this->table(['Metric', 'Value'], [
                    ['Total Requests', $metrics['total_requests']],
                    ['Successful Requests', $metrics['successful_requests']],
                    ['Failed Requests', $metrics['failed_requests']],
                    ['Average Response Time', $metrics['average_response_time'] . ' seconds'],
                    ['Success Rate', $metrics['success_rate'] . '%'],
                ]);
                
                return 0;
            } else {
                $this->error('✗ Connection failed!');
                $this->error('Error: ' . ($health['error'] ?? 'Unknown error'));
                
                // Update settings with error
                $settings->update([
                    'last_connection_test' => now(),
                    'last_connection_status' => false,
                    'last_connection_error' => $health['error'] ?? 'Unknown error',
                ]);
                
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error('✗ Connection test failed!');
            $this->error('Error: ' . $e->getMessage());
            
            if ($this->option('verbose')) {
                $this->error('Stack trace:');
                $this->line($e->getTraceAsString());
            }
            
            return 1;
        }
    }
}