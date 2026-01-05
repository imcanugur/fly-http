<?php

declare(strict_types=1);

namespace Fly\Http\Exception;

use RuntimeException;

/**
 * Exception thrown when circuit breaker is open.
 */
class CircuitBreakerException extends RuntimeException
{
    /**
     * @var string
     */
    private string $serviceKey;

    /**
     * @param string $message
     * @param string $serviceKey
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message,
        string $serviceKey,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->serviceKey = $serviceKey;
    }

    /**
     * Get the service key that triggered the circuit breaker.
     */
    public function getServiceKey(): string
    {
        return $this->serviceKey;
    }
}
