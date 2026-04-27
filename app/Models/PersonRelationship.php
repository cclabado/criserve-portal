<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonRelationship extends Model
{
    protected $fillable = [
        'person_id',
        'related_person_id',
        'relationship_id',
        'source_application_id',
        'is_confirmed',
    ];

    protected $casts = [
        'is_confirmed' => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function relatedPerson()
    {
        return $this->belongsTo(Person::class, 'related_person_id');
    }

    public function relationship()
    {
        return $this->belongsTo(Relationship::class);
    }

    public function sourceApplication()
    {
        return $this->belongsTo(Application::class, 'source_application_id');
    }
}
