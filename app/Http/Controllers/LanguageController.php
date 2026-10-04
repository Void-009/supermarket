<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

class LanguageController extends Controller
{
    public function switch($locale, Request $request)
    {
        if (in_array($locale, ['en', 'ha'])) {
            session(['app_locale' => $locale]);
            App::setLocale($locale);
            Cookie::queue('app_locale', $locale, 60 * 24 * 365);
        }

        $back = $request->headers->get('referer');

        if ($back && parse_url($back, PHP_URL_HOST) === $request->getHost()) {
            return redirect($back);
        }

        return redirect()->route('home');
    }
}
