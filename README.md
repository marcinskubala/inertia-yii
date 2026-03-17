# inertia-yii

Yii integration for [`maskulabs/inertia-psr`](https://github.com/maskulabs/inertia-psr).

`inertia-yii` provides the Yii-specific pieces needed to use **Inertia.js v3** in Yii applications, including rendering, middleware, validation error handling, session / flash adapters, and Vite integration.

Official Inertia.js documentation:

- <https://inertiajs.com/docs/v3/getting-started/index>

## Features

- Yii-specific `Inertia` implementation
- HTML and JSON Inertia responses
- Shared props middleware
- Validation exception middleware with flashed errors
- Yii session and flash adapters
- Yii response and stream factories
- Vite dev server and manifest integration

## Requirements

- PHP 8.5+
- `maskulabs/inertia-psr`
- Yii packages required by `composer.json`

## Installation

Install the package with Composer:
```bash
composer require maskulabs/inertia-yii
```
## Looking for a ready application template?

If you want a ready starting point instead of wiring everything manually, see [`maskulabs/inertia-app`](https://github.com/maskulabs/inertia-app).

It is an application template based on `yiisoft/app` with **Inertia.js integration already configured**.

## Quick start
```php
<?php

use MaskuLabs\InertiaPsr\InertiaInterface;
use MaskuLabs\InertiaPsr\Response\ResponseInterface;

final readonly class DashboardAction
{
    public function __construct(
        private InertiaInterface $inertia,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->inertia->render('Dashboard', [
            'stats' => [
                'users' => 120,
                'sales' => 54,
            ],
        ]);
    }
}
```
## Root view

The package ships with a default root view, but you can also configure your own.

Your root view should:

- render the serialized Inertia page data
- provide the frontend mount element
- include your frontend JavaScript and CSS assets

## Shared props

`ShareMiddleware` is intended to be configured in the Yii middleware stack.

Example:
```php
<?php

use MaskuLabs\InertiaPsr\Middleware\InertiaMiddleware;
use MaskuLabs\InertiaYii\Middleware\ShareMiddleware as InertiaShareMiddleware;

return [
    InertiaMiddleware::class,
    [
        'class' => InertiaShareMiddleware::class,
        'withDefinitions()' => [
            Reference::to(I18n::class),
            Reference::to(Layout::class),
        ],
    ],
];
```
## Validation errors

`ValidationExceptionMiddleware` catches validation exceptions and flashes formatted errors so they can be returned to the frontend after redirect.

Example:
```php
<?php

use MaskuLabs\InertiaYii\Exception\ValidationException;

if (!$formHydrator->populateFromPostAndValidate($loginForm, $request)) {
    throw new ValidationException($loginForm->getValidationResult());
}
```
## Vite integration

`ViteAsset` supports two common modes:

- **development mode** using the Vite dev server
- **production mode** using `.vite/manifest.json`

## License

MIT
