<?php

declare(strict_types=1);

namespace Fly\Http\Http;

use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * PSR-7 Stream implementation.
 */
class Stream implements StreamInterface
{
    /**
     * @var resource|null
     */
    private $resource;

    /**
     * @var bool
     */
    private bool $seekable;

    /**
     * @var bool
     */
    private bool $readable;

    /**
     * @var bool
     */
    private bool $writable;

    /**
     * @var array|null
     */
    private ?array $meta;

    /**
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        if (is_string($resource)) {
            $resource = fopen('php://temp', 'r+');
            fwrite($resource, $resource);
            rewind($resource);
        }

        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Invalid stream resource');
        }

        $this->resource = $resource;
        $this->meta = stream_get_meta_data($this->resource);
        $this->seekable = $this->meta['seekable'];
        $this->readable = isset($this->meta['mode']) && strstr($this->meta['mode'], 'r') !== false;
        $this->writable = isset($this->meta['mode']) && (strstr($this->meta['mode'], 'w') !== false || strstr($this->meta['mode'], '+') !== false);
    }

    public function __destruct()
    {
        $this->close();
    }

    public function close(): void
    {
        if ($this->resource !== null) {
            fclose($this->resource);
            $this->resource = null;
        }
    }

    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        $stats = fstat($this->resource);
        return $stats['size'] ?? null;
    }

    public function tell(): int
    {
        $this->assertResource();
        return ftell($this->resource);
    }

    public function eof(): bool
    {
        $this->assertResource();
        return feof($this->resource);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->assertResource();
        $this->assertSeekable();

        if (fseek($this->resource, $offset, $whence) === -1) {
            throw new RuntimeException('Unable to seek stream');
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write($string): int
    {
        $this->assertResource();
        $this->assertWritable();

        $result = fwrite($this->resource, $string);
        if ($result === false) {
            throw new RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read($length): string
    {
        $this->assertResource();
        $this->assertReadable();

        $result = fread($this->resource, $length);
        if ($result === false) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $result;
    }

    public function getContents(): string
    {
        $this->assertResource();
        $this->assertReadable();

        $result = stream_get_contents($this->resource);
        if ($result === false) {
            throw new RuntimeException('Unable to get stream contents');
        }

        return $result;
    }

    public function getMetadata($key = null)
    {
        if ($key === null) {
            return $this->meta;
        }

        return $this->meta[$key] ?? null;
    }

    public function __toString(): string
    {
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Assert that the resource exists.
     */
    private function assertResource(): void
    {
        if ($this->resource === null) {
            throw new RuntimeException('Stream is detached');
        }
    }

    /**
     * Assert that the stream is seekable.
     */
    private function assertSeekable(): void
    {
        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }
    }

    /**
     * Assert that the stream is readable.
     */
    private function assertReadable(): void
    {
        if (!$this->readable) {
            throw new RuntimeException('Stream is not readable');
        }
    }

    /**
     * Assert that the stream is writable.
     */
    private function assertWritable(): void
    {
        if (!$this->writable) {
            throw new RuntimeException('Stream is not writable');
        }
    }
}
