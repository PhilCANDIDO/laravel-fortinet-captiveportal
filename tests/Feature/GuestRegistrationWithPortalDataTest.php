<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class GuestRegistrationWithPortalDataTest extends TestCase
{
    use RefreshDatabase;

    protected function getValidPortalData(): array
    {
        return [
            'portal_url' => 'https://192.168.1.1:1003/portal',
            'auth_url' => 'https://192.168.1.1:1003/authenticate',
            'magic' => 'abc123xyz',
            'client_mac' => '00:11:22:33:44:55',
            'client_ip' => '192.168.1.100',
            'ap_mac' => 'AA:BB:CC:DD:EE:FF',
            'ssid' => 'CompanyWiFi',
            'redirect_url' => 'https://www.company.com',
            'form_fields' => [
                'username_field' => 'user',
                'password_field' => 'pass',
                'magic_field' => 'magic_token',
                'redirect_field' => 'redir'
            ]
        ];
    }

    public function test_registration_form_accepts_portal_data_parameter()
    {
        $portalData = $this->getValidPortalData();
        $encodedData = base64_encode(json_encode($portalData));
        
        $response = $this->get(route('guest.register', ['portal_data' => $encodedData]));
        
        $response->assertStatus(200);
        $response->assertViewHas('portalInfo');
        $response->assertSee('CompanyWiFi');
    }

    public function test_registration_form_handles_invalid_portal_data()
    {
        $response = $this->get(route('guest.register', ['portal_data' => 'invalid-data']));
        
        $response->assertStatus(200);
        $response->assertViewHas('portalInfo', null);
    }

    public function test_registration_form_works_without_portal_data()
    {
        $response = $this->get(route('guest.register'));
        
        $response->assertStatus(200);
        $response->assertViewHas('portalInfo', null);
    }

    public function test_guest_registration_with_portal_data()
    {
        $portalData = $this->getValidPortalData();
        $encodedData = base64_encode(json_encode($portalData));
        
        // First visit the form to store portal data in session
        $this->get(route('guest.register', ['portal_data' => $encodedData]));
        
        // Submit registration
        $registrationData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'company_name' => 'Test Company',
            'visit_reason' => 'Business meeting',
        ];
        
        $response = $this->post(route('guest.register'), $registrationData);
        
        $response->assertRedirect(route('guest.register.success'));
        $response->assertSessionHas('has_portal_data', true);
        
        // Verify user was created with portal data
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->portal_data);
        
        $storedPortalData = json_decode($user->portal_data, true);
        $this->assertEquals('CompanyWiFi', $storedPortalData['ssid']);
    }

    public function test_guest_registration_without_portal_data()
    {
        $registrationData = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '+9876543210',
            'company_name' => 'Another Company',
            'visit_reason' => 'Conference',
        ];
        
        $response = $this->post(route('guest.register'), $registrationData);
        
        $response->assertRedirect(route('guest.register.success'));
        $response->assertSessionHas('has_portal_data', false);
        
        // Verify user was created without portal data
        $user = User::where('email', 'jane.smith@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->portal_data);
    }

    public function test_success_page_with_auto_authentication()
    {
        $portalData = $this->getValidPortalData();
        
        // Simulate session data from registration
        session([
            'email' => 'john.doe@example.com',
            'password' => 'SecurePass123!',
            'username' => 'guest_john_abc123',
            'has_portal_data' => true,
        ]);
        
        // Store portal data in session
        $portalDataService = app(\App\Services\PortalDataService::class);
        $portalDataService->storeInSession($portalData);
        
        $response = $this->get(route('guest.register.success'));
        
        $response->assertStatus(200);
        $response->assertViewHas('autoAuthUrl');
        $response->assertViewHas('portalInfo');
        
        // Check that auto-auth URL is generated correctly
        $viewData = $response->viewData('autoAuthUrl');
        $this->assertStringContainsString('https://192.168.1.1:1003/authenticate', $viewData);
        $this->assertStringContainsString('user=guest_john_abc123', $viewData);
        $this->assertStringContainsString('pass=SecurePass123', urldecode($viewData));
    }

    public function test_success_page_without_auto_authentication()
    {
        // Simulate session data from registration without portal data
        session([
            'email' => 'jane.smith@example.com',
            'password' => 'AnotherPass456!',
            'username' => 'guest_jane_xyz789',
            'has_portal_data' => false,
        ]);
        
        $response = $this->get(route('guest.register.success'));
        
        $response->assertStatus(200);
        $response->assertViewHas('autoAuthUrl', null);
        $response->assertViewHas('portalInfo', null);
    }

    public function test_portal_data_sanitization()
    {
        $portalData = [
            'portal_url' => 'https://192.168.1.1:1003/portal',
            'auth_url' => 'https://192.168.1.1:1003/authenticate',
            'ssid' => 'Company<script>alert("xss")</script>WiFi',
            'client_ip' => '192.168.1.100',
        ];
        $encodedData = base64_encode(json_encode($portalData));
        
        $this->get(route('guest.register', ['portal_data' => $encodedData]));
        
        $registrationData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test.user@example.com',
            'phone' => '+1234567890',
            'company_name' => 'Test Corp',
            'visit_reason' => 'Testing',
        ];
        
        $this->post(route('guest.register'), $registrationData);
        
        $user = User::where('email', 'test.user@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->portal_data);
        
        $storedPortalData = json_decode($user->portal_data, true);
        
        // Verify XSS is sanitized
        $this->assertStringNotContainsString('<script>', $storedPortalData['ssid']);
        $this->assertStringContainsString('&lt;script&gt;', $storedPortalData['ssid']);
    }
}