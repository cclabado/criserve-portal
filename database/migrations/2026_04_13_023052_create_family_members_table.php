<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Schema::create('family_members', function (Blueprint $table) {
        //     $table->id();

        //     $table->foreignId('application_id')->constrained()->cascadeOnDelete();

        //     // NAME (PSA FORMAT)
        //     $table->string('last_name');
        //     $table->string('first_name');
        //     $table->string('middle_name')->nullable();
        //     $table->string('extension_name')->nullable();

        //     $table->string('relationship');
        //     $table->date('birthdate')->nullable();
        //     $table->string('id_number')->nullable();

        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
};
