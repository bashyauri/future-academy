<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('subscription_code')->nullable()->after('reference');
            $table->string('plan_code')->nullable()->after('plan');
            $table->timestamp('next_billing_date')->nullable()->after('ends_at');
            $table->timestamp('cancelled_at')->nullable()->after('next_billing_date');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['subscription_code', 'plan_code', 'next_billing_date', 'cancelled_at']);
        });
    }
};
