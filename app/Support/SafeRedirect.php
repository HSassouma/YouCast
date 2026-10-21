<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SafeRedirect
{
    public static function intended(Request $request, string $fallback): RedirectResponse
    {
        $target = $request->session()->pull('url.intended');

        if (! is_string($target) || $target === '' || str_contains($target, "\\") || preg_match('/[\x00-\x1F\x7F]/', $target) === 1) {
            return redirect($fallback);
        }

        // A single-leading-slash path is always relative to this site.
        if (str_starts_with($target, '/') && ! str_starts_with($target, '//')) {
            return redirect($target);
        }

        $targetParts = parse_url($target);
        $appParts = parse_url((string) config('app.url'));

        if (! is_array($targetParts) || ! is_array($appParts)
            || empty($targetParts['host']) || empty($appParts['host'])) {
            return redirect($fallback);
        }

        $targetScheme = strtolower((string) ($targetParts['scheme'] ?? ''));
        $appScheme = strtolower((string) ($appParts['scheme'] ?? ''));
        $targetPort = $targetParts['port'] ?? ($targetScheme === 'https' ? 443 : 80);
        $appPort = $appParts['port'] ?? ($appScheme === 'https' ? 443 : 80);

        if ($targetScheme !== $appScheme
            || strcasecmp($targetParts['host'], $appParts['host']) !== 0
            || $targetPort !== $appPort
            || isset($targetParts['user'])
            || isset($targetParts['pass'])) {
            return redirect($fallback);
        }

        return redirect()->to($target);
    }
}
