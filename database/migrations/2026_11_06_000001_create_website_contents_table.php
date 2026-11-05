<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_contents', function (Blueprint $table) {
            $table->id();
            $table->string('type', 32)->index();
            $table->string('key')->nullable()->index();
            $table->string('title')->nullable();
            $table->string('slug')->nullable()->unique();
            $table->json('content')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['type', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_contents');
    }
};
