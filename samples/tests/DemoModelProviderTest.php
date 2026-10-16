<?php

namespace Tests\Unit;

use App\Adapters\Models\DemoModelProvider;
use App\Http\Middleware\ContentSecurityPolicy;
use App\Support\DataSource;
use PHPUnit\Framework\TestCase;

/**
 * Showcase sample: the fixture provider is deterministic and offline,
 * so the whole run pipeline can be tested without any network calls.
 */
class DemoModelProviderTest extends TestCase
{
    public function test_answers_are_tagged_as_demo_data(): void
    {
        $answer = (new DemoModelProvider)->ask('perplexity', 'best warehouse software', 'Northwind', [], ['Contoso']);

        $this->assertSame(DataSource::DEMO, $answer->source);
        $this->assertStringContainsString('Northwind', $answer->body);
    }

    public function test_each_engine_returns_a_different_fixture(): void
    {
        $provider = new DemoModelProvider;

        $chatgpt = $provider->complete('chatgpt', 'q', 'Northwind', [], ['Contoso']);
        $gemini = $provider->complete('gemini', 'q', 'Northwind', ['Northwind HQ'], ['Contoso']);

        $this->assertStringContainsString('Contoso', $chatgpt);
        $this->assertStringContainsString('Northwind HQ', $gemini);
        $this->assertNotSame($chatgpt, $gemini);
    }

    public function test_unknown_engine_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new DemoModelProvider)->complete('unknown', 'q', 'Northwind', [], []);
    }

    public function test_csp_policy_carries_the_request_nonce(): void
    {
        $policy = ContentSecurityPolicy::policy('abc123');

        $this->assertStringContainsString("'nonce-abc123'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
    }
}
