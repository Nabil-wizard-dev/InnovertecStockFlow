<?php

use App\Enums\ErrorCode;
use App\Exceptions\ApiException;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $isApi = fn (Request $request): bool => $request->is('api/*') || $request->expectsJson();

        /*
         * Les callbacks sont evalues dans l'ordre d'enregistrement et le
         * premier qui repond gagne (Handler.php:809). Les cas precis d'abord,
         * le filet Throwable en dernier.
         *
         * Les exceptions derivant de ApiException portent leur propre
         * methode render() et n'ont donc rien a faire ici.
         */

        // Validation -> 422
        $exceptions->render(fn (ValidationException $e, Request $request) => $isApi($request)
            ? ApiResponse::validationError($e->errors())
            : null);

        // Modele absent / route inconnue -> 404
        $exceptions->render(fn (ModelNotFoundException $e, Request $request) => $isApi($request)
            ? ApiResponse::notFound()
            : null);

        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => $isApi($request)
            ? ApiResponse::notFound()
            : null);

        // Verbe HTTP interdit -> 405
        $exceptions->render(fn (MethodNotAllowedHttpException $e, Request $request) => $isApi($request)
            ? ApiResponse::error(errorCode: ErrorCode::MethodNotAllowed)
            : null);

        // Limitation de debit -> 429
        $exceptions->render(fn (TooManyRequestsHttpException $e, Request $request) => $isApi($request)
            ? ApiResponse::error(errorCode: ErrorCode::TooManyRequests)
            : null);

        // Authentification / autorisation
        $exceptions->render(fn (AuthenticationException $e, Request $request) => $isApi($request)
            ? ApiResponse::error(errorCode: ErrorCode::Unauthenticated)
            : null);

        $exceptions->render(fn (AuthorizationException $e, Request $request) => $isApi($request)
            ? ApiResponse::error(errorCode: ErrorCode::Forbidden)
            : null);

        /*
         * Filet de securite : toute exception technique non prevue.
         *
         * En local (APP_DEBUG=true) on laisse passer pour conserver la trace
         * complete de Laravel, indispensable au debogage. En production la
         * trace ne doit jamais fuir : seul le code SERVER_ERROR sort.
         */
        $exceptions->render(function (Throwable $e, Request $request) use ($isApi) {
            if (! $isApi($request)) {
                return null;
            }

            // Deja traite plus haut, ou exception HTTP portant son propre statut.
            if ($e instanceof ApiException || $e instanceof HttpExceptionInterface) {
                return null;
            }

            if (config('app.debug')) {
                return null;
            }

            report($e);

            return ApiResponse::error(errorCode: ErrorCode::ServerError);
        });
    })->create();
