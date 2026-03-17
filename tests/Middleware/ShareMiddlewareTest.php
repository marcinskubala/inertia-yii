<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Tests\Middleware;

use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use MaskuLabs\InertiaPsr\InertiaInterface;
use MaskuLabs\InertiaPsr\Service\CallableResolver\CallableResolverInterface;
use MaskuLabs\InertiaYii\Middleware\ShareMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

use function is_string;

#[CoversClass(ShareMiddleware::class)]
final class ShareMiddlewareTest extends TestCase
{
    private CallableResolverInterface&MockObject $callable_resolver;
    private InertiaInterface&MockObject $inertia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->callable_resolver = $this->createMock(CallableResolverInterface::class);
        $this->inertia = $this->createMock(InertiaInterface::class);
    }

    public function test_process_passes_through_when_nothing_is_configured(): void
    {
        $request = new ServerRequest();
        $response = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->callable_resolver
            ->expects(self::never())
            ->method('resolve');

        $this->inertia
            ->expects(self::never())
            ->method('share');

        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $middleware = new ShareMiddleware(
            $this->callable_resolver,
            new class implements ContainerInterface {
                public function get(string $id): mixed
                {
                    throw new RuntimeException('Service not found.');
                }

                public function has(string $id): bool
                {
                    return false;
                }
            },
            $this->inertia,
        );

        self::assertSame($response, $middleware->process($request, $handler));
    }

    public function test_with_returns_cloned_instance_without_mutating_original(): void
    {
        $request = new ServerRequest();
        $original_response = new Response();
        $cloned_response = new Response();

        $original_handler = $this->createMock(RequestHandlerInterface::class);
        $cloned_handler = $this->createMock(RequestHandlerInterface::class);

        /** @var list<array{0: string, 1?: mixed, 2?: mixed}> $events */
        $events = [];

        $this->callable_resolver
            ->expects(self::once())
            ->method('resolve')
            ->with('shared_key')
            ->willReturn('resolved.shared_key');

        $this->inertia
            ->expects(self::once())
            ->method('share')
            ->with('resolved.shared_key', 'shared_value')
            ->willReturnCallback(function (mixed $key, mixed $value) use (&$events): InertiaInterface {
                $events[] = ['share', $key, $value];

                return $this->inertia;
            });

        $original_handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturnCallback(static function () use ($original_response, &$events): Response {
                $events[] = ['handle_original'];

                return $original_response;
            });

        $cloned_handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturnCallback(static function () use ($cloned_response, &$events): Response {
                $events[] = ['handle_cloned'];

                return $cloned_response;
            });

        $original = new ShareMiddleware(
            $this->callable_resolver,
            new class implements ContainerInterface {
                public function get(string $id): mixed
                {
                    throw new RuntimeException('Service not found.');
                }

                public function has(string $id): bool
                {
                    return false;
                }
            },
            $this->inertia,
        );

        $cloned = $original->with('shared_key', 'shared_value');

        self::assertNotSame($original, $cloned);
        self::assertSame($original_response, $original->process($request, $original_handler));
        self::assertSame($cloned_response, $cloned->process($request, $cloned_handler));

        self::assertSame([
            ['handle_original'],
            ['share', 'resolved.shared_key', 'shared_value'],
            ['handle_cloned'],
        ], $events);
    }

    public function test_process_resolves_and_shares_configured_values_before_handling_request(): void
    {
        $request = new ServerRequest();
        $response = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);

        /** @var list<array{0: string, 1?: mixed, 2?: mixed}> $events */
        $events = [];
        /** @var list<string|array<string, string>> $resolver_calls */
        $resolver_calls = [];

        $middleware = new ShareMiddleware(
            $this->callable_resolver,
            new class implements ContainerInterface {
                public function get(string $id): mixed
                {
                    throw new RuntimeException('Service not found.');
                }

                public function has(string $id): bool
                {
                    return false;
                }
            },
            $this->inertia,
        );

        $middleware = $middleware
            ->with('first_key', 'first_value')
            ->with(['second_key' => 'second_value']);

        $this->callable_resolver
            ->expects(self::exactly(2))
            ->method('resolve')
            ->willReturnCallback(function (mixed $value) use (&$resolver_calls): mixed {
                if (is_string($value)) {
                    $resolver_calls[] = $value;

                    if ($value === 'first_key') {
                        return 'resolved.first_key';
                    }

                    return $value;
                }

                /** @var array<string, string> $value */
                $resolver_calls[] = $value;

                return $value;
            });

        $this->inertia
            ->expects(self::exactly(2))
            ->method('share')
            ->willReturnCallback(function (mixed $key, mixed $value = null) use (&$events): InertiaInterface {
                $events[] = ['share', $key, $value];

                return $this->inertia;
            });

        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturnCallback(static function () use ($response, &$events): Response {
                $events[] = ['handle'];

                return $response;
            });

        self::assertSame($response, $middleware->process($request, $handler));

        self::assertSame([
            'first_key',
            ['second_key' => 'second_value'],
        ], $resolver_calls);

        self::assertSame([
            ['share', 'resolved.first_key', 'first_value'],
            ['share', ['second_key' => 'second_value'], null],
            ['handle'],
        ], $events);
    }

    public function test_process_resolves_and_shares_definition_results_before_handling_request(): void
    {
        $request = new ServerRequest();
        $response = new Response();
        $handler = $this->createMock(RequestHandlerInterface::class);

        /** @var list<array{0: string, 1?: mixed, 2?: mixed}> $events */
        $events = [];

        $middleware = new ShareMiddleware(
            $this->callable_resolver,
            new class implements ContainerInterface {
                public function get(string $id): mixed
                {
                    throw new RuntimeException('Service not found.');
                }

                public function has(string $id): bool
                {
                    return false;
                }
            },
            $this->inertia,
        );

        $middleware = $middleware->withDefinitions(
            static fn(): array => ['auth' => ['user' => 'john']],
            static fn(): string => 'csrf.token',
        );

        $this->callable_resolver
            ->expects(self::never())
            ->method('resolve');

        $this->inertia
            ->expects(self::exactly(2))
            ->method('share')
            ->willReturnCallback(function (mixed $key, mixed $value = null) use (&$events): InertiaInterface {
                $events[] = ['share', $key, $value];

                return $this->inertia;
            });

        $handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturnCallback(static function () use ($response, &$events): Response {
                $events[] = ['handle'];

                return $response;
            });

        self::assertSame($response, $middleware->process($request, $handler));

        self::assertSame([
            ['share', ['auth' => ['user' => 'john']], null],
            ['share', 'csrf.token', null],
            ['handle'],
        ], $events);
    }

    public function test_with_definitions_returns_cloned_instance_without_mutating_original(): void
    {
        $request = new ServerRequest();
        $original_response = new Response();
        $cloned_response = new Response();

        $original_handler = $this->createMock(RequestHandlerInterface::class);
        $cloned_handler = $this->createMock(RequestHandlerInterface::class);

        /** @var list<array{0: string, 1?: mixed, 2?: mixed}> $events */
        $events = [];

        $this->callable_resolver
            ->expects(self::never())
            ->method('resolve');

        $this->inertia
            ->expects(self::once())
            ->method('share')
            ->with(['shared' => 'definition'])
            ->willReturnCallback(function (mixed $key, mixed $value = null) use (&$events): InertiaInterface {
                $events[] = ['share', $key, $value];

                return $this->inertia;
            });

        $original_handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturnCallback(static function () use ($original_response, &$events): Response {
                $events[] = ['handle_original'];

                return $original_response;
            });

        $cloned_handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturnCallback(static function () use ($cloned_response, &$events): Response {
                $events[] = ['handle_cloned'];

                return $cloned_response;
            });

        $original = new ShareMiddleware(
            $this->callable_resolver,
            new class implements ContainerInterface {
                public function get(string $id): mixed
                {
                    throw new RuntimeException('Service not found.');
                }

                public function has(string $id): bool
                {
                    return false;
                }
            },
            $this->inertia,
        );

        $cloned = $original->withDefinitions(
            static fn(): array => ['shared' => 'definition'],
        );

        self::assertNotSame($original, $cloned);
        self::assertSame($original_response, $original->process($request, $original_handler));
        self::assertSame($cloned_response, $cloned->process($request, $cloned_handler));

        self::assertSame([
            ['handle_original'],
            ['share', ['shared' => 'definition'], null],
            ['handle_cloned'],
        ], $events);
    }
}
