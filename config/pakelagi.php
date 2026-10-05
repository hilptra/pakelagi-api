<?php

return [
    'admin' => [
        'name' => env('ADMIN_NAME', 'Admin'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    'images' => [
        'disk' => env('PRODUCT_IMAGE_DISK', 'public'),
        'max_per_upload' => 8,
        'max_per_product' => 10,
        'max_size_kb' => 2048,
        'max_dimension' => 4000,
        'full_size' => 1600,
        'thumbnail_size' => 480,
        'quality' => 80,
    ],

    'frontend' => [
        'revalidate_url' => env('FRONTEND_REVALIDATE_URL'),
        'revalidate_secret' => env('REVALIDATE_SECRET'),
        'revalidate_timeout' => 5,
    ],
];
