<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Response;

use MaskuLabs\InertiaPsr\Flash\Flash;
use MaskuLabs\InertiaPsr\Helper\RequestHelper;
use MaskuLabs\InertiaPsr\Response\Response;
use MaskuLabs\InertiaPsr\Response\ResponseFactoryInterface;
use MaskuLabs\InertiaPsr\Response\ResponseInterface;
use MaskuLabs\InertiaPsr\Response\StreamFactoryInterface;
use MaskuLabs\InertiaPsr\Service\PageResolver\Page;
use MaskuLabs\InertiaPsr\Service\PageResolver\PageResolverInterface;
use MaskuLabs\InertiaPsr\Support\Header;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;

final readonly class ResponseFactory implements ResponseFactoryInterface
{
    public function __construct(
        private JsonResponseFactory $jsonResponseFactory,
        private HtmlResponseFactory $htmlResponseFactory,
        private StreamFactoryInterface $streamFactory,
        private PageResolverInterface $pageResolver,
        private Flash $flash,
    ) {}

    public function createResponse(ServerRequestInterface $request, Page $page, string $rootView): ResponseInterface
    {
        $response = RequestHelper::isInertia($request)
            ? $this->jsonResponseFactory->createResponse()->withHeader(Header::Inertia->value, 'true')
            : $this->htmlResponseFactory->createResponse();

        return new Response(
            $request,
            $response,
            $this->streamFactory,
            $this->pageResolver,
            $this->flash,
            $page,
            $rootView,
        );
    }
}
