<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Assets;

use JsonException;
use Override;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\Exception\InvalidConfigException;
use Yiisoft\Files\PathMatcher\PathMatcher;
use Yiisoft\Json\Json;
use Yiisoft\View\WebView;

use function is_string;

final class ViteAsset extends AssetBundle
{
    #[Override]
    public ?string $basePath = '@assets/build/spa/';
    #[Override]
    public ?string $baseUrl = '@assetsUrl/build/spa/';
    #[Override]
    public ?string $sourcePath = '@assetsSource/build/spa/';
    #[Override]
    public ?int $jsPosition = WebView::POSITION_HEAD;
    #[Override]
    public array $jsOptions = ['type' => 'module'];

    public bool $devMode = true;
    public string $manifestPath = '.vite/manifest.json';
    public string $appJsPath = 'frontend/app.js';
    public string $viteServerUrl = 'http://localhost:5173';

    public function __construct(
        public ?Aliases $aliases = null,
    ) {
        $this->publishOptions['filter'] = new PathMatcher()->doNotCheckFilesystem()->except('**/' . $this->manifestPath);
    }

    /**
     * @throws InvalidConfigException
     * @throws JsonException
     */
    public function getConfiguration(): array
    {
        $aliases = $this->getAliases();

        $config = [
            'manifestPath' => $this->manifestPath,
            'appJsPath' => $this->appJsPath,
            'viteServerUrl' => $this->viteServerUrl,
            'aliases' => $aliases,
        ];

        if ($this->devMode) {
            return [
                'js' => [
                    "{$this->viteServerUrl}/@vite/client",
                    "{$this->viteServerUrl}/{$this->appJsPath}",
                ],
                ...$config,
            ];
        }

        $manifestConfig = $this->getManifestEntry($aliases);

        return [
            'css' => $manifestConfig['css'] ?? [],
            'js' => [
                $manifestConfig['file'],
            ],
            ...$config,
        ];
    }

    /**
     * @throws InvalidConfigException
     */
    public function getVersion(): ?string
    {
        $manifestPath = $this->resolveManifestPath($this->getAliases());

        if (!is_file($manifestPath)) {
            return null;
        }

        $hash = hash_file(
            'xxh128',
            $manifestPath,
        );

        return $hash === false ? null : $hash;
    }

    /**
     * @throws InvalidConfigException
     */
    private function getAliases(): Aliases
    {
        if ($this->aliases === null) {
            throw new InvalidConfigException('Aliases must be set.');
        }

        return $this->aliases;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidConfigException
     * @throws JsonException
     */
    private function getManifestEntry(Aliases $aliases): array
    {
        $manifestPath = $this->resolveManifestPath($aliases);

        if (!is_file($manifestPath)) {
            throw new InvalidConfigException("Manifest file does not exist on path: \"$manifestPath\".");
        }

        $manifestContent = file_get_contents($manifestPath);

        if ($manifestContent === false) {
            throw new InvalidConfigException("Manifest file could not be read: \"$manifestPath\".");
        }

        /** @var array<string, array<string, mixed>> $manifest */
        $manifest = Json::decode($manifestContent);

        $manifestConfig = $manifest[$this->appJsPath] ?? null;

        if ($manifestConfig === null) {
            throw new InvalidConfigException("No \"{$this->appJsPath}\" entry found in manifest.");
        }

        if (!isset($manifestConfig['file']) || !is_string($manifestConfig['file']) || $manifestConfig['file'] === '') {
            throw new InvalidConfigException("Manifest entry for \"{$this->appJsPath}\" does not contain a valid \"file\" value.");
        }

        return $manifestConfig;
    }

    private function resolveManifestPath(Aliases $aliases): string
    {
        return $aliases->get($this->sourcePath . $this->manifestPath);
    }
}
