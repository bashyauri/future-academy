<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\StaffPanelProvider;
use App\Providers\FortifyServiceProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    StaffPanelProvider::class,
    FortifyServiceProvider::class,
];
