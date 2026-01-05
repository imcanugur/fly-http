<?php

declare(strict_types=1);

namespace Fly\Http\Transport;

use Fly\Http\Http\Response;
use Fly\Http\Http\ResponseFactory;
use Fly\Http\Http\Stream;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Native cURL transport adapter.
 *
 * Lightweight transport using PHP's built-in cURL extension.
 * No external dependencies required.
 */
class NativeCurlTransport implements ClientInterface
{
    /**
     * @var array
     */
    private array $defaultOptions;

    /**
     * @var ResponseFactory
     */
    private ResponseFactory $responseFactory;

    /**
     * @param array $defaultOptions Default cURL options
     */
    public function __construct(array $defaultOptions = [])
    {
        $this->defaultOptions = array_merge([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_USERAGENT => 'Fly-HTTP-Client/1.0',
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADER => false,
        ], $defaultOptions);

        $this->responseFactory = new ResponseFactory();
    }

    /**
     * Send HTTP request using native cURL.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $curlHandle = curl_init();

        try {
            $options = $this->buildCurlOptions($request);
            curl_setopt_array($curlHandle, $options);

            $responseBody = curl_exec($curlHandle);
            $httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
            $error = curl_error($curlHandle);
            $errorCode = curl_errno($curlHandle);

            if ($errorCode !== 0) {
                throw new \RuntimeException(
                    sprintf('cURL error (%d): %s', $errorCode, $error)
                );
            }

            return $this->createResponse($httpCode, $responseBody);
        } finally {
            curl_close($curlHandle);
        }
    }

    /**
     * Build cURL options from PSR-7 request.
     */
    private function buildCurlOptions(RequestInterface $request): array
    {
        $options = $this->defaultOptions;
        $uri = $request->getUri();

        // URL
        $options[CURLOPT_URL] = (string) $uri;

        // Method
        $method = $request->getMethod();
        $options[CURLOPT_CUSTOMREQUEST] = $method;

        // Headers
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = $name . ': ' . $value;
            }
        }
        $options[CURLOPT_HTTPHEADER] = $headers;

        // Body
        $body = (string) $request->getBody();
        if ($body !== '') {
            $options[CURLOPT_POSTFIELDS] = $body;
        }

        // Protocol version
        if ($request->getProtocolVersion() === '1.1') {
            $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_1_1;
        } elseif ($request->getProtocolVersion() === '2.0') {
            $options[CURLOPT_HTTP_VERSION] = CURL_HTTP_VERSION_2_0;
        }

        // Handle attributes (timeouts, etc.)
        if ($request->getAttribute('timeout')) {
            $options[CURLOPT_TIMEOUT] = $request->getAttribute('timeout');
        }
        if ($request->getAttribute('connect_timeout')) {
            $options[CURLOPT_CONNECTTIMEOUT] = $request->getAttribute('connect_timeout');
        }

        return $options;
    }

    /**
     * Create PSR-7 response from cURL result.
     */
    private function createResponse(int $statusCode, string $body): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($statusCode);
        $response = $response->withBody(new Stream($body));

        return $response;
    }
}
