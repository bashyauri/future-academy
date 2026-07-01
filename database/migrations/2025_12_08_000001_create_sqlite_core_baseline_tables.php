<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->nullable();
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('phone')->nullable();
                $table->string('avatar')->nullable();
                $table->string('account_type')->default('student');
                $table->boolean('is_active')->default(true);
                $table->string('stream')->nullable();
                $table->json('selected_subjects')->nullable();
                $table->json('exam_types')->nullable();
                $table->boolean('has_completed_onboarding')->default(false);
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
                $table->timestamp('two_factor_confirmed_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->string('color')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('enrollments')) {
            Schema::create('enrollments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('enrolled_by')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('enrolled_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'is_active']);
                $table->index(['subject_id', 'is_active']);
            });
        }

        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
                $table->unique(['name', 'guard_name']);
            });
        }

        if (! Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table): void {
                $table->unsignedBigInteger('role_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        if (! Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->index(['model_id', 'model_type']);
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }

        if (! Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->primary(['permission_id', 'role_id']);
            });
        }

        if (! Schema::hasTable('exam_types')) {
            Schema::create('exam_types', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('lessons')) {
            Schema::create('lessons', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->string('title')->default('');
                $table->text('description')->nullable();
                $table->string('video_type')->nullable();
                $table->string('video_url')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->integer('order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('quizzes')) {
            Schema::create('quizzes', function (Blueprint $table): void {
                $table->id();
                $table->string('title')->default('');
                $table->text('description')->nullable();
                $table->string('type')->nullable();
                $table->string('status')->default('active');
                $table->boolean('is_active')->default(true);
                $table->integer('duration_minutes')->nullable();
                $table->integer('question_count')->default(0);
                $table->json('question_order')->nullable();
                $table->json('subject_ids')->nullable();
                $table->json('topic_ids')->nullable();
                $table->timestamp('available_from')->nullable();
                $table->timestamp('available_until')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('questions')) {
            Schema::create('questions', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('subject_id')->nullable();
                $table->unsignedBigInteger('topic_id')->nullable();
                $table->unsignedBigInteger('exam_type_id')->nullable();
                $table->text('question_text')->nullable();
                $table->boolean('is_active')->default(true);
                $table->string('status')->default('approved');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('options')) {
            Schema::create('options', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('question_id')->nullable();
                $table->text('option_text')->nullable();
                $table->boolean('is_correct')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('quiz_id')->nullable();
                $table->string('status')->default('in_progress');
                $table->json('question_order')->nullable();
                $table->integer('total_questions')->default(0);
                $table->integer('correct_answers')->default(0);
                $table->decimal('score_percentage', 5, 2)->default(0);
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('user_answers')) {
            Schema::create('user_answers', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('quiz_attempt_id')->nullable();
                $table->unsignedBigInteger('question_id')->nullable();
                $table->unsignedBigInteger('option_id')->nullable();
                $table->boolean('is_correct')->nullable();
                $table->integer('time_spent_seconds')->nullable();
                $table->timestamps();
            });
        }

        $defaultRoles = [
            'super-admin',
            'admin',
            'teacher',
            'uploader',
            'guardian',
            'school',
            'community',
            'student',
        ];

        foreach ($defaultRoles as $roleName) {
            $exists = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->exists();

            if (! $exists) {
                DB::table('roles')->insert([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            return;
        }

        Schema::dropIfExists('user_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('exam_types');
        Schema::dropIfExists('enrollments');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
    }
};
