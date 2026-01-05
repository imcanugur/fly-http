<?php

declare(strict_types=1);

namespace Fly\Http\Http;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-17 HTTP Response Factory implementation.
 */
class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create a new response.
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, [], null, '1.1', $reasonPhrase);
    }
}
