<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Global exception handler for API responses.
 *
 * LARAVEL vs SYMFONY — Exception Handling:
 * - Laravel: app/Exceptions/Handler.php with render() method
 *   Catches exceptions and converts them to HTTP responses.
 *   Uses $dontReport, $dontFlash, renderable() closures.
 *
 * - Symfony: Event Listeners on KernelEvents::EXCEPTION
 *   The kernel dispatches an ExceptionEvent when an exception is thrown.
 *   Any listener can catch it and set a response.
 *
 * KEY DIFFERENCE:
 * - Laravel has ONE centralized exception handler class
 * - Symfony uses the Event Dispatcher pattern — multiple listeners can handle exceptions
 *   Priority determines order. This is more flexible but requires understanding events.
 *
 * MIDDLEWARE vs KERNEL EVENTS:
 * - Laravel: Middleware wraps the request/response pipeline
 * - Symfony: Kernel Events (kernel.request, kernel.response, kernel.exception, etc.)
 *   serve a similar purpose but are event-driven rather than middleware-chain-driven.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: 0)]
class ExceptionListener
{
    public function __construct(
        private readonly bool $debug,
    ) {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        } elseif ($exception instanceof \InvalidArgumentException) {
            $statusCode = Response::HTTP_BAD_REQUEST;
        } else {
            $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;
        }

        // In production, hide internal error details for 500 errors to avoid leaking
        // sensitive information (SQL queries, file paths, etc.)
        $message = ($statusCode === Response::HTTP_INTERNAL_SERVER_ERROR && !$this->debug)
            ? 'Internal server error.'
            : $exception->getMessage();

        $data = [
            'error' => true,
            'message' => $message,
            'code' => $statusCode,
        ];

        $response = new JsonResponse($data, $statusCode);
        $event->setResponse($response);
    }
}
