<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PortalDataService;

class FortiGatePortalDataTest extends TestCase
{
    protected PortalDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PortalDataService();
    }

    public function test_decode_real_fortigate_portal_data()
    {
        // Real FortiGate portal data format from the logs
        $portalData = [
            'portal_url' => 'http://192.168.20.1:1000/fgtauth?050d0496b5579426',
            'auth_post_url' => '/',
            'redir_id' => '4Tredir',
            'redir_value' => 'http://google.fr/',
            'magic_id' => 'magic',
            'magic_value' => '050d0496b5579426',
            'method_id' => '',
            'method_value' => '',
            'username_id' => 'username',
            'password_id' => 'password'
        ];

        $encoded = base64_encode(json_encode($portalData));
        
        $decoded = $this->service->decodePortalData($encoded);
        
        $this->assertNotNull($decoded);
        $this->assertEquals($portalData['portal_url'], $decoded['portal_url']);
        // The auth_url should now be the same as portal_url for FortiGate
        $this->assertEquals('http://192.168.20.1:1000/fgtauth?050d0496b5579426', $decoded['auth_url']);
        $this->assertEquals('050d0496b5579426', $decoded['magic']);
        $this->assertEquals('http://google.fr/', $decoded['redirect_url']);
        
        // Check form fields are properly mapped
        $this->assertArrayHasKey('form_fields', $decoded);
        $this->assertEquals('username', $decoded['form_fields']['username_field']);
        $this->assertEquals('password', $decoded['form_fields']['password_field']);
        $this->assertEquals('magic', $decoded['form_fields']['magic_field']);
        $this->assertEquals('4Tredir', $decoded['form_fields']['redirect_field']);
    }

    public function test_generate_auth_url_with_fortigate_format()
    {
        $portalData = [
            'auth_url' => 'http://192.168.20.1:1000/fgtauth?050d0496b5579426',
            'magic' => '050d0496b5579426',
            'redirect_url' => 'http://google.fr/',
            'form_fields' => [
                'username_field' => 'username',
                'password_field' => 'password',
                'magic_field' => 'magic',
                'redirect_field' => '4Tredir'
            ]
        ];

        $url = $this->service->generateAuthUrl($portalData, 'guest-10', 'TestPass123!');
        
        $this->assertStringContainsString('http://192.168.20.1:1000/fgtauth?050d0496b5579426', $url);
        $this->assertStringContainsString('username=guest-10', $url);
        $this->assertStringContainsString('password=TestPass123', $url);
        $this->assertStringContainsString('4Tredir=' . urlencode('http://google.fr/'), $url);
    }

    public function test_portal_info_extraction_with_minimal_data()
    {
        $portalData = [
            'portal_url' => 'http://192.168.20.1:1000/fgtauth',
            'auth_url' => 'http://192.168.20.1:1000/',
        ];

        $info = $this->service->getPortalInfo($portalData);
        
        $this->assertEquals('wired', $info['network_type']);
        $this->assertEquals(__('guest.wired_network'), $info['network_name']); // Will be translated
        $this->assertNull($info['ssid']);
        $this->assertEquals('N/A', $info['client_ip']);
        $this->assertTrue($info['has_auto_auth']);
    }
}