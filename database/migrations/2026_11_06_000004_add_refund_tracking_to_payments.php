<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider_refund_id')->nullable()->unique()->after('provider_payment_id');
            $table->string('refund_reason', 500)->nullable()->after('failure_description');
            $table->timestamp('refunded_at')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['provider_refund_id']);
            $table->dropColumn(['provider_refund_id', 'refund_reason', 'refunded_at']);
        });
    }
};
