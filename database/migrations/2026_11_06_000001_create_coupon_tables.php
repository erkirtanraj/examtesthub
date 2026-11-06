<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('discount_type', 16);
            $table->decimal('discount_value', 10, 2);
            $table->decimal('minimum_amount', 10, 2)->default(0);
            $table->decimal('maximum_discount', 10, 2)->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_user_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('applies_to', 20)->default('all');
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('test_series_id')->nullable()->constrained('test_series')->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['applies_to', 'course_id', 'test_series_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->string('coupon_code', 40)->nullable();
            $table->unsignedBigInteger('subtotal_paise')->nullable();
            $table->unsignedBigInteger('discount_paise')->default(0);
        });

        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('discount_paise');
            $table->timestamps();
            $table->unique(['coupon_id', 'payment_id']);
            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn(['coupon_code', 'subtotal_paise', 'discount_paise']);
        });
        Schema::dropIfExists('coupons');
    }
};
