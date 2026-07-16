<?php

namespace App\Support;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Renders every API failure as the safe error envelope defined in
 * REST_API_SPECIFICATION.md section 2.3, never leaking internal exception
 * messages, ORM attributes, or stack traces to the client.
 */
class ApiExceptionRenderer
{
    public static function register(Exceptions $exceptions): void
    {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            [$status, $code, $message, $details] = self::classify($exception);

            $requestId = $request->attributes->get('correlation_id') ?? (string) Str::uuid();

            return response()->json([
                'error' => array_filter([
                    'code' => $code,
                    'message' => $message,
                    'details' => $details,
                    'requestId' => $requestId,
                ], fn ($value) => $value !== null),
            ], $status)->header('X-Request-ID', $requestId);
        });
    }

    /**
     * @return array{0: int, 1: string, 2: string, 3: array|null}
     */
    private static function classify(Throwable $exception): array
    {
        return match (true) {
            $exception instanceof ValidationException => [
                422, 'VALIDATION_FAILED', 'The request could not be validated.', $exception->errors(),
            ],
            $exception instanceof AuthenticationException => [
                401, 'UNAUTHENTICATED', 'Authentication is required to access this resource.', null,
            ],
            $exception instanceof AuthorizationException => [
                403, 'FORBIDDEN', 'You do not have permission to perform this action.', null,
            ],
            $exception instanceof ModelNotFoundException, $exception instanceof NotFoundHttpException => [
                404, 'NOT_FOUND', 'The requested resource could not be found.', null,
            ],
            $exception instanceof ThrottleRequestsException => [
                429, 'RATE_LIMITED', 'Too many requests. Please try again shortly.', null,
            ],
            $exception instanceof HttpExceptionInterface => [
                $exception->getStatusCode(),
                self::codeForStatus($exception->getStatusCode()),
                self::safeMessageForStatus($exception->getStatusCode()),
                null,
            ],
            default => [500, 'INTERNAL_ERROR', 'An unexpected error occurred. Please try again.', null],
        };
    }

    private static function codeForStatus(int $status): string
    {
        return match ($status) {
            400 => 'INVALID_REQUEST',
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            409 => 'CONFLICT',
            422 => 'VALIDATION_FAILED',
            429 => 'RATE_LIMITED',
            default => 'INTERNAL_ERROR',
        };
    }

    private static function safeMessageForStatus(int $status): string
    {
        return match ($status) {
            400 => 'The request could not be processed.',
            401 => 'Authentication is required to access this resource.',
            403 => 'You do not have permission to perform this action.',
            404 => 'The requested resource could not be found.',
            409 => 'The request conflicts with the current state of the resource.',
            422 => 'The request could not be validated.',
            429 => 'Too many requests. Please try again shortly.',
            default => 'An unexpected error occurred. Please try again.',
        };
    }
}
