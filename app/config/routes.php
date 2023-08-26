<?php
/**
 * Define your routes here.
 */
return [
    // Note: Controller & Action suffixes are not needed.
    // So "Index.favicon" => "system/Index/IndexController::faviconAction()".

    '/' => 'Home',
    '/docs' => 'Docs',
    '/docs/:id' => 'Docs',

    // More..
    // '/user/:id' => ['GET' => 'User.show'],
    // '/user/:id' => ['GET' => function ($id) { ... }],
];
