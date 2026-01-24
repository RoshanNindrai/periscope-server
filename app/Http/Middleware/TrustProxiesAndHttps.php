<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class TrustProxiesAndHttps
{
    /**
     * In production behind AWS ELB: trust forwarded headers and force HTTPS.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!app()->environment('production')) {
            return $next($request);
        }

        $request->server->set('HTTPS', 'on');
        Request::setTrustedProxies(
            ['*'],
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );
        URL::forceScheme('https');

        return $next($request);
    }
}
