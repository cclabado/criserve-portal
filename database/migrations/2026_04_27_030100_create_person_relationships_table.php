<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('person_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('related_person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('relationship_id')->nullable()->constrained('relationships')->nullOnDelete();
            $table->foreignId('source_application_id')->nullable()->constrained('applications')->nullOnDelete();
            $table->boolean('is_confirmed')->default(true);
            $table->timestamps();

            $table->unique(['person_id', 'related_person_id', 'relationship_id', 'source_application_id'], 'person_relationship_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_relationships');
    }
};
