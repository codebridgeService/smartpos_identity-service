<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AttackShieldMiddleware
{
    /**
     * Known vulnerability scanners and attack tool User-Agent signatures.
     */
    protected array $blockedUserAgents = [
        'sqlmap',
        'nikto',
        'acunetix',
        'w3af',
        'havij',
        'dirbuster',
        'gobuster',
        'nmap',
        'masscan',
        'zgrab',
        'hydra',
        'metasploit',
        'morfeus',
        'nessus',
        'arachni',
    ];

    /**
     * Probed paths commonly targeted by automated vulnerability bots.
     */
    protected array $blockedPathPatterns = [
        '/\.env',
        '/\.git',
        '/\.aws',
        '/\.svn',
        '/phpmyadmin',
        '/pma',
        '/wp-admin',
        '/wp-login',
        '/wp-content',
        '/actuator',
        '/cgi-bin',
        '/setup\.php',
        '/xmlrpc\.php',
        '/config\.json',
        '/dump\.sql',
        '/backup\.sql',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = strtolower((string) $request->header('User-Agent', ''));
        $path = $request->path();
        $queryString = rawurldecode((string) $request->getQueryString());

        // 1. Block known scanner User-Agents
        foreach ($this->blockedUserAgents as $scanner) {
            if ($userAgent !== '' && str_contains($userAgent, $scanner)) {
                $this->logAttack($request, "Malicious Scanner User-Agent blocked: {$scanner}");

                return response()->json([
                    'success' => false,
                    'error' => 'FORBIDDEN',
                    'message' => 'Automated scanning tools and malicious agents are blocked.',
                ], 403);
            }
        }

        // 2. Block malicious reconnaissance probes targeting sensitive files
        foreach ($this->blockedPathPatterns as $pattern) {
            if (preg_match('#' . $pattern . '#i', '/' . $path)) {
                $this->logAttack($request, "Reconnaissance path probe blocked: {$path}");

                return response()->json([
                    'success' => false,
                    'error' => 'NOT_FOUND',
                    'message' => 'The requested resource was not found.',
                ], 404);
            }
        }

        // 3. Detect Path Traversal attempts
        if (str_contains($path, '..') || str_contains($queryString, '..')) {
            $this->logAttack($request, "Path traversal attempt blocked: {$path}?{$queryString}");

            return response()->json([
                'success' => false,
                'error' => 'BAD_REQUEST',
                'message' => 'Invalid path structure detected.',
            ], 400);
        }

        return $next($request);
    }

    /**
     * Log suspicious attack attempts.
     */
    protected function logAttack(Request $request, string $reason): void
    {
        Log::warning("[AttackShield] {$reason}", [
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
            'user_agent' => $request->header('User-Agent'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
