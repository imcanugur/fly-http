<?php

declare(strict_types=1);

namespace Fly\Http\Transport;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Mock transport for testing.
 *
 * Allows predefined responses and request inspection for unit testing.
 */
class MockTransport implements ClientInterface
{
    /**
     * @var array<ResponseInterface>
     */
    private array $responses = [];

    /**
     * @var array<RequestInterface>
     */
    private array $requests = [];

    /**
     * @var \Closure|null
     */
    private ?\Closure $responseFactory = null;

    /**
     * Add a response to the queue.
     */
    public function addResponse(ResponseInterface $response): self
    {
        $this->responses[] = $response;
        return $this;
    }

    /**
     * Set a response factory function.
     */
    public function setResponseFactory(\Closure $factory): self
    {
        $this->responseFactory = $factory;
        return $this;
    }

    /**
     * Send mock request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        if ($this->responseFactory !== null) {
            return ($this->responseFactory)($request);
        }

        if (empty($this->responses)) {
            throw new \RuntimeException('No mock responses available');
        }

        return array_shift($this->responses);
    }

    /**
     * Get all captured requests.
     */
    public function getRequests(): array
    {
        return $this->requests;
    }

    /**
     * Get the last captured request.
     */
    public function getLastRequest(): ?RequestInterface
    {
        return end($this->requests) ?: null;
    }

    /**
     * Clear captured requests.
     */
    public function clearRequests(): void
    {
        $this->requests = [];
    }

    /**
     * Clear queued responses.
     */
    public function clearResponses(): void
    {
        $this->responses = [];
    }

    /**
     * Reset the transport state.
     */
    public function reset(): void
    {
        $this->clearRequests();
        $this->clearResponses();
        $this->responseFactory = null;
    }

    /**
     * Assert that a request was made with specific criteria.
     */
    public function assertRequestMade(
        ?string $method = null,
        ?string $uri = null,
        ?array $headers = null
    ): void {
        foreach ($this->requests as $request) {
            if ($method !== null && $request->getMethod() !== $method) {
                continue;
            }

            if ($uri !== null && (string) $request->getUri() !== $uri) {
                continue;
            }

            if ($headers !== null) {
                $requestHeaders = $request->getHeaders();
                $matches = true;

                foreach ($headers as $name => $value) {
                    if (!isset($requestHeaders[$name]) || !in_array($value, $requestHeaders[$name])) {
                        $matches = false;
                        break;
                    }
                }

                if (!$matches) {
                    continue;
                }
            }

            // Found a matching request
            return;
        }

        throw new \RuntimeException('Expected request was not made');
    }
}
