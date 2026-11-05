<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profile_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('allow_profile_updates')->default(true);
            $table->boolean('allow_name_edit')->default(true);
            $table->boolean('allow_email_edit')->default(true);
            $table->boolean('allow_language_edit')->default(true);
            $table->boolean('allow_password_change')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profile_settings');
    }
};
