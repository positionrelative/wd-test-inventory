<?php

declare(strict_types=1);

namespace App\OpenApi\Extensions;

use App\Http\Requests\IndexRequest;
use App\OpenApi\ErrorEnvelope;
use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Header;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Router;
use ReflectionNamedType;

/**
 * Cross-cutting API behaviour Scramble cannot infer from the code:
 * - list endpoints (actions taking an IndexRequest) return the compact cursor "meta";
 * - throttled routes can answer 429 with Retry-After.
 */
final class ApiOperationConventions extends OperationExtension
{
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        if ($this->isCursorList($routeInfo)) {
            $this->addCursorMeta($operation);
        }

        if ($this->isThrottled($routeInfo)) {
            $operation->addResponse($this->tooManyRequests());
        }
    }

    private function isCursorList(RouteInfo $routeInfo): bool
    {
        foreach ($routeInfo->reflectionMethod()?->getParameters() ?? [] as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && is_a($type->getName(), IndexRequest::class, true)) {
                return true;
            }
        }

        return false;
    }

    private function addCursorMeta(Operation $operation): void
    {
        foreach ($operation->responses ?? [] as $response) {
            if (!$response instanceof Response || $response->code !== 200) {
                continue;
            }

            $schema = $response->getContent('application/json');

            if (!$schema instanceof Schema || !$schema->type instanceof ObjectType) {
                continue;
            }

            $cursor = static fn (string $description) => (new StringType())->nullable(true)->setDescription($description);

            $schema->type
                ->addProperty('meta', (new ObjectType())
                    ->addProperty('per_page', new IntegerType())
                    ->addProperty('next_cursor', $cursor('Pass as ?cursor= for the next page; null on the last page.'))
                    ->addProperty('prev_cursor', $cursor('Pass as ?cursor= for the previous page; null on the first page.'))
                    ->setRequired(['per_page', 'next_cursor', 'prev_cursor']))
                ->addRequired(['meta']);
        }
    }

    private function isThrottled(RouteInfo $routeInfo): bool
    {
        // The HTTP kernel registers the middleware groups on the router; resolving it makes the
        // "api" group expandable in console runs too (scramble:export), not only in HTTP requests.
        app(HttpKernel::class);

        // Groups expanded ("api" → its middleware); alias or class form (throttleApi() adds ThrottleRequests:api).
        foreach (app(Router::class)->gatherRouteMiddleware($routeInfo->route) as $middleware) {
            if (is_string($middleware) && (str_starts_with($middleware, 'throttle:') || str_starts_with($middleware, ThrottleRequests::class))) {
                return true;
            }
        }

        return false;
    }

    private function tooManyRequests(): Reference
    {
        $components = $this->openApiTransformer->getComponents();
        $reference = new Reference('responses', 'TooManyRequests', $components);

        if ($components->has($reference)) {
            return $reference;
        }

        $response = ErrorEnvelope::response(429, 'too_many_requests', 'Rate limit exceeded', 'Too Many Requests.')
            ->addHeader('Retry-After', new Header(
                description: 'Seconds to wait before retrying.',
                schema: Schema::fromType(new IntegerType()),
            ));

        return $components->add($reference, $response);
    }
}
