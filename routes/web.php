<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $path = base_path('docs/API.md');
    $apiDocMarkdown = File::exists($path) ? File::get($path) : '# API Documentation\n\nFile not found.';

    return view('welcome', ['apiDocMarkdown' => $apiDocMarkdown]);
});
