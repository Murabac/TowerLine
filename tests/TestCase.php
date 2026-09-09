<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'overpass-api.de/*' => Http::response(['elements' => []], 200),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function signatureFields(): array
    {
        return [
            'use_saved_signature' => '0',
            'save_signature' => '1',
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwADhQGAWjR9awAAAABJRU5ErkJggg==',
        ];
    }
}
