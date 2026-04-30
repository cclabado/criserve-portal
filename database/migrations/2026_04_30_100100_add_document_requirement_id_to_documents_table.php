<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'document_requirement_id')) {
                $table->foreignId('document_requirement_id')
                    ->nullable()
                    ->after('application_id')
                    ->constrained('assistance_document_requirements')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'document_requirement_id')) {
                $table->dropConstrainedForeignId('document_requirement_id');
            }
        });
    }
};
