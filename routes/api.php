<?php

use Illuminate\Support\Facades\Route;

Route::get('/ping', function (): array {
    return [
        'status' => 'ok',
    ];
});
