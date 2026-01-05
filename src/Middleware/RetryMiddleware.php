<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Client\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Middleware for retrying failed HTTP requests with exponential backoff.
 */
class RetryMiddleware implements MiddlewareInterface
{
    /**
     * @var int
     */
    private int $maxRetries;

    /**
     * @var float
     */
    private float $baseDelay;

    /**
     * @param int $maxRetries
     * @param float $baseDelay Base delay in seconds
     */
    public function __construct(int $maxRetries = 3, float $baseDelay = 1.0)
    {
        $this->maxRetries = $maxRetries;
        $this->baseDelay = $baseDelay;
    }

    /**
     * Process request with retry logic.
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $lastException = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $next($request);
            } catch (\Throwable $exception) {
                $lastException = $exception;

                if ($attempt < $this->maxRetries && $this->shouldRetry($exception)) {
                    $delay = $this->calculateDelay($attempt);
                    usleep((int) ($delay * 1000000));
                    continue;
                }

                break;
            }
        }

        throw $lastException;
    }

    /**
     * Determine if the exception should trigger a retry.
     */
    private function shouldRetry(\Throwable $exception): bool
    {
        // Retry on network errors, timeouts, and 5xx server errors
        if ($exception instanceof \Psr\Http\Client\NetworkExceptionInterface) {
            return true;
        }

        if ($exception instanceof \Psr\Http\Client\RequestExceptionInterface) {
            $response = $exception->getResponse();
            if ($response && $response->getStatusCode() >= 500) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate exponential backoff delay.
     */
    private function calculateDelay(int $attempt): float
    {
        return $this->baseDelay * pow(2, $attempt);
    }
}
