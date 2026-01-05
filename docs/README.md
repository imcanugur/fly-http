# Fly HTTP Client Documentation

## Basic Usage

```php
use Fly\Http\Client;
use Fly\Http\Transport\NativeCurlTransport;
use Fly\Http\Middleware\RetryMiddleware;
use Fly\Http\Middleware\LoggerMiddleware;

// Create transport (zero dependencies!)
$transport = new NativeCurlTransport();

// Create client
$client = new Client($transport);

// Add middlewares
$client->addMiddleware(new RetryMiddleware(3, 1.0));
$client->addMiddleware(new LoggerMiddleware($logger));

// Make request
$response = $client->sendRequest($request);
```

## Advanced Configuration

### Circuit Breaker for Fault Tolerance

```php
use Fly\Http\Middleware\CircuitBreakerMiddleware;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Create cache for circuit breaker state
$cache = new FilesystemAdapter();

// Add circuit breaker middleware
$client->addMiddleware(new CircuitBreakerMiddleware(
    $cache,
    'api-service',  // Service identifier
    5,              // Failure threshold
    60,             // Recovery timeout (seconds)
    3               // Success threshold for half-open
));
```

### Request Caching

```php
use Fly\Http\Middleware\CacheMiddleware;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

// Create cache for HTTP responses
$cache = new FilesystemAdapter();

// Add caching middleware
$client->addMiddleware(new CacheMiddleware(
    $cache,
    300  // TTL in seconds
));
```

### Metrics & Monitoring

```php
use Fly\Http\Middleware\MetricsMiddleware;

// Add metrics collection
$client->addMiddleware(new MetricsMiddleware($logger, 'api-client'));

// Later, get metrics snapshot
$metrics = $client->getMetrics();
$prometheusOutput = $client->exportMetrics();
```

## Authentication & Timeouts

```php
use Fly\Http\Middleware\AuthMiddleware;
use Fly\Http\Middleware\TimeoutMiddleware;

// Authentication
$client->addMiddleware(new AuthMiddleware('your-token', 'Bearer'));

// Timeouts
$client->addMiddleware(new TimeoutMiddleware(30.0, 10.0));
```

## Custom Middleware

```php
use Fly\Http\Client\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CustomMiddleware implements MiddlewareInterface
{
    public function process(RequestInterface $request, callable $next): ResponseInterface
    {
        // Pre-processing
        $modifiedRequest = $request->withHeader('X-Custom', 'value');

        $response = $next($modifiedRequest);

        // Post-processing
        return $response->withHeader('X-Processed', 'true');
    }
}
```

## Custom Transport

```php
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class CustomTransport implements ClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // Your custom HTTP implementation
        // Return PSR-7 Response
    }
}
```

## Testing

```php
use Fly\Http\Client;
use Fly\Http\Transport\MockTransport;

// Create mock transport for testing
$mockTransport = new MockTransport();
$client = new Client($mockTransport);

// Add predefined responses
$mockTransport->addResponse($successResponse);
$mockTransport->addResponse($errorResponse);

// Or use response factory
$mockTransport->setResponseFactory(function ($request) {
    return new Response(200, [], 'OK');
});

// Test your HTTP logic
$response = $client->sendRequest($request);

// Assert requests were made
$mockTransport->assertRequestMade('GET', 'https://api.example.com/users');
```

## PSR-7 HTTP Messages

Fly includes its own PSR-7 implementation for zero dependencies:

```php
use Fly\Http\Http\RequestFactory;
use Fly\Http\Http\ResponseFactory;
use Fly\Http\Http\StreamFactory;
use Fly\Http\Http\UriFactory;

// Create PSR-17 factories
$requestFactory = new RequestFactory();
$responseFactory = new ResponseFactory();
$streamFactory = new StreamFactory();
$uriFactory = new UriFactory();

// Create messages
$request = $requestFactory->createRequest('GET', 'https://api.example.com');
$response = $responseFactory->createResponse(200);
$stream = $streamFactory->createStream('Hello World');
$uri = $uriFactory->createUri('https://api.example.com');
```

## Error Handling

```php
use Fly\Http\Exception\CircuitBreakerException;

try {
    $response = $client->sendRequest($request);
} catch (CircuitBreakerException $e) {
    // Circuit breaker is open
    echo "Service unavailable: " . $e->getServiceKey();
} catch (\Psr\Http\Client\ClientExceptionInterface $e) {
    // HTTP client error
    echo "HTTP Error: " . $e->getMessage();
}
```

## Performance Tips

1. **Use NativeCurlTransport** for best performance
2. **Enable caching** for repeated requests
3. **Configure circuit breakers** for resilient systems
4. **Set appropriate timeouts** to prevent hanging
5. **Use connection pooling** in high-throughput scenarios
