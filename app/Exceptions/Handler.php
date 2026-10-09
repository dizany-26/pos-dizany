<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use PDOException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if (! $request->expectsJson() || ! $this->isTransientDatabaseFailure($e)) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'La base de datos está temporalmente ocupada. Espera unos segundos y vuelve a intentarlo.',
            ], 503);
        });
    }

    private function isTransientDatabaseFailure(Throwable $exception): bool
    {
        $databaseException = $exception instanceof QueryException
            || $exception instanceof PDOException;

        if (! $databaseException) {
            return false;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'max_user_connections')
            || str_contains($message, 'too many connections')
            || str_contains($message, 'no such file or directory')
            || str_contains($message, 'server has gone away')
            || str_contains($message, 'lost connection');
    }
}
