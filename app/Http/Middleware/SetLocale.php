<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->query('lang');

        if (!$locale && $request->hasHeader('Accept-Language')) {
            $locale = substr($request->header('Accept-Language'), 0, 2);
        }

        if (!$locale) {
            $locale = config('app.locale');
        }

        if (!in_array($locale, array_keys(config('app.supported_locales', [
            'en' => 'English',
            'vi' => 'Tiếng Việt'
        ])))) {
            $locale = config('app.fallback_locale');
        }

        App::setLocale($locale);

        /** @var \Illuminate\Http\JsonResponse $response */
        $response = $next($request);

        // Nếu response là JSON, thêm locale vào payload
        if (method_exists($response, 'getData')) {
            $data = $response->getData(true);
            $data['locale'] = $locale;
            $response->setData($data);
        }

        return $response;
    }
}
