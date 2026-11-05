<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ApiDocsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'api.docs.username' => 'docs-user',
            'api.docs.password' => 'docs-secret',
        ]);
    }

    public function test_docs_require_basic_auth(): void
    {
        foreach (['/docs/api', '/docs/api.json'] as $url) {
            $this->get($url)
                ->assertUnauthorized()
                ->assertHeader('WWW-Authenticate', 'Basic realm="Internal tools", charset="UTF-8"');
        }
    }

    public function test_wrong_credentials_are_rejected(): void
    {
        $this->withBasicAuth('docs-user', 'wrong')->get('/docs/api.json')->assertUnauthorized();
        $this->withBasicAuth('someone', 'docs-secret')->get('/docs/api.json')->assertUnauthorized();
    }

    public function test_docs_are_closed_when_no_credentials_are_configured(): void
    {
        config(['api.docs.username' => null, 'api.docs.password' => null]);

        $this->withBasicAuth('', '')->get('/docs/api.json')->assertUnauthorized();
    }

    public function test_ui_is_served_with_valid_credentials(): void
    {
        $this->withBasicAuth('docs-user', 'docs-secret')
            ->get('/docs/api')
            ->assertOk()
            ->assertSee('Inventory API');
    }

    public function test_openapi_document_describes_the_v1_api(): void
    {
        $spec = $this->withBasicAuth('docs-user', 'docs-secret')
            ->getJson('/docs/api.json')
            ->assertOk()
            ->json();

        $this->assertStringStartsWith('3.1', $spec['openapi']);
        $this->assertSame('Inventory API', $spec['info']['title']);
        $this->assertStringEndsWith('/api/v1', $spec['servers'][0]['url']);

        foreach (['/assets', '/assets/{asset}', '/assets/count', '/contracts', '/contracts/{contract}/assets', '/manufacturers', '/vendors'] as $path) {
            $this->assertArrayHasKey($path, $spec['paths'], "missing path {$path}");
        }
    }

    public function test_openapi_document_uses_the_api_conventions(): void
    {
        $spec = $this->withBasicAuth('docs-user', 'docs-secret')->getJson('/docs/api.json')->json();
        $responses = $spec['components']['responses'];

        // Errors: the standard envelope, not Laravel's default {"message", "errors"}.
        $validation = $responses['ValidationError']['content']['application/json']['schema'];
        $this->assertSame(['error'], $validation['required']);
        $this->assertArrayHasKey('errors', $validation['properties']['error']['properties']);

        // Domain errors with their own status and code.
        $inUse = $responses['AssetInUseException']['content']['application/json']['schema']['properties']['error']['properties'];
        $this->assertSame([409], $inUse['status']['examples']);
        $this->assertSame(['asset_in_use'], $inUse['code']['examples']);
        $this->assertArrayHasKey('409', $spec['paths']['/assets/{asset}']['delete']['responses']);

        // Rate limit on API routes.
        $this->assertArrayHasKey('Retry-After', $responses['TooManyRequests']['headers']);
        $this->assertArrayHasKey('429', $spec['paths']['/assets']['get']['responses']);

        // Cursor pagination meta on lists only.
        $list = $spec['paths']['/assets']['get']['responses']['200']['content']['application/json']['schema'];
        $this->assertSame(['per_page', 'next_cursor', 'prev_cursor'], array_keys($list['properties']['meta']['properties']));
        $this->assertArrayNotHasKey(
            'meta',
            $spec['paths']['/assets/count']['get']['responses']['200']['content']['application/json']['schema']['properties'] ?? [],
        );
    }
}
