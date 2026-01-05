<?php

declare(strict_types=1);

namespace Fly\Http\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-7 HTTP Response implementation.
 */
class Response implements ResponseInterface
{
    /**
     * @var int
     */
    private int $statusCode;

    /**
     * @var array
     */
    private array $headers = [];

    /**
     * @var StreamInterface
     */
    private StreamInterface $body;

    /**
     * @var string
     */
    private string $protocolVersion;

    /**
     * @var string
     */
    private string $reasonPhrase;

    /**
     * Status code to reason phrase mapping.
     */
    private const STATUS_PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @param int $statusCode
     * @param array $headers
     * @param mixed $body
     * @param string $protocolVersion
     * @param string|null $reasonPhrase
     */
    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        $body = null,
        string $protocolVersion = '1.1',
        ?string $reasonPhrase = null
    ) {
        $this->statusCode = $statusCode;
        $this->headers = $this->normalizeHeaders($headers);
        $this->body = $body instanceof StreamInterface ? $body : new Stream($body ?? '');
        $this->protocolVersion = $protocolVersion;
        $this->reasonPhrase = $reasonPhrase ?? (self::STATUS_PHRASES[$statusCode] ?? '');
    }

    /**
     * Normalize headers to lowercase keys.
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $name => $value) {
            $normalized[strtolower($name)] = is_array($value) ? $value : [$value];
        }
        return $normalized;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        $clone = clone $this;
        $clone->statusCode = $code;
        $clone->reasonPhrase = $reasonPhrase ?: (self::STATUS_PHRASES[$code] ?? '');
        return $clone;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version): self
    {
        $clone = clone $this;
        $clone->protocolVersion = $version;
        return $clone;
    }

    public function getHeaders(): array
    {
        return array_map(function ($values) {
            return $values[0] ?? '';
        }, $this->headers);
    }

    public function hasHeader($name): bool
    {
        return isset($this->headers[strtolower($name)]);
    }

    public function getHeader($name): array
    {
        $key = strtolower($name);
        return $this->headers[$key] ?? [];
    }

    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value): self
    {
        $clone = clone $this;
        $clone->headers[strtolower($name)] = is_array($value) ? $value : [$value];
        return $clone;
    }

    public function withAddedHeader($name, $value): self
    {
        $clone = clone $this;
        $key = strtolower($name);
        $existing = $clone->headers[$key] ?? [];
        $clone->headers[$key] = array_merge($existing, is_array($value) ? $value : [$value]);
        return $clone;
    }

    public function withoutHeader($name): self
    {
        $clone = clone $this;
        unset($clone->headers[strtolower($name)]);
        return $clone;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }
}
