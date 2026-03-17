<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Service;

use MaskuLabs\InertiaPsr\Service\CallableResolver\CallableResolverInterface;
use Psr\Container\ContainerExceptionInterface;
use Yiisoft\Injector\Injector;
use ReflectionException;

use function is_callable;
use function is_string;

final readonly class CallableResolver implements CallableResolverInterface
{
    public function __construct(
        private Injector $injector,
    ) {}

    /**
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function resolve(mixed $value): mixed
    {
        return match (true) {
            !is_string($value) && is_callable($value) => $this->injector->invoke($value),
            default => $value,
        };
    }
}
