<?php

declare(strict_types=1);

use MaskuLabs\InertiaPsr\Ssr\Response;
use Yiisoft\Html\Html;
use Yiisoft\Json\Json;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var array $page
 * @var Response $ssrResponse
 */

echo Html::script(
    Json::encode($page),
    [
        'data-page' => 'app',
        'type' => 'application/json',
    ],
);
echo Html::div(
    '',
    [
        'id' => 'app',
    ],
);
