<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referral_institutions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('addressee')->nullable();
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_institutions');
    }
};
