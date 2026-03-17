<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Tests\Service;

use MaskuLabs\InertiaPsr\Flash\FlashInterface;
use MaskuLabs\InertiaYii\Service\CallableResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Yiisoft\Injector\Injector;
use RuntimeException;

#[CoversClass(CallableResolver::class)]
final class CallableResolverTest extends TestCase
{
    public function test_resolve_returns_string_without_changes(): void
    {
        $resolver = new CallableResolver(new Injector());

        self::assertSame('plain-string', $resolver->resolve('plain-string'));
    }

    public function test_resolve_returns_array_without_changes(): void
    {
        $resolver = new CallableResolver(new Injector());
        $value = ['key' => 'value'];

        self::assertSame($value, $resolver->resolve($value));
    }

    public function test_resolve_invokes_closure(): void
    {
        $resolver = new CallableResolver(new Injector());
        $called = false;

        $value = static function () use (&$called): string {
            $called = true;

            return 'resolved-value';
        };

        self::assertSame('resolved-value', $resolver->resolve($value));
        self::assertTrue($called);
    }

    public function test_resolve_invokes_closure_with_dependency_injection(): void
    {
        $flash = new class implements FlashInterface {
            public function get(string $key): mixed
            {
                return null;
            }

            public function set(string $key, mixed $value = true): void {}

            public function reflash(): void {}
        };

        $container = new readonly class ($flash) implements ContainerInterface {
            public function __construct(
                private FlashInterface $flash,
            ) {}

            public function get(string $id): mixed
            {
                if ($id === FlashInterface::class) {
                    return $this->flash;
                }

                throw new class ('Service not found.') extends RuntimeException {};
            }

            public function has(string $id): bool
            {
                return $id === FlashInterface::class;
            }
        };

        $resolver = new CallableResolver(new Injector($container));

        $value = static function (FlashInterface $flash): FlashInterface {
            return $flash;
        };

        self::assertSame($flash, $resolver->resolve($value));
    }

    public function test_resolve_invokes_invokable_object(): void
    {
        $resolver = new CallableResolver(new Injector());

        $value = new class {
            public bool $called = false;

            public function __invoke(): string
            {
                $this->called = true;

                return 'resolved-from-invokable-object';
            }
        };

        self::assertSame('resolved-from-invokable-object', $resolver->resolve($value));
        self::assertTrue($value->called);
    }

    public function test_resolve_does_not_invoke_callable_string(): void
    {
        $resolver = new CallableResolver(new Injector());

        self::assertSame('trim', $resolver->resolve('trim'));
    }

    public function test_resolve_invokes_callable_array(): void
    {
        $resolver = new CallableResolver(new Injector());

        $callable = [
            new class {
                public function resolve(): string
                {
                    return 'resolved-from-array-callable';
                }
            },
            'resolve',
        ];

        self::assertSame('resolved-from-array-callable', $resolver->resolve($callable));
    }
}
