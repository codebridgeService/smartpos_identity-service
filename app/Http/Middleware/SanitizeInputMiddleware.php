<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInputMiddleware
{
    /**
     * Maximum allowed JSON/URL-encoded request body size in bytes (2MB default).
     */
    protected int $maxPayloadBytes = 2 * 1024 * 1024;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Enforce payload size limit on non-file requests to prevent memory exhaustion DoS
        if (! $request->is('*/avatar')) {
            $contentLength = (int) ($request->header('Content-Length') ?: $request->server('CONTENT_LENGTH', 0));
            $actualBodySize = strlen((string) $request->getContent());

            if ($contentLength > $this->maxPayloadBytes || $actualBodySize > $this->maxPayloadBytes) {
                return response()->json([
                    'success' => false,
                    'error' => 'PAYLOAD_TOO_LARGE',
                    'message' => 'Request payload exceeds the maximum allowed size of 2MB.',
                ], 413);
            }
        }

        // 2. Strip null-byte characters from query and body inputs
        $cleanInputs = $this->cleanInputs($request->all());
        $request->merge($cleanInputs);

        return $next($request);
    }

    /**
     * Recursively strip null bytes and malformed UTF-8 characters from input array.
     */
    protected function cleanInputs(mixed $input): mixed
    {
        if (is_array($input)) {
            return array_map([$this, 'cleanInputs'], $input);
        }

        if (is_string($input)) {
            // Strip null bytes and control chars (except standard newlines/tabs)
            $cleaned = str_replace("\0", '', $input);
            return mb_convert_encoding($cleaned, 'UTF-8', 'UTF-8');
        }

        return $input;
    }
}
