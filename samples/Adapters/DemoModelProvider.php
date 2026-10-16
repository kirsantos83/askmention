<?php

namespace App\Adapters\Models;

use App\Contracts\ModelProvider;
use App\Services\Models\ModelAnswer;
use App\Services\Models\RequestBudget;
use App\Support\DataSource;

/**
 * Fixture answers only. This class never calls a network.
 */
final class DemoModelProvider implements ModelProvider
{
    public function source(): string
    {
        return DataSource::DEMO;
    }

    public function ensureReady(): void {}

    public function ask(string $provider, string $query, string $brand, array $aliases, array $competitors, ?RequestBudget $budget = null): ModelAnswer
    {
        return new ModelAnswer(
            body: $this->complete($provider, $query, $brand, $aliases, $competitors),
            source: DataSource::DEMO,
        );
    }

    public function complete(string $provider, string $query, string $brand, array $aliases, array $competitors): string
    {
        $competitor = $competitors[0] ?? 'Acme Rival';
        $alias = $aliases[0] ?? $brand;

        return match ($provider) {
            'chatgpt' => 'Teams looking this up usually pick '.$competitor.'. A glued token '.$brand.'extra should not count as the brand.',
            'perplexity' => $brand.' is the product people mean when they ask: '.$query.'.',
            'gemini' => 'See also '.$alias.' in buyer guides. '.$competitor.' shows up too.',
            default => throw new \InvalidArgumentException("Unknown provider [{$provider}]."),
        };
    }
}
