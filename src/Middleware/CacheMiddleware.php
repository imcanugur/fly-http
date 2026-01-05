<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Client\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Middleware for caching HTTP requests and responses.
 *
 * Implements intelligent caching with cache-busting headers and TTL management.
 */
class CacheMiddleware implements MiddlewareInterface
{
    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * @var int
     */
    private int $defaultTtl;

    /**
     * @var callable|null
     */
    private $cacheKeyGenerator;

    /**
     * @var callable|null
     */
    private $shouldCacheRequest;

    /**
     * @param CacheInterface $cache
     * @param int $defaultTtl Default TTL in seconds
     * @param callable|null $cacheKeyGenerator Custom cache key generator
     * @param callable|null $shouldCacheRequest Custom cache decision logic
     */
    public function __construct(
        CacheInterface $cache,
        int $defaultTtl = 300,
        ?callable $cacheKeyGenerator = null,
        ?callable $shouldCacheRequest = null
    ) {
        $this->cache = $cache;
        $this->defaultTtl = $defaultTtl;
        $this->cacheKeyGenerator = $cacheKeyGenerator ?? [$this, 'generateCacheKey'];
        $this->shouldCacheRequest = $shouldCacheRequest ?? [$this, 'shouldCache'];
    }

    /**
     * Process request with caching logic.
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        if (!$this->shouldCache($request)) {
            return $next($request);
        }

        $cacheKey = $this->generateCacheKey($request);

        // Check cache first
        $cachedResponse = $this->cache->get($cacheKey);
        if ($cachedResponse !== null) {
            return $this->restoreResponse($cachedResponse, $cacheKey);
        }

        // Execute request
        $response = $next($request);

        // Cache successful responses
        if ($this->shouldCacheResponse($response)) {
            $ttl = $this->getResponseTtl($response);
            $this->cache->set($cacheKey, $this->serializeResponse($response), $ttl);
        }

        return $response;
    }

    /**
     * Generate cache key for request.
     */
    protected function generateCacheKey(RequestInterface $request): string
    {
        $key = sprintf(
            'http_cache:%s:%s:%s',
            $request->getMethod(),
            (string) $request->getUri(),
            md5($request->getBody()->getContents())
        );

        // Include relevant headers in cache key
        $headers = $request->getHeaders();
        $cacheHeaders = [];

        foreach (['Authorization', 'Accept', 'Accept-Language', 'X-API-Key'] as $header) {
            if (isset($headers[$header])) {
                $cacheHeaders[$header] = $headers[$header];
            }
        }

        if (!empty($cacheHeaders)) {
            $key .= ':' . md5(serialize($cacheHeaders));
        }

        return $key;
    }

    /**
     * Determine if request should be cached.
     */
    protected function shouldCache(RequestInterface $request): bool
    {
        // Only cache GET and HEAD requests
        if (!in_array($request->getMethod(), ['GET', 'HEAD'])) {
            return false;
        }

        // Don't cache if Cache-Control: no-cache is present
        $cacheControl = $request->getHeader('Cache-Control');
        if (!empty($cacheControl) && in_array('no-cache', $cacheControl)) {
            return false;
        }

        return true;
    }

    /**
     * Determine if response should be cached.
     */
    protected function shouldCacheResponse(ResponseInterface $response): bool
    {
        $statusCode = $response->getStatusCode();

        // Only cache 2xx responses
        if ($statusCode < 200 || $statusCode >= 300) {
            return false;
        }

        // Don't cache if Cache-Control: no-store is present
        $cacheControl = $response->getHeader('Cache-Control');
        if (!empty($cacheControl) && in_array('no-store', $cacheControl)) {
            return false;
        }

        return true;
    }

    /**
     * Get TTL from response headers or use default.
     */
    protected function getResponseTtl(ResponseInterface $response): int
    {
        $cacheControl = $response->getHeader('Cache-Control');

        if (!empty($cacheControl)) {
            foreach ($cacheControl as $directive) {
                if (preg_match('/max-age=(\d+)/', $directive, $matches)) {
                    return (int) $matches[1];
                }
            }
        }

        return $this->defaultTtl;
    }

    /**
     * Serialize response for caching.
     */
    protected function serializeResponse(ResponseInterface $response): array
    {
        return [
            'status' => $response->getStatusCode(),
            'reason' => $response->getReasonPhrase(),
            'headers' => $response->getHeaders(),
            'body' => (string) $response->getBody(),
            'protocol' => $response->getProtocolVersion(),
            'cached_at' => time(),
        ];
    }

    /**
     * Restore response from cache.
     */
    protected function restoreResponse(array $data, string $cacheKey): ResponseInterface
    {
        // Create PSR-7 response (this would need a PSR-17 factory in real implementation)
        // For now, return a mock response structure
        throw new \RuntimeException('Response restoration requires PSR-17 HTTP factory implementation');
    }
}
