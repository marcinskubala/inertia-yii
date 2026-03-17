<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Session;

use MaskuLabs\InertiaPsr\Session\SessionInterface;
use Yiisoft\Session\SessionInterface as YiiSessionInterface;

final readonly class Session implements SessionInterface
{
    public function __construct(
        private YiiSessionInterface $session,
    ) {}

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value): void
    {
        $this->session->set($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function pull(string $key, mixed $default = null): mixed
    {
        return $this->session->pull($key, $default);
    }
}
