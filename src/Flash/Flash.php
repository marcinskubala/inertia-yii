<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Flash;

use MaskuLabs\InertiaPsr\Flash\FlashInterface;
use Yiisoft\Session\Flash\FlashInterface as YiiFlashInterface;

final readonly class Flash implements FlashInterface
{
    public function __construct(
        private YiiFlashInterface $flash,
    ) {}

    /**
     * @inheritDoc
     */
    public function get(string $key): mixed
    {
        return $this->flash->get($key);
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value = true): void
    {
        $this->flash->set($key, $value);
    }

    /**
     * @inheritDoc
     */
    public function reflash(): void
    {
        $flashes = $this->flash->getAll();
        foreach ($flashes as $key => $value) {
            $this->flash->set($key, $value);
        }
    }
}
