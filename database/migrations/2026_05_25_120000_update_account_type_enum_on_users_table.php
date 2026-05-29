<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY account_type ENUM('super-admin','admin','teacher','uploader','guardian','school','community','student') NOT NULL DEFAULT 'student'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY account_type ENUM('super-admin','admin','teacher','uploader','guardian','student') NOT NULL DEFAULT 'student'");
    }
};
