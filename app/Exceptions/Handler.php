<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Bugsnag\BugsnagLaravel\BugsnagExceptionHandler as BugsnagExceptionHandler;
use Illuminate\Http\Response;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Validation\ValidationException::class,
    ];

    public function report(Exception $e)
    {
        parent::report($e);

        if (app()->environment('production') && $this->shouldReport($e)) {
            app(BugsnagExceptionHandler::class)->report($e);
        }
    }

    public function render($request, Exception $e)
    {
        if ($this->isHttpException($e)) {
            return $this->renderHttpException($e);
        }

        if ($this->shouldntReport($e)) {
            return parent::render($request, $e);
        }

        if (config('app.debug')) {
            return $this->renderExceptionWithWhoops($e);
        }

        return parent::render($request, $e);
    }

    protected function renderExceptionWithWhoops(Exception $e): Response
    {
        $this->unsetSensitiveData();

        $whoops = new \Whoops\Run();
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler());

        return new \Illuminate\Http\Response(
            $whoops->handleException($e),
            $e->getStatusCode(),
            $e->getHeaders()
        );
    }

    /*
     * Don't ever display sensitive data in Whoops pages.
     */
    protected function unsetSensitiveData()
    {
        foreach ($_ENV as $key => $value) {
            unset($_SERVER[$key]);
        }

        $_ENV = [];
    }
    /**
     * Convert an authentication exception into an unauthenticated response.
     *
     * @param \Illuminate\Http\Request                 $request
     * @param \Illuminate\Auth\AuthenticationException $e
     *
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $e)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        } else {
            return redirect()->guest('login');
        }
    }
}
