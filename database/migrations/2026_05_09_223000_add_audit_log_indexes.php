<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->hasIndex('audit_logs', 'audit_logs_user_id_created_at_index')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index(['user_id', 'created_at']);
            });
        }

        if (! $this->hasIndex('audit_logs', 'audit_logs_created_at_index')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('audit_logs', 'audit_logs_user_id_created_at_index')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex(['user_id', 'created_at']);
            });
        }

        if ($this->hasIndex('audit_logs', 'audit_logs_created_at_index')) {
            Schema::table('audit_logs', function (Blueprint $table) {
                $table->dropIndex(['created_at']);
            });
        }
    }

    protected function hasIndex(string $table, string $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

        return $indexes !== [];
    }
};
