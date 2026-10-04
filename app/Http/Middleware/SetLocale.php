<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('app_locale');

        if (!$locale) {
            $locale = $request->cookie('app_locale');
        }

        if (!$locale) {
            $locale = config('app.locale');
        }

        if (in_array($locale, ['en', 'ha'])) {
            App::setLocale($locale);
            session(['app_locale' => $locale]);
        }

        return $next($request);
    }
}
