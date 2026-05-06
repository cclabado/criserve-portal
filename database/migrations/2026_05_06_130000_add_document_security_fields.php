<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('storage_disk')->nullable()->after('file_path');
            $table->string('mime_type')->nullable()->after('storage_disk');
            $table->unsignedBigInteger('file_size')->nullable()->after('mime_type');
            $table->string('file_hash', 64)->nullable()->after('file_size');
        });

        $documents = \App\Models\Document::query()->get();

        foreach ($documents as $document) {
            $sourceDisk = Storage::disk('public');
            $targetDisk = Storage::disk('local');

            if (filled($document->file_path) && $sourceDisk->exists($document->file_path)) {
                $contents = $sourceDisk->get($document->file_path);
                $targetDisk->put($document->file_path, $contents);
                $sourceDisk->delete($document->file_path);

                $absolutePath = $targetDisk->path($document->file_path);

                $document->update([
                    'storage_disk' => 'local',
                    'mime_type' => $targetDisk->mimeType($document->file_path) ?: null,
                    'file_size' => $targetDisk->size($document->file_path) ?: null,
                    'file_hash' => is_file($absolutePath) ? hash_file('sha256', $absolutePath) : null,
                ]);

                continue;
            }

            $document->update([
                'storage_disk' => $document->storage_disk ?: 'local',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'storage_disk',
                'mime_type',
                'file_size',
                'file_hash',
            ]);
        });
    }
};
