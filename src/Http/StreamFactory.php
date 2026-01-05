<?php

declare(strict_types=1);

namespace Fly\Http\Http;

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * PSR-17 HTTP Stream Factory implementation.
 */
class StreamFactory implements StreamFactoryInterface
{
    /**
     * Create a new stream from a string.
     */
    public function createStream(string $content = ''): StreamInterface
    {
        return new Stream($content);
    }

    /**
     * Create a stream from an existing file.
     */
    public function createStreamFromFile(string $file, string $mode = 'r'): StreamInterface
    {
        return new Stream(fopen($file, $mode));
    }

    /**
     * Create a new stream from an existing resource.
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        return new Stream($resource);
    }
}
