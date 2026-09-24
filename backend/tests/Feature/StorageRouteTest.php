<?php

namespace Tests\Feature;

use Tests\TestCase;

class StorageRouteTest extends TestCase
{
    public function test_storage_asset_is_served(): void
    {
        $response = $this->get('/storage/deposits/5iS7wxB6kqnKpqlKBsM1c5QB3cx1s0VJcoawJDrz.jpg');
        $response->assertStatus(200);
        $this->assertStringContainsString('image/', (string) $response->headers->get('Content-Type'));
    }
}
