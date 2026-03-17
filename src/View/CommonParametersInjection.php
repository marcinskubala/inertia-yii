<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\View;

use Yiisoft\Yii\View\Renderer\CommonParametersInjectionInterface;

final readonly class CommonParametersInjection implements CommonParametersInjectionInterface
{
    public function __construct(
        private array $params,
    ) {}

    /**
     * @inheritDoc
     */
    public function getCommonParameters(): array
    {
        return $this->params;
    }
}
