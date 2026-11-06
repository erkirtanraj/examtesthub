<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupon_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('discount_paise');
            $table->string('status', 16)->default('reserved')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();
            $table->index(['coupon_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_reservations');
    }
};
