<?php
namespace Tests\Feature;
use Tests\TestCase;
class HealthCheckTest extends TestCase {
 public function test_application_health_endpoint_returns_success(): void { $this->get('/up')->assertOk(); }
 public function test_api_v1_health_endpoint_returns_json(): void { $this->getJson('/api/v1/health')->assertOk()->assertJsonPath('status','ok')->assertJsonPath('service','examtesthub-api'); }
}
