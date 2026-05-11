<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('gl_payment_status')
                ->default('unpaid')
                ->after('final_amount');
            $table->string('gl_soa_status')
                ->default('awaiting_upload')
                ->after('gl_payment_status');
            $table->text('gl_soa_review_notes')
                ->nullable()
                ->after('gl_soa_status');
            $table->foreignId('gl_soa_reviewed_by')
                ->nullable()
                ->after('gl_soa_review_notes')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('gl_soa_reviewed_at')
                ->nullable()
                ->after('gl_soa_reviewed_by');

            $table->index(['gl_payment_status', 'gl_soa_status']);
        });

        DB::table('applications')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('documents')
                    ->whereColumn('documents.application_id', 'applications.id')
                    ->where('documents.document_type', 'Updated Statement of Account');
            })
            ->update([
                'gl_soa_status' => 'pending_review',
            ]);
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['gl_payment_status', 'gl_soa_status']);
            $table->dropConstrainedForeignId('gl_soa_reviewed_by');
            $table->dropColumn([
                'gl_payment_status',
                'gl_soa_status',
                'gl_soa_review_notes',
                'gl_soa_reviewed_at',
            ]);
        });
    }
};
