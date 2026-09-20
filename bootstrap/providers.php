<?php

use App\Providers\AdminServiceProvider;
use App\Providers\AppServiceProvider;
use App\ValiantShade\Providers\ValiantShadeServiceProvider;
use DemoVuexy\Providers\DemoVuexyServiceProvider;

return [
    AppServiceProvider::class,
    DemoVuexyServiceProvider::class,
    AdminServiceProvider::class,
    ValiantShadeServiceProvider::class,
];

