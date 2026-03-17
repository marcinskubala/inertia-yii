<?php

declare(strict_types=1);

use MaskuLabs\InertiaPsr\Flash\FlashInterface;
use MaskuLabs\InertiaPsr\Response\ResponseFactoryInterface;
use MaskuLabs\InertiaPsr\Response\StreamFactoryInterface;
use MaskuLabs\InertiaPsr\Service\CustomPropResolver\CustomPropResolverInterface;
use MaskuLabs\InertiaPsr\Service\PropsResolver\PropsResolverInterface;
use MaskuLabs\InertiaPsr\Session\SessionInterface;
use MaskuLabs\InertiaYii\Flash\Flash;
use MaskuLabs\InertiaYii\Response\ResponseFactory;
use MaskuLabs\InertiaYii\Response\StreamFactory;
use MaskuLabs\InertiaYii\Service\CallableResolver;
use MaskuLabs\InertiaYii\Session\Session;
use MaskuLabs\InertiaPsr\Ssr\GatewayInterface;
use MaskuLabs\InertiaPsr\Ssr\HttpGateway;
use MaskuLabs\InertiaPsr\InertiaInterface;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\RequestProvider\RequestProviderInterface;
use MaskuLabs\InertiaYii\Inertia;
use Yiisoft\Cookies\Cookie;
use Yiisoft\Csrf\CsrfTokenInterface;
use MaskuLabs\InertiaPsr\Service\CallableResolver\CallableResolverInterface;
use MaskuLabs\InertiaPsr\Service\CustomPropResolver\CustomPropResolver;
use MaskuLabs\InertiaPsr\Service\FlashResolver\FlashResolver;
use MaskuLabs\InertiaPsr\Service\FlashResolver\FlashResolverInterface;
use MaskuLabs\InertiaPsr\Service\PageResolver\PageResolver;
use MaskuLabs\InertiaPsr\Service\PageResolver\PageResolverInterface;
use MaskuLabs\InertiaPsr\Service\PropsResolver\PropsResolver;

/* @var $params array */

return [
    StreamFactoryInterface::class => StreamFactory::class,
    ResponseFactoryInterface::class => ResponseFactory::class,

    SessionInterface::class => Session::class,
    FlashInterface::class => Flash::class,

    FlashResolverInterface::class => FlashResolver::class,
    PageResolverInterface::class => PageResolver::class,
    PropsResolverInterface::class => PropsResolver::class,
    CallableResolverInterface::class => CallableResolver::class,
    CustomPropResolverInterface::class => CustomPropResolver::class,

    GatewayInterface::class => [
        'class' => HttpGateway::class,
        '__construct()' => [
            'enabled' => $params['maskulabs/inertia-yii']['gateway']['enabled'],
            'devMode' => $params['maskulabs/inertia-yii']['gateway']['devMode'],
            'url' => $params['maskulabs/inertia-yii']['gateway']['url'],
        ],
    ],

    InertiaInterface::class => [
        'class' => Inertia::class,
        '__construct()' => [
            'request' => DynamicReference::to(
                static fn(RequestProviderInterface $requestProvider) => $requestProvider->get(),
            ),
            'csrfCookie' => \is_array($params['maskulabs/inertia-yii']['csrfCookie'])
                ? DynamicReference::to(
                    static fn(CsrfTokenInterface $csrfToken)
                        => new Cookie(...$params['maskulabs/inertia-yii']['csrfCookie'])->withRawValue($csrfToken->getValue()),
                )
                : $params['maskulabs/inertia-yii']['csrfCookie'],
        ],
        'setRootView()' => [
            $params['maskulabs/inertia-yii']['rootView'],
        ],
    ],

    //    InertiaInterface::class => static function (
    //        RequestProviderInterface $requestProvider,
    //        Psr\Http\Message\ResponseFactoryInterface $psrResponseFactory,
    //        ResponseFactoryInterface $responseFactory,
    //        SessionInterface $session,
    //        CallableResolverInterface $callableResolver,
    //        MaskuLabs\InertiaPsr\Flash\Flash $flash,
    //    ) use ($params) {
    //        return new Inertia(
    //            $requestProvider->get(),
    //            $psrResponseFactory,
    //            $responseFactory,
    //            $session,
    //            $callableResolver,
    //            $flash,
    //        );
    //    },
];
