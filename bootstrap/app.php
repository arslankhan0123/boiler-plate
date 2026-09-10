<?php

declare(strict_types=1);

use App\Facades\ApiResponse;
use App\Http\Middleware\CaptureRequestContext;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTenantInitialized;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\InitializeTenancyFromUser;
use App\Http\Middleware\LogApiActivity;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // API-only: every API request is treated as JSON so errors/auth
        // failures return the JSON envelope instead of HTML or a login redirect.
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        // Snapshot the request context (ip, device, url) for background audit logs,
        // then log one activity row per request on terminate (after the response).
        $middleware->api(append: [
            CaptureRequestContext::class,
            LogApiActivity::class,
        ]);

        // Tenant resolution from the authenticated user. Applied per-route
        // AFTER `auth:api` (see routes) so the user is already resolved.
        // `tenant.required` (after `tenant.init`) rejects non-tenant contexts.
        $middleware->alias([
            'tenant.init' => InitializeTenancyFromUser::class,
            'tenant.required' => EnsureTenantInitialized::class,
            'platform.admin' => EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Map exceptions onto the single response envelope for API requests.
        $exceptions->render(fn (ValidationException $e, Request $request) => $request->is('api/*')
            ? ApiResponse::validationErrorResponse('The given data was invalid.', Arr::flatten($e->errors()))
            : null);

        $exceptions->render(fn (AuthenticationException $e, Request $request) => $request->is('api/*')
            ? ApiResponse::unauthorizedErrorResponse('Please login first.')
            : null);

        $exceptions->render(fn (AuthorizationException $e, Request $request) => $request->is('api/*')
            ? ApiResponse::forbiddenErrorResponse($e->getMessage())
            : null);

        $exceptions->render(fn (ModelNotFoundException $e, Request $request) => $request->is('api/*')
            ? ApiResponse::notFoundErrorResponse('Resource not found.')
            : null);

        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => $request->is('api/*')
            ? ApiResponse::notFoundErrorResponse('Resource not found.')
            : null);

        $exceptions->render(function (ThrottleRequestsException $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $headers = $e->getHeaders();
            $retryAfter = $headers['Retry-After'] ?? null;
            $errors = [];

            if (is_numeric($retryAfter)) {
                $now = Carbon::now();
                $retryIn = $now->copy()->addSeconds((int) $retryAfter)
                    ->diffForHumans($now, syntax: CarbonInterface::DIFF_ABSOLUTE);
                $errors[] = 'Too many attempts. Please retry after '.$retryIn.'.';
            }

            return ApiResponse::failedResponse('Too many requests.', $errors, 429)
                ->withHeaders($headers);
        });

        // Catch-all: API requests ALWAYS receive the JSON envelope, never an
        // HTML error page — even with debug on. Detail messages go in
        // data.errors (strings); the headline message stays stable and safe.
        // Internals are exposed only when debugging (Section 9, 19).
        $exceptions->render(function (Throwable $e, Request $request): ?JsonResponse {
            if (! $request->is('api/*')) {
                return null;
            }

            $errors = [];

            if (config('app.debug') === true) {
                for ($cause = $e; $cause !== null; $cause = $cause->getPrevious()) {
                    $errors[] = $cause::class.': '.$cause->getMessage()
                        .' (at '.$cause->getFile().':'.$cause->getLine().')';
                }
            }

            // Preserve correct HTTP status for framework HTTP exceptions (404, 405, 503, ...).
            if ($e instanceof HttpExceptionInterface) {
                return ApiResponse::failedResponse(
                    $e->getMessage() !== '' ? $e->getMessage() : 'Request failed.',
                    $errors,
                    $e->getStatusCode(),
                );
            }

            return ApiResponse::errorResponse('Something went wrong.', $errors);
        });
    })->create();
