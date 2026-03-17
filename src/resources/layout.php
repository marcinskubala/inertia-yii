<?php

declare(strict_types=1);

use MaskuLabs\InertiaYii\Assets\ViteAsset;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Assets\AssetManager;
use MaskuLabs\InertiaPsr\Ssr\Response;

/**
 * @var string $content
 * @var WebView $this
 * @var AssetManager $assetManager
 * @var ViteAsset $viteAsset
 * @var Response|null $ssrResponse
 */

$assetManager->registerCustomized(
    $viteAsset::class,
    $viteAsset->getConfiguration(),
);

$this->addCssFiles($assetManager->getCssFiles());
$this->addCssStrings($assetManager->getCssStrings());
$this->addJsFiles($assetManager->getJsFiles());
$this->addJsStrings($assetManager->getJsStrings());
$this->addJsVars($assetManager->getJsVars());

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= $this->getTitle() ? Html::tag('title', $this->getTitle()) : '' ?>
    <?php $this->head() ?>
    <?= $ssrResponse?->head ?? '' ?>
</head>
<body>
<?php $this->beginBody() ?>
    <?= $ssrResponse?->body ?? $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
