<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->remove('X-Powered-By');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        if ($request->is('docs*') || $request->is('docs/*')) {
            $response->headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://unpkg.com https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com data:; connect-src 'self' https: http:; img-src 'self' data: https:; frame-ancestors 'none';");
        } else {
            $response->headers->set('Content-Security-Policy', "default-src 'none'; frame-ancestors 'none';");
        }

        $response->headers->set('Permissions-Policy', 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        return $response;
    }
}
