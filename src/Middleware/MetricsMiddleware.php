<?php

declare(strict_types=1);

namespace Fly\Http\Middleware;

use Fly\Http\Client\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Middleware for collecting HTTP metrics and monitoring.
 *
 * Tracks request/response metrics, latency, error rates, and success rates.
 */
class MetricsMiddleware implements MiddlewareInterface
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var string
     */
    private string $serviceName;

    /**
     * @var array
     */
    private array $metrics = [];

    /**
     * @param LoggerInterface $logger
     * @param string $serviceName Name of the service being monitored
     */
    public function __construct(LoggerInterface $logger, string $serviceName = 'http_client')
    {
        $this->logger = $logger;
        $this->serviceName = $serviceName;
    }

    /**
     * Process request and collect metrics.
     */
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        $startTime = microtime(true);
        $method = $request->getMethod();
        $uri = $request->getUri();
        $host = $uri->getHost();

        try {
            $response = $next($request);

            $duration = microtime(true) - $startTime;
            $statusCode = $response->getStatusCode();

            $this->recordMetric('request_total', 1, [
                'method' => $method,
                'host' => $host,
                'status' => (string) $statusCode,
                'status_class' => $this->getStatusClass($statusCode),
            ]);

            $this->recordMetric('request_duration_seconds', $duration, [
                'method' => $method,
                'host' => $host,
            ]);

            $this->logger->info('HTTP Request Completed', [
                'service' => $this->serviceName,
                'method' => $method,
                'host' => $host,
                'path' => $uri->getPath(),
                'status' => $statusCode,
                'duration_ms' => round($duration * 1000, 2),
            ]);

            return $response;
        } catch (\Throwable $exception) {
            $duration = microtime(true) - $startTime;

            $this->recordMetric('request_total', 1, [
                'method' => $method,
                'host' => $host,
                'status' => 'error',
                'error_type' => get_class($exception),
            ]);

            $this->recordMetric('request_errors_total', 1, [
                'method' => $method,
                'host' => $host,
                'error_type' => get_class($exception),
            ]);

            $this->logger->error('HTTP Request Failed', [
                'service' => $this->serviceName,
                'method' => $method,
                'host' => $host,
                'path' => $uri->getPath(),
                'error' => $exception->getMessage(),
                'duration_ms' => round($duration * 1000, 2),
            ]);

            throw $exception;
        }
    }

    /**
     * Record a metric.
     */
    private function recordMetric(string $name, float $value, array $labels = []): void
    {
        $metricKey = $name . ':' . md5(serialize($labels));

        if (!isset($this->metrics[$metricKey])) {
            $this->metrics[$metricKey] = [
                'name' => $name,
                'value' => 0,
                'labels' => $labels,
                'timestamp' => time(),
            ];
        }

        $this->metrics[$metricKey]['value'] += $value;
        $this->metrics[$metricKey]['timestamp'] = time();
    }

    /**
     * Get status class (2xx, 3xx, 4xx, 5xx).
     */
    private function getStatusClass(int $statusCode): string
    {
        if ($statusCode >= 200 && $statusCode < 300) {
            return '2xx';
        }
        if ($statusCode >= 300 && $statusCode < 400) {
            return '3xx';
        }
        if ($statusCode >= 400 && $statusCode < 500) {
            return '4xx';
        }
        if ($statusCode >= 500 && $statusCode < 600) {
            return '5xx';
        }
        return 'unknown';
    }

    /**
     * Get current metrics snapshot.
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    /**
     * Export metrics in Prometheus format.
     */
    public function exportMetrics(): string
    {
        $output = '';

        foreach ($this->metrics as $metric) {
            $labels = '';
            if (!empty($metric['labels'])) {
                $labelPairs = [];
                foreach ($metric['labels'] as $key => $value) {
                    $labelPairs[] = sprintf('%s="%s"', $key, $value);
                }
                $labels = '{' . implode(',', $labelPairs) . '}';
            }

            $output .= sprintf(
                "# HELP %s HTTP client metric\n# TYPE %s counter\http_client_%s%s %s\n",
                $metric['name'],
                $metric['name'],
                $metric['name'],
                $labels,
                $metric['value']
            );
        }

        return $output;
    }

    /**
     * Reset metrics.
     */
    public function resetMetrics(): void
    {
        $this->metrics = [];
    }
}
