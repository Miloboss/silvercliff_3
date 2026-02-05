<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/simple_web_ui/index.html');
});
