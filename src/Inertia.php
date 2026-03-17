<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii;

use BackedEnum;
use MaskuLabs\InertiaPsr\Flash\Flash;
use MaskuLabs\InertiaPsr\Property\ProvidesInertiaPropertiesInterface;
use MaskuLabs\InertiaPsr\Response\ResponseFactoryInterface;
use MaskuLabs\InertiaPsr\Service\CallableResolver\CallableResolverInterface;
use MaskuLabs\InertiaPsr\Session\SessionInterface;
use Psr\Http\Message\ResponseFactoryInterface as PsrResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use UnitEnum;
use Yiisoft\Arrays\ArrayableInterface;
use Yiisoft\Cookies\Cookie;
use Yiisoft\Http\Header;
use MaskuLabs\InertiaPsr\Response\ResponseInterface;
use Override;

final class Inertia extends \MaskuLabs\InertiaPsr\Inertia
{
    #[Override]
    protected string $rootView = __DIR__ . '/resources/app.php';

    public function __construct(
        ServerRequestInterface $request,
        PsrResponseFactoryInterface $psrResponseFactory,
        ResponseFactoryInterface $responseFactory,
        SessionInterface $session,
        CallableResolverInterface $callableResolver,
        Flash $flash,
        protected Cookie|string|null $csrfCookie = null,
    ) {
        parent::__construct(
            $request,
            $psrResponseFactory,
            $responseFactory,
            $session,
            $callableResolver,
            $flash,
        );
    }

    #[Override]
    public function render(BackedEnum|UnitEnum|string $component, ProvidesInertiaPropertiesInterface|ArrayableInterface|array $props = []): ResponseInterface
    {
        $response = parent::render($component, $props);

        if ($this->csrfCookie === null) {
            return $response;
        }

        return $response->withAddedHeader(Header::SET_COOKIE, (string) $this->csrfCookie);
    }
}
