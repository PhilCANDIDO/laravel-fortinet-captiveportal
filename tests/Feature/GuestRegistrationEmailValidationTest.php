<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Queue;

class GuestRegistrationEmailValidationTest extends TestCase
{
    use RefreshDatabase;
    
    protected $mockQueue = true;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Fake the queue to prevent jobs from running
        Queue::fake();
        
        // Ensure FortiGate settings exist
        \App\Models\FortiGateSettings::create([
            'api_url' => 'https://192.168.1.1',
            'api_token' => 'test-token',
            'user_group' => 'guest-users',
            'captive_portal_url' => 'https://192.168.1.1:1003/portal',
        ]);
    }

    protected function getValidPortalData(): array
    {
        return [
            'portal_url' => 'https://192.168.1.1:1003/portal',
            'auth_url' => 'https://192.168.1.1:1003/authenticate',
            'magic' => 'abc123xyz',
            'client_ip' => '192.168.1.100',
            'ssid' => 'CompanyWiFi',
        ];
    }

    public function test_guest_registration_with_email_validation_enabled()
    {
        // Enable email validation
        Setting::updateOrCreate(
            ['key' => 'guest_email_validation_enabled'],
            ['value' => '1', 'type' => 'boolean', 'group' => 'security']
        );

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
        $response->assertSessionHas('email_validation_enabled', true);
        $response->assertSessionHas('user_active', false);

        // Verify user was created with pending status
        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(User::STATUS_PENDING, $user->status);
        $this->assertFalse($user->is_active);
        $this->assertNotNull($user->validation_token);
        $this->assertNotNull($user->validation_expires_at);
        $this->assertNull($user->validated_at);
        
        // Verify FortiGate username follows the pattern guest-{{id}}
        $this->assertEquals('guest-' . $user->id, $user->fortigate_username);
    }

    public function test_guest_registration_with_email_validation_disabled()
    {
        // Disable email validation
        Setting::updateOrCreate(
            ['key' => 'guest_email_validation_enabled'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'security']
        );

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
        $response->assertSessionHas('email_validation_enabled', false);
        $response->assertSessionHas('user_active', true);

        // Verify user was created with active status
        $user = User::where('email', 'jane.smith@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals(User::STATUS_ACTIVE, $user->status);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->validation_token);
        $this->assertNull($user->validation_expires_at);
        $this->assertNotNull($user->validated_at);
        
        // Verify FortiGate username follows the pattern guest-{{id}}
        $this->assertEquals('guest-' . $user->id, $user->fortigate_username);
    }

    public function test_auto_authentication_with_portal_data_and_email_validation_disabled()
    {
        // Disable email validation
        Setting::updateOrCreate(
            ['key' => 'guest_email_validation_enabled'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'security']
        );

        $portalData = $this->getValidPortalData();
        $encodedData = base64_encode(json_encode($portalData));

        // First visit the form to store portal data in session
        $this->get(route('guest.register', ['portal_data' => $encodedData]));

        // Submit registration
        $registrationData = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test.user@example.com',
            'phone' => '+1234567890',
            'company_name' => 'Test Corp',
            'visit_reason' => 'Testing',
        ];

        $response = $this->post(route('guest.register'), $registrationData);

        $response->assertRedirect(route('guest.register.success'));

        // Get the user to verify the username
        $user = User::where('email', 'test.user@example.com')->first();
        $this->assertNotNull($user);
        $expectedUsername = 'guest-' . $user->id;
        $this->assertEquals($expectedUsername, $user->fortigate_username);

        // Simulate session data from registration
        session([
            'email' => 'test.user@example.com',
            'password' => 'SecurePass123!',
            'username' => $expectedUsername,
            'has_portal_data' => true,
            'email_validation_enabled' => false,
            'user_active' => true,
        ]);

        // Store portal data in session
        $portalDataService = app(\App\Services\PortalDataService::class);
        $portalDataService->storeInSession($portalData);

        $response = $this->get(route('guest.register.success'));

        $response->assertStatus(200);
        $response->assertViewHas('autoAuthUrl');
        $response->assertViewHas('emailValidationEnabled', false);
        $response->assertViewHas('userActive', true);

        // Check that auto-auth URL is generated correctly with the new username pattern
        $viewData = $response->viewData('autoAuthUrl');
        $this->assertStringContainsString('https://192.168.1.1:1003/authenticate', $viewData);
        // The default field name is 'username' not 'user' when form_fields is not specified
        $this->assertStringContainsString('username=' . $expectedUsername, $viewData);
    }

    public function test_no_auto_authentication_with_portal_data_and_email_validation_enabled()
    {
        // Enable email validation
        Setting::updateOrCreate(
            ['key' => 'guest_email_validation_enabled'],
            ['value' => '1', 'type' => 'boolean', 'group' => 'security']
        );

        $portalData = $this->getValidPortalData();

        // Simulate session data from registration
        session([
            'email' => 'pending.user@example.com',
            'password' => 'SecurePass123!',
            'username' => 'guest-999',
            'has_portal_data' => true,
            'email_validation_enabled' => true,
            'user_active' => false,
        ]);

        // Store portal data in session
        $portalDataService = app(\App\Services\PortalDataService::class);
        $portalDataService->storeInSession($portalData);

        $response = $this->get(route('guest.register.success'));

        $response->assertStatus(200);
        $response->assertViewHas('autoAuthUrl', null); // No auto-auth because validation is required
        $response->assertViewHas('emailValidationEnabled', true);
        $response->assertViewHas('userActive', false);
    }

    public function test_username_pattern_is_sequential()
    {
        // Disable email validation for simpler testing
        Setting::updateOrCreate(
            ['key' => 'guest_email_validation_enabled'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'security']
        );

        // Create first user
        $response1 = $this->post(route('guest.register'), [
            'first_name' => 'User',
            'last_name' => 'One',
            'email' => 'user.one@example.com',
            'company_name' => 'Company A',
        ]);

        $user1 = User::where('email', 'user.one@example.com')->first();
        $this->assertEquals('guest-' . $user1->id, $user1->fortigate_username);

        // Create second user
        $response2 = $this->post(route('guest.register'), [
            'first_name' => 'User',
            'last_name' => 'Two',
            'email' => 'user.two@example.com',
            'company_name' => 'Company B',
        ]);

        $user2 = User::where('email', 'user.two@example.com')->first();
        $this->assertEquals('guest-' . $user2->id, $user2->fortigate_username);

        // Verify the IDs are sequential
        $this->assertEquals($user1->id + 1, $user2->id);
        $this->assertNotEquals($user1->fortigate_username, $user2->fortigate_username);
    }
}