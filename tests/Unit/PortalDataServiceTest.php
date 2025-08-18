<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\PortalDataService;
use Illuminate\Support\Facades\Session;

class PortalDataServiceTest extends TestCase
{
    protected PortalDataService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PortalDataService();
    }

    public function test_decode_valid_portal_data()
    {
        // Sample portal data that would come from FortiGate
        $portalData = [
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

        $encoded = base64_encode(json_encode($portalData));
        
        $decoded = $this->service->decodePortalData($encoded);
        
        $this->assertNotNull($decoded);
        $this->assertEquals($portalData['portal_url'], $decoded['portal_url']);
        $this->assertEquals($portalData['auth_url'], $decoded['auth_url']);
        $this->assertEquals($portalData['ssid'], $decoded['ssid']);
    }

    public function test_decode_invalid_base64()
    {
        $decoded = $this->service->decodePortalData('not-valid-base64!!!');
        $this->assertNull($decoded);
    }

    public function test_decode_invalid_json()
    {
        $encoded = base64_encode('not valid json');
        $decoded = $this->service->decodePortalData($encoded);
        $this->assertNull($decoded);
    }

    public function test_generate_auth_url()
    {
        $portalData = [
            'auth_url' => 'https://192.168.1.1:1003/authenticate',
            'magic' => 'abc123',
            'form_fields' => [
                'username_field' => 'user',
                'password_field' => 'pass',
                'magic_field' => 'magic'
            ]
        ];

        $url = $this->service->generateAuthUrl($portalData, 'testuser', 'testpass');
        
        $this->assertStringContainsString('https://192.168.1.1:1003/authenticate', $url);
        $this->assertStringContainsString('user=testuser', $url);
        $this->assertStringContainsString('pass=testpass', $url);
        $this->assertStringContainsString('magic=abc123', $url);
    }

    public function test_session_storage()
    {
        $portalData = [
            'portal_url' => 'https://192.168.1.1:1003/portal',
            'auth_url' => 'https://192.168.1.1:1003/authenticate',
            'ssid' => 'TestNetwork'
        ];

        $this->service->storeInSession($portalData);
        
        $retrieved = $this->service->getFromSession();
        
        $this->assertNotNull($retrieved);
        $this->assertEquals($portalData['ssid'], $retrieved['ssid']);
        
        $this->service->clearFromSession();
        
        $cleared = $this->service->getFromSession();
        $this->assertNull($cleared);
    }

    public function test_portal_info_extraction()
    {
        $portalData = [
            'ssid' => 'CompanyWiFi',
            'client_ip' => '192.168.1.100',
            'ap_mac' => 'AA:BB:CC:DD:EE:FF',
            'auth_url' => 'https://192.168.1.1:1003/authenticate'
        ];

        $info = $this->service->getPortalInfo($portalData);
        
        $this->assertEquals('wireless', $info['network_type']);
        $this->assertEquals('CompanyWiFi', $info['network_name']);
        $this->assertEquals('CompanyWiFi', $info['ssid']);
        $this->assertEquals('192.168.1.100', $info['client_ip']);
        $this->assertEquals('AA:BB:CC:DD:EE:FF', $info['ap_mac']);
        $this->assertTrue($info['has_auto_auth']);
    }
    
    public function test_portal_info_extraction_wired_network()
    {
        $portalData = [
            'client_ip' => '192.168.1.100',
            'auth_url' => 'https://192.168.1.1:1003/authenticate'
        ];

        $info = $this->service->getPortalInfo($portalData);
        
        $this->assertEquals('wired', $info['network_type']);
        $this->assertEquals(__('guest.wired_network'), $info['network_name']); // Will be translated
        $this->assertNull($info['ssid']);
        $this->assertEquals('192.168.1.100', $info['client_ip']);
        $this->assertTrue($info['has_auto_auth']);
    }

    public function test_has_auto_auth()
    {
        $withAuth = ['auth_url' => 'https://192.168.1.1:1003/authenticate'];
        $withoutAuth = ['portal_url' => 'https://192.168.1.1:1003/portal'];
        
        $this->assertTrue($this->service->hasAutoAuth($withAuth));
        $this->assertFalse($this->service->hasAutoAuth($withoutAuth));
        $this->assertFalse($this->service->hasAutoAuth(null));
    }
}