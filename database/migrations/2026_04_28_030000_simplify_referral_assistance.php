<?php

use App\Models\AssistanceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('application_assistance_recommendations', function (Blueprint $table) {
                $table->foreignId('assistance_subtype_id')->nullable()->change();
            });
        } else {
        Schema::table('application_assistance_recommendations', function (Blueprint $table) {
            $table->dropForeign('aar_subtype_fk');
            $table->foreignId('assistance_subtype_id')->nullable()->change();
            $table->foreign('assistance_subtype_id', 'aar_subtype_fk')
                ->references('id')
                ->on('assistance_subtypes')
                ->nullOnDelete();
        });
        }

        $referralType = AssistanceType::whereRaw('LOWER(name) = ?', ['referral to other services'])->first()
            ?? AssistanceType::whereRaw('LOWER(name) = ?', ['referral'])->first();

        if (! $referralType) {
            return;
        }

        $referralType->update(['name' => 'Referral']);

        $referralSubtypeIds = DB::table('assistance_subtypes')
            ->where('assistance_type_id', $referralType->id)
            ->pluck('id');

        if ($referralSubtypeIds->isEmpty()) {
            return;
        }

        DB::table('application_assistance_recommendations')
            ->where('assistance_type_id', $referralType->id)
            ->whereIn('assistance_subtype_id', $referralSubtypeIds)
            ->update([
                'assistance_subtype_id' => null,
                'assistance_detail_id' => null,
            ]);

        $referencedSubtypeIds = DB::table('applications')
            ->whereIn('assistance_subtype_id', $referralSubtypeIds)
            ->pluck('assistance_subtype_id')
            ->unique();

        DB::table('assistance_subtypes')
            ->whereIn('id', $referralSubtypeIds->diff($referencedSubtypeIds))
            ->delete();
    }

    public function down(): void
    {
        AssistanceType::whereRaw('LOWER(name) = ?', ['referral'])
            ->update(['name' => 'Referral to Other Services']);
    }
};
