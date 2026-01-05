<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Client\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Middleware for logging HTTP requests and responses.
 */
class LoggerMiddleware implements MiddlewareInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Process request and log it.
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $startTime = microtime(true);

        $this->logger->info('HTTP Request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $request->getHeaders(),
        ]);

        try {
            $response = $next($request);

            $duration = microtime(true) - $startTime;

            $this->logger->info('HTTP Response', [
                'status' => $response->getStatusCode(),
                'duration' => round($duration * 1000, 2) . 'ms',
                'headers' => $response->getHeaders(),
            ]);

            return $response;
        } catch (\Throwable $exception) {
            $duration = microtime(true) - $startTime;

            $this->logger->error('HTTP Request Failed', [
                'method' => $request->getMethod(),
                'uri' => (string) $request->getUri(),
                'duration' => round($duration * 1000, 2) . 'ms',
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
