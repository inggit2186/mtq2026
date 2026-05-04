<?php

$compiledPath = env('VIEW_COMPILED_PATH');

$isAbsolutePath = is_string($compiledPath)
    && preg_match('/^(?:[A-Za-z]:[\\\\\\/]|[\\\\\\/]{2})/', $compiledPath) === 1;

return [
    'paths' => [
        resource_path('views'),
    ],
    'compiled' => $compiledPath
        ? ($isAbsolutePath ? $compiledPath : base_path($compiledPath))
        : (realpath(base_path('bootstrap/cache/views')) ?: base_path('bootstrap/cache/views')),
];
