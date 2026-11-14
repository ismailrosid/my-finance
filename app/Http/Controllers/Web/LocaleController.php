<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch application locale.
     */
    public function switch(Request $request, ?string $locale = null): RedirectResponse|JsonResponse
    {
        $targetLocale = $locale ?? $request->input('locale');
        $targetLocale = strtolower((string) $targetLocale);

        if (!in_array($targetLocale, SetLocale::SUPPORTED_LOCALES, true)) {
            $targetLocale = 'en';
        }

        // 1. Update authenticated user preference
        if ($request->user()) {
            $request->user()->update(['locale' => $targetLocale]);
        }

        // 2. Set session
        session(['locale' => $targetLocale]);
        app()->setLocale($targetLocale);

        $cookie = cookie()->forever('locale', $targetLocale);

        if ($request->expectsJson() || $request->isJson()) {
            return response()->json([
                'success' => true,
                'locale'  => $targetLocale,
                'message' => __('settings.locale_updated_success'),
            ])->withCookie($cookie);
        }

        return redirect()->back()
            ->withCookie($cookie)
            ->with('success', __('settings.locale_updated_success'));
    }
}
