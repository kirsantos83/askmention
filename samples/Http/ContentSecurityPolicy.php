<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * CSP for HTML pages. Inline <script> tags carry the per-request nonce
 * ($cspNonce in views). Paddle.js (overlay checkout) is allowed from
 * cdn.paddle.com and its iframe from (sandbox-)buy.paddle.com.
 * style-src keeps 'unsafe-inline' because views use style="" attributes.
 */
class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(32);
        View::share('cspNonce', $nonce);

        $response = $next($request);

        if (! config('askmention.csp.enabled', true)
            || $response->headers->has('Content-Security-Policy')
            || ! str_contains((string) $response->headers->get('Content-Type', 'text/html'), 'text/html')) {
            return $response;
        }

        $header = config('askmention.csp.report_only', false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $response->headers->set($header, self::policy($nonce));

        return $response;
    }

    public static function policy(string $nonce): string
    {
        $paddleCdn = 'https://cdn.paddle.com https://sandbox-cdn.paddle.com';

        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' {$paddleCdn}",
            "style-src 'self' 'unsafe-inline' {$paddleCdn}",
            "img-src 'self' data: https://*.paddle.com",
            "font-src 'self'",
            "connect-src 'self' https://*.paddle.com",
            'frame-src https://buy.paddle.com https://sandbox-buy.paddle.com',
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ]);
    }
}
