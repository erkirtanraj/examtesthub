<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_series_enrollment_id')->constrained('test_series_enrollments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 24)->default('razorpay');
            $table->string('provider_order_id')->nullable()->unique();
            $table->string('provider_payment_id')->nullable()->unique();
            $table->string('receipt', 40)->unique();
            $table->unsignedBigInteger('amount_paise');
            $table->char('currency', 3)->default('INR');
            $table->string('status', 24)->default('pending')->index();
            $table->string('failure_code')->nullable();
            $table->string('failure_description', 500)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['test_series_enrollment_id', 'status']);
        });

        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider_event_id', 120)->unique();
            $table->string('event_type', 100);
            $table->char('payload_hash', 64);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payments');
    }
};
