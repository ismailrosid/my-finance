<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported application locales.
     */
    public const SUPPORTED_LOCALES = ['en', 'id'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        App::setLocale($locale);
        Carbon::setLocale($locale === 'id' ? 'id_ID' : 'en_US');

        return $next($request);
    }

    /**
     * Determine the appropriate locale for the request.
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Authenticated user saved preference
        if ($request->user() && !empty($request->user()->locale)) {
            $userLocale = strtolower($request->user()->locale);
            if (in_array($userLocale, self::SUPPORTED_LOCALES, true)) {
                return $userLocale;
            }
        }

        // 2. Session preference (especially for guests or immediate toggle)
        if ($request->hasSession() && $request->session()->has('locale')) {
            $sessionLocale = strtolower((string) $request->session()->get('locale'));
            if (in_array($sessionLocale, self::SUPPORTED_LOCALES, true)) {
                return $sessionLocale;
            }
        }

        // 3. Cookie preference
        $cookieLocale = strtolower((string) $request->cookie('locale'));
        if (in_array($cookieLocale, self::SUPPORTED_LOCALES, true)) {
            return $cookieLocale;
        }

        // 4. Default configuration fallback
        $defaultLocale = strtolower((string) config('app.locale', 'en'));

        return in_array($defaultLocale, self::SUPPORTED_LOCALES, true) ? $defaultLocale : 'en';
    }
}
