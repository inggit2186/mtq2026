<?php

return [
    'pdf' => [
        'enabled' => true,
        'binary' => env('WKHTMLTOPDF_BINARY', 'C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe'),
        'timeout' => false,
        'options' => [
            'enable-local-file-access' => true,
            'load-error-handling' => 'ignore',
            'load-media-error-handling' => 'ignore',
            'no-stop-slow-scripts' => true,
            'enable-smart-shrinking' => true,
 ],
        'env' => [],
    ],

    'pdf_without_media' => [
        'enabled' => true,
        'binary' => env('WKHTMLTOPDF_BINARY', 'C:\Program Files\wkhtmltopdf\bin\wkhtmltopdf.exe'),
        'timeout' => false,
        'options' => [
            'enable-local-file-access' => true,
            'load-error-handling' => 'ignore',
            'load-media-error-handling' => 'ignore',
            'no-stop-slow-scripts' => true,
            'enable-smart-shrinking' => true,
        ],
        'env' => [],
    ],
];
