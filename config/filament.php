<?php

use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\ParentPanelProvider;
use App\Providers\Filament\StaffPanelProvider;
use App\Providers\Filament\StudentPanelProvider;
use App\Providers\Filament\TeacherPanelProvider;

return [
    'panel_providers' => [
        AdminPanelProvider::class,
        StaffPanelProvider::class,
        TeacherPanelProvider::class,
        ParentPanelProvider::class,
        StudentPanelProvider::class,
    ],

    'default_panel' => 'admin',
];
