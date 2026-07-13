<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetApiLocale
{
    /**
     * Resolve the response language for every API request.
     *
     * Resolution order (first match wins):
     *   1. Explicit `language` header / `language` param (apiRequestLanguage)
     *   2. Authenticated user's stored `current_lang` — persisted choice
     *   3. `Accept-Language` header — ambient device default (implicit)
     *   4. Application default locale
     *
     * The explicit header/param lets a client localize a response without ever
     * touching the DB. `current_lang` still matters for background jobs
     * (scheduled notifications, emails) that run with no request to read, and
     * ranks above Accept-Language because it is an explicit user choice while
     * Accept-Language is sent automatically by every client.
     *
     * NOTE: this middleware is registered on the `api` group, which runs
     * BEFORE the per-route `auth:sanctum` middleware. The default guard is
     * therefore still unauthenticated here, so we resolve the bearer token
     * explicitly through the `sanctum` guard.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user('sanctum');

        $locale = apiRequestLanguage()
            ?? $user->current_lang
            ?? acceptLanguageLocale()
            ?? config('app.locale');

        // Guard against a code with no matching lang folder / inactive language.
        App::setLocale(resolveSupportedLocale($locale));

        return $next($request);
    }
}
