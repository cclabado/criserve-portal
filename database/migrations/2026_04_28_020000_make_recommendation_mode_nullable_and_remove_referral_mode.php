<?php

use App\Models\ModeOfAssistance;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_assistance_recommendations', function (Blueprint $table) {
            $table->dropForeign('aar_mode_fk');
            $table->foreignId('mode_of_assistance_id')->nullable()->change();
            $table->foreign('mode_of_assistance_id', 'aar_mode_fk')
                ->references('id')
                ->on('mode_of_assistances')
                ->nullOnDelete();
        });

        $referralModeId = ModeOfAssistance::whereRaw('LOWER(name) = ?', ['referral'])->value('id');

        if ($referralModeId) {
            DB::table('applications')
                ->where('mode_of_assistance_id', $referralModeId)
                ->update([
                    'mode_of_assistance_id' => null,
                    'mode_of_assistance' => null,
                ]);

            DB::table('application_assistance_recommendations')
                ->where('mode_of_assistance_id', $referralModeId)
                ->update(['mode_of_assistance_id' => null]);

            ModeOfAssistance::whereKey($referralModeId)->delete();
        }
    }

    public function down(): void
    {
        ModeOfAssistance::firstOrCreate(['name' => 'Referral']);
    }
};
