<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->settings) {
            $locale = $request->user()->settings->language;
        } else {
            $locale = $request->getPreferredLanguage(['en', 'id']);
        }

        if ($locale) {
            \Illuminate\Support\Facades\App::setLocale($locale);
        }

        return $next($request);
    }
}
