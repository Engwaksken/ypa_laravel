<?php

return [
    'show_warnings' => false,
    'options' => [
        // Uploaded template markup must never fetch remote URLs or application secrets.
        'chroot' => array_values(array_filter([realpath(public_path()), realpath(storage_path('app/public')), env('LEGACY_UPLOADS_PATH') ? realpath(env('LEGACY_UPLOADS_PATH')) : false])),
        'enable_remote' => false,
        'enable_php' => false,
        'enable_javascript' => false,
        'enable_font_subsetting' => true,
        'default_font' => 'DejaVu Sans',
        'temp_dir' => storage_path('framework/cache'),
    ],
];
