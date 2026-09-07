<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;

abstract class Controller implements HasMiddleware
{
    /**
     * Every controller must declare its middleware stack statically.
     * Laravel 12 removed the constructor-based $this->middleware() API.
     */
    abstract public static function middleware(): array;
}