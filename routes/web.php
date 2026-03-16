<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $v1Path = base_path('docs/API.md');
    $v2Path = base_path('docs/API-v2.md');

    $apiDocV1Markdown = File::exists($v1Path) ? File::get($v1Path) : '# v1 Documentation\n\nFile not found.';
    $apiDocV2Markdown = File::exists($v2Path) ? File::get($v2Path) : '# v2 Documentation\n\nFile not found.';

    return view('welcome', [
        'apiDocV1Markdown' => $apiDocV1Markdown,
        'apiDocV2Markdown' => $apiDocV2Markdown,
    ]);
});
