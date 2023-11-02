<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
    }

    public function render($request, Throwable $exception) {
        if ($exception instanceof NotFoundHttpException) {
            if ($request->wantsJson()) {
                return response()->json([
                    'status' => 'not found'
                ], 404);
            } else {
                return response('a', 404);
            }
        }

        if ($exception instanceof ValidationException) {
            $validationErrors = $exception->errors();

            return response()->json([
                'error' => 'Validation failed',
                'fields' => collect($validationErrors)->map(function($error) {
                    return $error[0];
                })
            ]);
        }

        return parent::render($request, $exception);
    }
}
