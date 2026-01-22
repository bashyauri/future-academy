<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('trial_ends_at')->nullable()->after('email');
            $table->boolean('is_subscribed')->default(false)->after('trial_ends_at');
            $table->string('subscription_type')->nullable()->after('is_subscribed'); // one_time or recurring
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'is_subscribed', 'subscription_type', 'subscription_ends_at']);
        });
    }
};
