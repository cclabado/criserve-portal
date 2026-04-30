<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    public const CATEGORY_OPTIONS = [
        'Chemo',
        'Device',
        'Diagnostic Center',
        'Dialysis',
        'Dialysis Center',
        'Funeral',
        'Hearing',
        'Hospital',
        'Implant',
        'Medical Devices',
        'Optha',
        'Pharmacy',
        'Procedure',
        'Prosthesis',
        'Prosthesis/Orthotics',
        'Theraphy',
    ];

    protected $fillable = [
        'name',
        'addressee',
        'categories',
        'contact_number',
        'address',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'categories' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accounts()
    {
        return $this->hasMany(User::class)->orderBy('name');
    }

    public function applications()
    {
        return $this->hasMany(Application::class)->orderByDesc('updated_at');
    }

    public function categoryList(): array
    {
        return collect($this->categories ?? [])
            ->filter(fn ($category) => in_array($category, self::CATEGORY_OPTIONS, true))
            ->values()
            ->all();
    }

    public static function inferRelevantCategories(?string $subtypeName = null, ?string $detailName = null): array
    {
        $sources = array_filter([
            self::normalizeCategoryText($detailName),
            self::normalizeCategoryText($subtypeName),
        ]);

        if ($sources === []) {
            return [];
        }

        $matches = collect();

        foreach ($sources as $source) {
            foreach (self::categoryMatchers() as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if ($keyword !== '' && str_contains($source, $keyword)) {
                        $matches->push($category);
                        break;
                    }
                }
            }

            if ($matches->isNotEmpty()) {
                break;
            }
        }

        return $matches->unique()->values()->all();
    }

    protected static function categoryMatchers(): array
    {
        return [
            'Chemo' => ['chemo', 'chemotherapy'],
            'Device' => ['device', 'assistive device'],
            'Diagnostic Center' => ['diagnostic center', 'diagnostic', 'laboratory', 'laboratory request', 'lab'],
            'Dialysis' => ['dialysis'],
            'Dialysis Center' => ['dialysis center'],
            'Funeral' => ['funeral', 'cadaver', 'remains'],
            'Hearing' => ['hearing'],
            'Hospital' => ['hospital', 'admitted', 'admission'],
            'Implant' => ['implant'],
            'Medical Devices' => ['medical device', 'assistive device'],
            'Optha' => ['optha', 'ophtha', 'eye', 'vision'],
            'Pharmacy' => ['pharmacy', 'medicine', 'medicines', 'prescription', 'drug'],
            'Procedure' => ['procedure', 'operation', 'surgery'],
            'Prosthesis' => ['prosthesis'],
            'Prosthesis/Orthotics' => ['prosthesis', 'orthotic', 'orthotics'],
            'Theraphy' => ['therapy', 'theraphy', 'rehab', 'rehabilitation'],
        ];
    }

    protected static function normalizeCategoryText(?string $value): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', ' ', trim((string) $value)));
    }
}
