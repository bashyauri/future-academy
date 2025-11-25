<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            // The subscriber (student OR guardian)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->enum('plan', ['monthly', 'termly', 'yearly']);
            $table->string('payment_method')->nullable();
            $table->integer('amount')->nullable();

            $table->date('starts_at');
            $table->date('ends_at');

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};