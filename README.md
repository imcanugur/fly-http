# Fly HTTP Client

A PSR-18 compliant, middleware-based HTTP client library designed for enterprise applications. Built to compete with Guzzle while providing superior architecture for complex HTTP workflows.

[![Latest Version](https://img.shields.io/packagist/v/imcanugur/fly-http.svg?style=flat-square)](https://packagist.org/packages/imcanugur/fly-http)
[![License](https://img.shields.io/packagist/l/imcanugur/fly-http.svg?style=flat-square)](LICENSE)
[![PHP Version](https://img.shields.io/packagist/php-v/imcanugur/fly-http.svg?style=flat-square)](https://php.net)
[![PSR-18](https://img.shields.io/badge/PSR-18-blue.svg?style=flat-square)](https://www.php-fig.org/psr/psr-18/)

---

## 🎯 Purpose

Fly HTTP Client is not just a "helper for making HTTP requests." It provides a core HTTP traffic management system for enterprise applications.

The library focuses on architectural correctness, maintainability, and extensibility rather than feature bloat.

---

## ⚡ Key Features

- **PSR-18 Compliant**: Drop-in replacement for any PSR-18 HTTP client
- **Zero Dependencies**: Only requires PSR interfaces - no Guzzle, no external libraries
- **Middleware Pipeline**: Chain policies for retry, authentication, logging, caching, circuit breaker
- **Transport Abstraction**: Switch between native cURL, mock, or custom transports
- **Built-in PSR-7**: Complete HTTP message implementation included
- **Enterprise Ready**: Circuit breaker, metrics, caching for production systems
- **Immutable Requests**: Thread-safe, predictable request handling

---

## 🏗️ Architecture Overview

HTTP processing is divided into three distinct layers:

### 1. Client Layer (Orchestration)

- Executes the middleware pipeline
- Delegates to transport for actual HTTP calls
- Implements PSR-18 `ClientInterface`
- Manages business logic flow

### 2. Middleware Layer (Policy)

- Applies cross-cutting concerns
- Can modify requests immutably
- Observes and logs responses
- Handles errors and recovery
- Never makes HTTP calls directly

### 3. Transport Layer (Execution)

- Executes actual HTTP requests
- Can use Guzzle, native curl, or custom implementations
- Completely abstracted from client logic
- Enables testing and vendor flexibility

---

## 🔗 Middleware System

Middlewares form a processing pipeline:

```php
Request
  ↓
RetryMiddleware    (handles failures)
  ↓
LoggerMiddleware   (logs requests/responses)
  ↓
AuthMiddleware     (adds authentication)
  ↓
Transport          (Guzzle/Native/Custom)
  ↓
Response
```

Each middleware:
- Receives a `RequestInterface` and `$next` callable
- Can modify the request immutably
- Calls `$next($request)` to continue the pipeline
- Can observe and process the response

### Built-in Middlewares

- **RetryMiddleware**: Exponential backoff retry logic
- **LoggerMiddleware**: PSR-3 compatible request/response logging
- **AuthMiddleware**: Bearer token, Basic auth, custom auth strategies
- **TimeoutMiddleware**: Request and connection timeouts

---

## 🚀 Basic Usage

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

## ⚙️ Advanced Configuration

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

### Authentication & Timeouts

```php
use Fly\Http\Middleware\AuthMiddleware;
use Fly\Http\Middleware\TimeoutMiddleware;

// Authentication
$client->addMiddleware(new AuthMiddleware('your-token', 'Bearer'));

// Timeouts
$client->addMiddleware(new TimeoutMiddleware(30.0, 10.0));
```

## 🧪 Testing

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

## 📋 PSR-7 HTTP Messages

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

## 🚨 Error Handling

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

## 🚚 Transport Options

Transport abstraction allows switching implementations:

```php
// Production - Native cURL (recommended)
$client = new Client(new NativeCurlTransport());

// Testing - Mock
$client = new Client(new MockTransport());

// Development - With custom options
$client = new Client(new NativeCurlTransport([
    CURLOPT_TIMEOUT => 60,
    CURLOPT_SSL_VERIFYPEER => false, // Development only!
]));
```

## ⚡ Performance Tips

1. **Use NativeCurlTransport** for best performance
2. **Enable caching** for repeated requests
3. **Configure circuit breakers** for resilient systems
4. **Set appropriate timeouts** to prevent hanging
5. **Use connection pooling** in high-throughput scenarios

---

## 📋 PSR Compliance

The library adheres to established PHP standards:

- **PSR-18**: HTTP Client interface
- **PSR-7**: HTTP Messages (Request/Response)
- **PSR-3**: Logger interface
- **PSR-17**: HTTP Factories

This ensures compatibility across frameworks and prevents vendor lock-in.

---

## 🎯 When to Use

Use Fly HTTP Client when you need:

- **Complex HTTP workflows** with multiple policies
- **Enterprise applications** requiring audit trails
- **Microservices** with circuit breaker patterns
- **Testable HTTP code** with transport abstraction
- **Framework agnostic** HTTP client

Do not use for:
- Simple one-off HTTP calls
- Basic REST API consumption
- Applications without complex HTTP requirements

---


## 📦 Installation

```bash
composer require imcanugur/fly-http
```


---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Add tests for new functionality
4. Ensure PSR standards compliance
5. Submit a pull request

---

## 📄 License

MIT License - see [LICENSE](LICENSE) file for details.

---

**Enterprise-grade HTTP client built for production systems**
