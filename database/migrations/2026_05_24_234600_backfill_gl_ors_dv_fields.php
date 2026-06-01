<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('applications')
            ->whereNotNull('gl_finance_fund_source')
            ->whereNull('gl_ors_number')
            ->orderBy('id')
            ->get()
            ->each(function ($application) {
                $fundSource = trim((string) ($application->gl_finance_fund_source ?? ''));
                $date = $application->gl_budget_reviewed_at
                    ?: $application->updated_at
                    ?: now();
                $timestamp = \Carbon\Carbon::parse($date);

                DB::table('applications')
                    ->where('id', $application->id)
                    ->update([
                        'gl_fund_cluster' => $application->gl_fund_cluster ?: 'Regular Agency Fund',
                        'gl_responsibility_center' => $application->gl_responsibility_center ?: 'PMB-CID',
                        'gl_mfo_pap' => $application->gl_mfo_pap ?: (str_contains(strtoupper($fundSource), 'AKAP') ? '320104200006000' : '320104100001000'),
                        'gl_mode_of_payment' => $application->gl_mode_of_payment ?: 'ADA',
                        'gl_ors_number' => sprintf('02-01101101-%s-%05d', $timestamp->format('Y-m'), (int) $application->id),
                        'gl_ors_date' => $application->gl_ors_date ?: $timestamp->toDateString(),
                        'gl_dv_number' => sprintf('DV-%s-%05d', $timestamp->format('Y-m'), (int) $application->id),
                        'gl_dv_date' => $application->gl_dv_date ?: $timestamp->toDateString(),
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('applications')
            ->whereNotNull('gl_ors_number')
            ->whereNotNull('gl_dv_number')
            ->update([
                'gl_fund_cluster' => null,
                'gl_responsibility_center' => null,
                'gl_mfo_pap' => null,
                'gl_mode_of_payment' => null,
                'gl_ors_number' => null,
                'gl_ors_date' => null,
                'gl_dv_number' => null,
                'gl_dv_date' => null,
            ]);
    }
};
