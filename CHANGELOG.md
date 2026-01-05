# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-01-05

### Added
- PSR-18 compliant HTTP client with middleware pipeline
- Complete PSR-7 HTTP message implementation (zero external dependencies)
- Native cURL transport for high performance
- Circuit breaker middleware for fault tolerance
- Request/response caching middleware
- Metrics and monitoring middleware
- Authentication middleware with Bearer/Basic support
- Retry middleware with exponential backoff
- Timeout middleware for request/connection timeouts
- Logger middleware with PSR-3 compatibility
- Mock transport for comprehensive testing
- PHPUnit test suite with CI/CD pipeline
- Makefile for development workflow automation
- GitHub Actions CI/CD configuration
- Comprehensive documentation with examples

### Features
- **Zero Dependencies**: No external HTTP libraries required
- **Enterprise Ready**: Production-grade features for high-volume applications
- **Transport Abstraction**: Easy switching between transports
- **Middleware Architecture**: Extensible request/response processing pipeline
- **Fault Tolerance**: Circuit breaker and retry mechanisms
- **Observability**: Metrics, logging, and monitoring capabilities

### Technical Details
- PHP 8.2+ compatible
- PSR-18 HTTP Client interface compliance
- PSR-7 HTTP Messages implementation
- PSR-3 Logger interface support
- PSR-17 HTTP Factories included
- PSR-16 Simple Cache interface for caching features
