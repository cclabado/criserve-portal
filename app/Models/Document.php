<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'application_id',
        'document_requirement_id',
        'document_type',
        'file_name',
        'file_path',
        'storage_disk',
        'mime_type',
        'file_size',
        'file_hash',
        'remarks',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function requirement()
    {
        return $this->belongsTo(AssistanceDocumentRequirement::class, 'document_requirement_id');
    }
}
