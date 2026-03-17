<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Response;

use MaskuLabs\InertiaPsr\Helper\RequestHelper;
use MaskuLabs\InertiaPsr\Response\StreamFactoryInterface;
use MaskuLabs\InertiaYii\Assets\ViteAsset;
use MaskuLabs\InertiaYii\View\CommonParametersInjection;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Yiisoft\DataResponse\DataStream\DataStream;
use Yiisoft\DataResponse\Formatter\HtmlFormatter;
use Yiisoft\DataResponse\Formatter\JsonFormatter;
use Yiisoft\View\Exception\ViewNotFoundException;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;
use Throwable;

final readonly class StreamFactory implements StreamFactoryInterface
{
    public function __construct(
        private WebViewRenderer $viewRenderer,
        private JsonFormatter $jsonFormatter,
        private HtmlFormatter $htmlFormatter,
        private ViteAsset $viteAsset,
    ) {}

    /**
     * @throws ViewNotFoundException
     * @throws Throwable
     */
    public function createStream(ServerRequestInterface $request, array $pageData, string $rootView, array $viewData): StreamInterface
    {
        if (RequestHelper::isInertia($request)) {
            return new DataStream($pageData, $this->jsonFormatter);
        }

        $viewData['ssrResponse'] ??= null;
        $viewData = $viewData + [
            'page' => $pageData,
            'viteAsset' => $this->viteAsset,
        ];

        $html = $this->viewRenderer
            ->withAddedInjections(
                new CommonParametersInjection($viewData),
            )
            ->renderAsString($rootView);

        return new DataStream($html, $this->htmlFormatter);
    }
}
