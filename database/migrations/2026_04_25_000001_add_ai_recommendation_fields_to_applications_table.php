<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->text('ai_recommendation_summary')->nullable()->after('social_worker_assessment');
            $table->unsignedTinyInteger('ai_recommendation_confidence')->nullable()->after('ai_recommendation_summary');
            $table->string('ai_recommendation_source')->nullable()->after('ai_recommendation_confidence');
            $table->string('ai_recommendation_model')->nullable()->after('ai_recommendation_source');
            $table->timestamp('ai_recommendation_generated_at')->nullable()->after('ai_recommendation_model');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'ai_recommendation_summary',
                'ai_recommendation_confidence',
                'ai_recommendation_source',
                'ai_recommendation_model',
                'ai_recommendation_generated_at',
            ]);
        });
    }
};
