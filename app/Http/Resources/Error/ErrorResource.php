<?php

declare(strict_types=1);

namespace App\Http\Resources\Error;

use App\Http\Errors\ApiError;
use App\Http\RequestId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Standard error envelope: {"error": {status, code, message, errors?, request_id, debug?}}.
 *
 * @property ApiError $resource
 */
final class ErrorResource extends JsonResource
{
    /** @var string|null */
    public static $wrap = 'error';

    public function __construct(ApiError $error)
    {
        parent::__construct($error);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $error = $this->resource;

        return [
            'status' => $error->status,
            'code' => $error->code,
            'message' => $error->message,
            'errors' => $this->when($error->errors !== null, $error->errors),
            'request_id' => RequestId::of($request),
            'debug' => $this->when($error->debug !== null, $error->debug),
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode($this->resource->status);
        $response->headers->add($this->resource->headers);
    }
}
