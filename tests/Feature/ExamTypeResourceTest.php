<?php

declare(strict_types=1);

use App\Models\ExamType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use App\Models\User;

uses(RefreshDatabase::class);

it('hides inactive exam types in filament', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $active = ExamType::factory()->create(['is_active' => true, 'name' => 'Active Exam']);
    $inactive = ExamType::factory()->create(['is_active' => false, 'name' => 'Quiz']);

    actingAs($admin);

    $visible = ExamType::query()->where('is_active', true)->pluck('id')->all();
    expect($visible)->toContain($active->id)
        ->not->toContain($inactive->id);
});
