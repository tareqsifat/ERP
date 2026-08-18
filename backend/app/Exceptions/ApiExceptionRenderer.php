<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * sdd.md §3: every 4xx/5xx API response uses the same {message, errors}
 * envelope — no module invents its own error shape.
 *
 * failed_doc.md §8: in non-debug mode this NEVER leaks a stack trace, SQL
 * error text, or file path back to the client. The one and only place
 * `config('app.debug')` is consulted for that decision is here.
 */
class ApiExceptionRenderer
{
    public function render(Throwable $e, Request $request): JsonResponse
    {
        [$status, $message, $errors] = match (true) {
            $e instanceof ValidationException => [422, 'The given data was invalid.', $e->errors()],
            $e instanceof AuthenticationException => [401, 'Unauthenticated.', null],
            $e instanceof AuthorizationException => [403, 'This action is unauthorized.', null],
            $e instanceof ModelNotFoundException => [404, 'Resource not found.', null],
            $e instanceof ThrottleRequestsException => [429, 'Too many requests.', null],
            $e instanceof HttpExceptionInterface => [$e->getStatusCode(), $e->getMessage() ?: 'Request failed.', null],
            default => [500, 'Server error.', null],
        };

        $payload = ['message' => $message];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        // Only ever attach debug detail when APP_DEBUG is explicitly on —
        // this is the single gate failed_doc.md §8 checks against.
        if (config('app.debug')) {
            $payload['debug'] = [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];
        }

        return response()->json($payload, $status);
    }
}
