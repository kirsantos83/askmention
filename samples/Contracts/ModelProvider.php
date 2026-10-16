<?php

namespace App\Contracts;

use App\Services\Models\ModelAnswer;
use App\Services\Models\RequestBudget;

interface ModelProvider
{
    /** Provenance written on every run and answer: "demo" or "api". */
    public function source(): string;

    /**
     * Throw \App\Services\Models\ModelProviderException when this provider
     * cannot run at all (e.g. live mode without a key).
     */
    public function ensureReady(): void;

    /**
     * Ask one engine (chatgpt, perplexity, gemini) one query.
     *
     * Throws ModelProviderException to stop the whole run, or
     * EngineFailedException when only this engine/query failed.
     *
     * @param  list<string>  $aliases
     * @param  list<string>  $competitors
     */
    public function ask(string $provider, string $query, string $brand, array $aliases, array $competitors, ?RequestBudget $budget = null): ModelAnswer;

    /**
     * Answer text only (kept for older callers).
     *
     * @param  list<string>  $aliases
     * @param  list<string>  $competitors
     */
    public function complete(string $provider, string $query, string $brand, array $aliases, array $competitors): string;
}
