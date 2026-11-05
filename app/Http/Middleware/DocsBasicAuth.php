<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP Basic Auth in front of the API documentation (/docs/api, /docs/api.json).
 *
 * Credentials come from config (api.docs.*, i.e. API_DOCS_USERNAME / API_DOCS_PASSWORD).
 * When they are not configured, the docs stay closed: no default credentials.
 */
final class DocsBasicAuth
{
    private const REALM = 'API documentation';

    public function __construct(
        #[Config('api.docs.username')]
        private readonly ?string $username = null,
        #[Config('api.docs.password')]
        private readonly ?string $password = null,
    ) {
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->authenticated($request)) {
            return response('Unauthorized.', Response::HTTP_UNAUTHORIZED, [
                'WWW-Authenticate' => sprintf('Basic realm="%s", charset="UTF-8"', self::REALM),
            ]);
        }

        return $next($request);
    }

    private function authenticated(Request $request): bool
    {
        if ($this->username === null || $this->username === '' || $this->password === null || $this->password === '') {
            return false;
        }

        // hash_equals: constant-time comparison, no timing leak of the credentials.
        return hash_equals($this->username, (string) $request->getUser())
            && hash_equals($this->password, (string) $request->getPassword());
    }
}
