<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_subscribed', 'subscription_type', 'subscription_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_subscribed')->default(false)->after('trial_ends_at');
            $table->string('subscription_type')->nullable()->after('is_subscribed');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_type');
        });
    }
};
