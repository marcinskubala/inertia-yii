<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Cache;

use MaskuLabs\InertiaPsr\Support\Header;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\HttpMiddleware\HttpCache\CacheControlProvider\CacheControlProviderInterface;

final readonly class CacheControlProvider implements CacheControlProviderInterface
{
    public function get(ServerRequestInterface $request): ?string
    {
        if ($request->hasHeader(Header::Inertia->value)) {
            return 'no-store';
        }
        return null;
    }
}
