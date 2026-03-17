<?php

declare(strict_types=1);

return [
    'maskulabs/inertia-yii' => [
        'rootView' => \dirname(__DIR__) . '/src/resources/app.php',
        'csrfCookie' => [
            'name' => 'XSRF-TOKEN',
            'secure' => false,
            'httpOnly' => false,
        ], // string/array/null
        'gateway' => [
            'enabled' => false,
            'devMode' => true,
            'url' => 'http://localhost:13714',
        ],
    ],
];
