<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Tests\Middleware;

use HttpSoft\Message\Response;
use HttpSoft\Message\ServerRequest;
use MaskuLabs\InertiaPsr\InertiaInterface;
use MaskuLabs\InertiaPsr\Support\Header;
use MaskuLabs\InertiaYii\Exception\ValidationException;
use MaskuLabs\InertiaYii\Middleware\ValidationExceptionMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Input\Http\InputValidationException;
use Yiisoft\Session\Flash\FlashInterface;
use Yiisoft\Validator\Result;

#[CoversClass(ValidationExceptionMiddleware::class)]
final class ValidationExceptionMiddlewareTest extends TestCase
{
    private InertiaInterface&MockObject $inertia;
    private FlashInterface&MockObject $flash;
    private RequestHandlerInterface&MockObject $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inertia = $this->createMock(InertiaInterface::class);
        $this->flash = $this->createMock(FlashInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
    }

    public function test_process_passes_through_when_referer_is_missing(): void
    {
        $request = new ServerRequest();
        $response = new Response();

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $this->flash
            ->expects(self::never())
            ->method('set');

        $this->inertia
            ->expects(self::never())
            ->method('redirect');

        $middleware = new ValidationExceptionMiddleware(
            $this->inertia,
            $this->flash,
        );

        self::assertSame($response, $middleware->process($request, $this->handler));
    }

    public function test_process_passes_through_when_request_is_not_inertia(): void
    {
        $request = new ServerRequest(
            headers: [
                'Referer' => 'http://localhost/form',
            ],
        );
        $response = new Response();

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $this->flash
            ->expects(self::never())
            ->method('set');

        $this->inertia
            ->expects(self::never())
            ->method('redirect');

        $middleware = new ValidationExceptionMiddleware(
            $this->inertia,
            $this->flash,
        );

        self::assertSame($response, $middleware->process($request, $this->handler));
    }

    public function test_process_flashes_first_errors_indexed_by_path_and_redirects_for_validation_exception(): void
    {
        $request = $this->create_inertia_request('http://localhost/form');
        $redirect_response = new Response();
        $result = new Result()
            ->addError('Email is required.', [], ['user', 'email'])
            ->addError('Email must be valid.', [], ['user', 'email']);

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willThrowException(new ValidationException($result));

        $this->flash
            ->expects(self::once())
            ->method('set')
            ->with('errors', [
                'user.email' => 'Email is required.',
            ]);

        $this->inertia
            ->expects(self::once())
            ->method('redirect')
            ->with('http://localhost/form')
            ->willReturn($redirect_response);

        $middleware = new ValidationExceptionMiddleware(
            $this->inertia,
            $this->flash,
            withAllErrors: false,
            indexByPath: true,
        );

        self::assertSame($redirect_response, $middleware->process($request, $this->handler));
    }

    public function test_process_uses_header_error_bag_for_input_validation_exception(): void
    {
        $request = $this->create_inertia_request(
            referer: 'http://localhost/form',
            error_bag: 'login',
        );
        $redirect_response = new Response();
        $result = new Result()
            ->addError('Email is required.', [], ['user', 'email']);
        $exception = new InputValidationException($result);

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willThrowException($exception);

        $this->flash
            ->expects(self::once())
            ->method('set')
            ->with('errors', [
                'login' => [
                    'user.email' => 'Email is required.',
                ],
            ]);

        $this->inertia
            ->expects(self::once())
            ->method('redirect')
            ->with('http://localhost/form')
            ->willReturn($redirect_response);

        $middleware = new ValidationExceptionMiddleware(
            $this->inertia,
            $this->flash,
            withAllErrors: false,
            indexByPath: true,
        );

        self::assertSame($redirect_response, $middleware->process($request, $this->handler));
    }

    public function test_process_uses_exception_overrides_for_validation_exception(): void
    {
        $request = $this->create_inertia_request(
            referer: 'http://localhost/form',
            error_bag: 'default',
        );
        $redirect_response = new Response();
        $result = new Result()
            ->addError('First property error.', [], ['email'])
            ->addError('Second property error.', [], ['email'])
            ->addError('Ignored path error.', [], ['user', 'email']);

        $exception = new ValidationException(
            result: $result,
            withBag: 'profile',
            withAllErrors: true,
            indexByPath: false,
        );

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willThrowException($exception);

        $this->flash
            ->expects(self::once())
            ->method('set')
            ->with('errors', [
                'profile' => [
                    'email' => [
                        'First property error.',
                        'Second property error.',
                    ],
                    'user' => [
                        'Ignored path error.',
                    ],
                ],
            ]);

        $this->inertia
            ->expects(self::once())
            ->method('redirect')
            ->with('http://localhost/form')
            ->willReturn($redirect_response);

        $middleware = new ValidationExceptionMiddleware(
            $this->inertia,
            $this->flash,
            withAllErrors: false,
            indexByPath: true,
        );

        self::assertSame($redirect_response, $middleware->process($request, $this->handler));
    }

    public function test_process_uses_first_errors_indexed_by_property_when_configured(): void
    {
        $request = $this->create_inertia_request('http://localhost/form');
        $redirect_response = new Response();
        $result = new Result()
            ->addError('Email is required.', [], ['email'])
            ->addError('Email must be valid.', [], ['email']);

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willThrowException(new ValidationException($result));

        $this->flash
            ->expects(self::once())
            ->method('set')
            ->with('errors', [
                'email' => 'Email is required.',
            ]);

        $this->inertia
            ->expects(self::once())
            ->method('redirect')
            ->with('http://localhost/form')
            ->willReturn($redirect_response);

        $middleware = new ValidationExceptionMiddleware(
            $this->inertia,
            $this->flash,
            withAllErrors: false,
            indexByPath: false,
        );

        self::assertSame($redirect_response, $middleware->process($request, $this->handler));
    }

    public function test_process_uses_all_errors_indexed_by_path_when_configured(): void
    {
        $request = $this->create_inertia_request('http://localhost/form');
        $redirect_response = new Response();
        $result = new Result()
            ->addError('Email is required.', [], ['user', 'email'])
            ->addError('Email must be valid.', [], ['user', 'email']);

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willThrowException(new ValidationException($result));

        $this->flash
            ->expects(self::once())
            ->method('set')
            ->with('errors', [
                'user.email' => [
                    'Email is required.',
                    'Email must be valid.',
                ],
            ]);

        $this->inertia
            ->expects(self::once())
            ->method('redirect')
            ->with('http://localhost/form')
            ->willReturn($redirect_response);

        $middleware = new ValidationExceptionMiddleware(
            $this->inertia,
            $this->flash,
            withAllErrors: true,
            indexByPath: true,
        );

        self::assertSame($redirect_response, $middleware->process($request, $this->handler));
    }

    public function test_process_uses_all_errors_indexed_by_property_for_input_validation_exception_when_configured(): void
    {
        $request = $this->create_inertia_request('http://localhost/form');
        $redirect_response = new Response();
        $result = new Result()
            ->addError('Email is required.', [], ['email'])
            ->addError('Email must be valid.', [], ['email']);
        $exception = new InputValidationException($result);

        $this->handler
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willThrowException($exception);

        $this->flash
            ->expects(self::once())
            ->method('set')
            ->with('errors', [
                'email' => [
                    'Email is required.',
                    'Email must be valid.',
                ],
            ]);

        $this->inertia
            ->expects(self::once())
            ->method('redirect')
            ->with('http://localhost/form')
            ->willReturn($redirect_response);

        $middleware = new ValidationExceptionMiddleware(
            $this->inertia,
            $this->flash,
            withAllErrors: true,
            indexByPath: false,
        );

        self::assertSame($redirect_response, $middleware->process($request, $this->handler));
    }

    private function create_inertia_request(string $referer, ?string $error_bag = null): ServerRequest
    {
        $headers = [
            'Referer' => $referer,
            'X-Inertia' => 'true',
        ];

        if ($error_bag !== null) {
            $headers[Header::ErrorBag->value] = $error_bag;
        }

        return new ServerRequest(headers: $headers);
    }
}
