<?php

declare(strict_types=1);

namespace MaskuLabs\InertiaYii\Tests\Asset;

use MaskuLabs\InertiaYii\Assets\ViteAsset;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Assets\Exception\InvalidConfigException;

use function bin2hex;
use function dirname;
use function file_put_contents;
use function hash_file;
use function is_dir;
use function json_encode;
use function mkdir;
use function random_bytes;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function unlink;

use const JSON_THROW_ON_ERROR;

#[CoversClass(ViteAsset::class)]
final class ViteAssetTest extends TestCase
{
    private string $temp_directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temp_directory = sys_get_temp_dir() . '/inertia-yii-vite-asset-test-' . bin2hex(random_bytes(8));

        $created = mkdir($this->temp_directory, 0777, true);

        if ($created === false && !is_dir($this->temp_directory)) {
            self::fail("Could not create temporary directory: {$this->temp_directory}");
        }
    }

    protected function tearDown(): void
    {
        $this->remove_directory($this->temp_directory);

        parent::tearDown();
    }

    public function test_get_configuration_returns_dev_configuration_when_dev_mode_is_enabled(): void
    {
        $aliases = $this->create_aliases();
        $asset = new ViteAsset($aliases);
        $asset->devMode = true;

        /** @var array<string, mixed> $configuration */
        $configuration = $asset->getConfiguration();

        self::assertSame([
            'js' => [
                'http://localhost:5173/@vite/client',
                'http://localhost:5173/frontend/app.js',
            ],
            'manifestPath' => '.vite/manifest.json',
            'appJsPath' => 'frontend/app.js',
            'viteServerUrl' => 'http://localhost:5173',
            'aliases' => $aliases,
        ], $configuration);
    }

    public function test_get_configuration_throws_exception_when_aliases_are_missing(): void
    {
        $asset = new ViteAsset();
        $asset->devMode = true;

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Aliases must be set.');

        $asset->getConfiguration();
    }

    public function test_get_configuration_returns_production_configuration(): void
    {
        $this->create_manifest([
            'frontend/app.js' => [
                'file' => 'assets/app.123.js',
                'css' => [
                    'assets/app.123.css',
                ],
            ],
        ]);

        $aliases = $this->create_aliases();
        $asset = new ViteAsset($aliases);
        $asset->devMode = false;

        /** @var array<string, mixed> $configuration */
        $configuration = $asset->getConfiguration();

        self::assertSame([
            'css' => [
                'assets/app.123.css',
            ],
            'js' => [
                'assets/app.123.js',
            ],
            'manifestPath' => '.vite/manifest.json',
            'appJsPath' => 'frontend/app.js',
            'viteServerUrl' => 'http://localhost:5173',
            'aliases' => $aliases,
        ], $configuration);
    }

    public function test_get_configuration_returns_empty_css_when_manifest_entry_does_not_contain_css(): void
    {
        $this->create_manifest([
            'frontend/app.js' => [
                'file' => 'assets/app.123.js',
            ],
        ]);

        $asset = new ViteAsset($this->create_aliases());
        $asset->devMode = false;

        /** @var array<string, mixed> $configuration */
        $configuration = $asset->getConfiguration();

        self::assertSame([], $configuration['css']);
        self::assertSame(['assets/app.123.js'], $configuration['js']);
    }

    public function test_get_configuration_throws_exception_when_manifest_file_does_not_exist(): void
    {
        $asset = new ViteAsset($this->create_aliases());
        $asset->devMode = false;

        $expected_path = $this->temp_directory . '/build/spa/.vite/manifest.json';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage("Manifest file does not exist on path: \"$expected_path\".");

        $asset->getConfiguration();
    }

    public function test_get_configuration_throws_exception_when_manifest_entry_does_not_exist(): void
    {
        $this->create_manifest([
            'frontend/other.js' => [
                'file' => 'assets/other.123.js',
            ],
        ]);

        $asset = new ViteAsset($this->create_aliases());
        $asset->devMode = false;

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No "frontend/app.js" entry found in manifest.');

        $asset->getConfiguration();
    }

    public function test_get_configuration_throws_exception_when_manifest_entry_does_not_contain_valid_file(): void
    {
        $this->create_manifest([
            'frontend/app.js' => [
                'file' => '',
            ],
        ]);

        $asset = new ViteAsset($this->create_aliases());
        $asset->devMode = false;

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Manifest entry for "frontend/app.js" does not contain a valid "file" value.');

        $asset->getConfiguration();
    }

    public function test_get_version_returns_null_when_manifest_does_not_exist(): void
    {
        $asset = new ViteAsset($this->create_aliases());

        self::assertNull($asset->getVersion());
    }

    public function test_get_version_returns_hash_when_manifest_exists(): void
    {
        $manifest_path = $this->create_manifest([
            'frontend/app.js' => [
                'file' => 'assets/app.123.js',
            ],
        ]);

        $asset = new ViteAsset($this->create_aliases());

        self::assertSame(hash_file('xxh128', $manifest_path), $asset->getVersion());
    }

    public function test_get_version_throws_exception_when_aliases_are_missing(): void
    {
        $asset = new ViteAsset();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Aliases must be set.');

        $asset->getVersion();
    }

    private function create_aliases(): Aliases
    {
        return new Aliases([
            '@assetsSource' => $this->temp_directory,
        ]);
    }

    /**
     * @param array<string, array<string, mixed>> $manifest
     */
    private function create_manifest(array $manifest): string
    {
        $manifest_path = $this->temp_directory . '/build/spa/.vite/manifest.json';
        $manifest_directory = dirname($manifest_path);

        if (!is_dir($manifest_directory)) {
            $created = mkdir($manifest_directory, 0777, true);

            if ($created === false && !is_dir($manifest_directory)) {
                self::fail("Could not create manifest directory: {$manifest_directory}");
            }
        }

        $manifest_json = json_encode($manifest, JSON_THROW_ON_ERROR);
        $written = file_put_contents($manifest_path, $manifest_json);

        if ($written === false) {
            self::fail("Could not write manifest file: {$manifest_path}");
        }

        return $manifest_path;
    }

    private function remove_directory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            self::fail("Could not read directory contents: {$directory}");
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;

            if (is_dir($path)) {
                $this->remove_directory($path);
                continue;
            }

            if (!unlink($path)) {
                self::fail("Could not remove file: {$path}");
            }
        }

        if (!rmdir($directory)) {
            self::fail("Could not remove directory: {$directory}");
        }
    }
}
