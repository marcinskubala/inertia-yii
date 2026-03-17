<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Middleware;

use MaskuLabs\InertiaPsr\InertiaInterface;
use MaskuLabs\InertiaPsr\Property\ProvidesInertiaPropertiesInterface;
use MaskuLabs\InertiaPsr\Service\CallableResolver\CallableResolverInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\Definitions\CallableDefinition;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableException;

final class ShareMiddleware implements MiddlewareInterface
{
    /**
     * @var list<array{key: ProvidesInertiaPropertiesInterface|ArrayableInterface|callable|array|string, value: mixed}>
     */
    private array $shares = [];

    /**
     * @var list<callable>
     */
    private array $definitions = [];

    public function __construct(
        protected CallableResolverInterface $callableResolver,
        protected ContainerInterface $container,
        protected InertiaInterface $inertia,
    ) {}

    /**
     * @inheritDoc
     *
     * @throws NotFoundExceptionInterface
     * @throws InvalidConfigException
     * @throws NotInstantiableException
     * @throws CircularReferenceException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        foreach ($this->shares as $share) {
            $key = $this->callableResolver->resolve($share['key']);
            $this->inertia->share($key, $share['value']);
        }

        foreach ($this->definitions as $definition) {
            /** @var string|array|ArrayableInterface|ProvidesInertiaPropertiesInterface $result */
            $result = new CallableDefinition($definition)->resolve($this->container);
            $this->inertia->share($result);
        }

        return $handler->handle($request);
    }

    public function with(ProvidesInertiaPropertiesInterface|ArrayableInterface|callable|array|string $key, mixed $value = null): self
    {
        $middleware = clone $this;
        $middleware->shares[] = [
            'key' => $key,
            'value' => $value,
        ];

        return $middleware;
    }

    public function withDefinitions(callable ...$definitions): self
    {
        $middleware = clone $this;

        foreach ($definitions as $definition) {
            $middleware->definitions[] = $definition;
        }

        return $middleware;
    }
}
