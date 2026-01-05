<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Client\MiddlewareInterface;
use Fly\Http\Exception\CircuitBreakerException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Circuit Breaker middleware for fault tolerance.
 *
 * Implements the Circuit Breaker pattern to prevent cascading failures
 * by monitoring request failures and temporarily stopping requests to failing services.
 */
class CircuitBreakerMiddleware implements MiddlewareInterface
{
    /**
     * Circuit states.
     */
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';
    private const STATE_HALF_OPEN = 'half_open';

    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * @var string
     */
    private string $serviceKey;

    /**
     * @var int
     */
    private int $failureThreshold;

    /**
     * @var int
     */
    private int $recoveryTimeout;

    /**
     * @var int
     */
    private int $successThreshold;

    /**
     * @param CacheInterface $cache
     * @param string $serviceKey Unique key for the service
     * @param int $failureThreshold Number of failures before opening circuit
     * @param int $recoveryTimeout Seconds to wait before trying half-open
     * @param int $successThreshold Number of successes needed in half-open state
     */
    public function __construct(
        CacheInterface $cache,
        string $serviceKey,
        int $failureThreshold = 5,
        int $recoveryTimeout = 60,
        int $successThreshold = 3
    ) {
        $this->cache = $cache;
        $this->serviceKey = $serviceKey;
        $this->failureThreshold = $failureThreshold;
        $this->recoveryTimeout = $recoveryTimeout;
        $this->successThreshold = $successThreshold;
    }

    /**
     * Process request through circuit breaker.
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $state = $this->getCircuitState();

        if ($state === self::STATE_OPEN) {
            if (!$this->shouldAttemptReset()) {
                throw new CircuitBreakerException(
                    sprintf('Circuit breaker is OPEN for service: %s', $this->serviceKey),
                    $this->serviceKey
                );
            }

            $this->setCircuitState(self::STATE_HALF_OPEN);
            $state = self::STATE_HALF_OPEN;
        }

        try {
            $response = $next($request);

            if ($state === self::STATE_HALF_OPEN) {
                $this->recordSuccess();
            } else {
                $this->resetFailureCount();
            }

            return $response;
        } catch (\Throwable $exception) {
            $this->recordFailure();

            if ($this->shouldOpenCircuit()) {
                $this->setCircuitState(self::STATE_OPEN);
            }

            throw $exception;
        }
    }

    /**
     * Get current circuit state.
     */
    private function getCircuitState(): string
    {
        $state = $this->cache->get($this->getStateKey(), self::STATE_CLOSED);
        return $state;
    }

    /**
     * Set circuit state.
     */
    private function setCircuitState(string $state): void
    {
        $this->cache->set($this->getStateKey(), $state, $this->recoveryTimeout * 2);
    }

    /**
     * Check if circuit should attempt reset.
     */
    private function shouldAttemptReset(): bool
    {
        $lastFailureTime = $this->cache->get($this->getLastFailureKey(), 0);
        return (time() - $lastFailureTime) >= $this->recoveryTimeout;
    }

    /**
     * Record a successful request.
     */
    private function recordSuccess(): void
    {
        $successCount = $this->cache->get($this->getSuccessCountKey(), 0) + 1;

        if ($successCount >= $this->successThreshold) {
            $this->setCircuitState(self::STATE_CLOSED);
            $this->resetCounters();
        } else {
            $this->cache->set($this->getSuccessCountKey(), $successCount, $this->recoveryTimeout);
        }
    }

    /**
     * Record a failed request.
     */
    private function recordFailure(): void
    {
        $failureCount = $this->cache->get($this->getFailureCountKey(), 0) + 1;
        $this->cache->set($this->getFailureCountKey(), $failureCount, $this->recoveryTimeout * 2);
        $this->cache->set($this->getLastFailureKey(), time(), $this->recoveryTimeout * 2);
    }

    /**
     * Check if circuit should be opened.
     */
    private function shouldOpenCircuit(): bool
    {
        $failureCount = $this->cache->get($this->getFailureCountKey(), 0);
        return $failureCount >= $this->failureThreshold;
    }

    /**
     * Reset failure count.
     */
    private function resetFailureCount(): void
    {
        $this->cache->delete($this->getFailureCountKey());
    }

    /**
     * Reset all counters.
     */
    private function resetCounters(): void
    {
        $this->cache->delete($this->getFailureCountKey());
        $this->cache->delete($this->getSuccessCountKey());
        $this->cache->delete($this->getLastFailureKey());
    }

    /**
     * Get cache key for circuit state.
     */
    private function getStateKey(): string
    {
        return sprintf('circuit_breaker:%s:state', $this->serviceKey);
    }

    /**
     * Get cache key for failure count.
     */
    private function getFailureCountKey(): string
    {
        return sprintf('circuit_breaker:%s:failures', $this->serviceKey);
    }

    /**
     * Get cache key for success count.
     */
    private function getSuccessCountKey(): string
    {
        return sprintf('circuit_breaker:%s:successes', $this->serviceKey);
    }

    /**
     * Get cache key for last failure time.
     */
    private function getLastFailureKey(): string
    {
        return sprintf('circuit_breaker:%s:last_failure', $this->serviceKey);
    }
}
