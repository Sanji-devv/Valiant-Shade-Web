<?php

use App\Providers\AdminServiceProvider;
use App\Providers\AppServiceProvider;
use DemoVuexy\Providers\DemoVuexyServiceProvider;

return [
    AppServiceProvider::class,
    DemoVuexyServiceProvider::class,
    AdminServiceProvider::class,
];
