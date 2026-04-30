<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistance_document_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assistance_subtype_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assistance_detail_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(true);
            $table->decimal('applies_when_amount_exceeds', 12, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['assistance_subtype_id', 'assistance_detail_id', 'is_active'], 'adr_selection_active_idx');
        });

        $this->seedMedicalAssistanceRequirements();
    }

    public function down(): void
    {
        Schema::dropIfExists('assistance_document_requirements');
    }

    protected function seedMedicalAssistanceRequirements(): void
    {
        $medicalSubtype = DB::table('assistance_subtypes')
            ->where('name', 'Medical Assistance')
            ->first();

        if (! $medicalSubtype) {
            return;
        }

        DB::table('assistance_details')
            ->where('assistance_subtype_id', $medicalSubtype->id)
            ->where('name', 'Payment for Hospital Bill')
            ->update(['name' => 'Hospital Bill Payment']);

        DB::table('assistance_details')
            ->where('assistance_subtype_id', $medicalSubtype->id)
            ->where('name', 'Medicines / Assistive Devices')
            ->update(['name' => 'Medicines or Assistive Devices']);

        DB::table('assistance_details')
            ->where('assistance_subtype_id', $medicalSubtype->id)
            ->where('name', 'Medical Procedures')
            ->update(['name' => 'Laboratory, Medical Procedure, or Operation']);

        $detailIdsByName = DB::table('assistance_details')
            ->where('assistance_subtype_id', $medicalSubtype->id)
            ->pluck('id', 'name');

        $upsert = function (?int $detailId, string $name, string $description, bool $isRequired, ?float $threshold, int $sortOrder) use ($medicalSubtype) {
            DB::table('assistance_document_requirements')->updateOrInsert(
                [
                    'assistance_subtype_id' => $medicalSubtype->id,
                    'assistance_detail_id' => $detailId,
                    'name' => $name,
                ],
                [
                    'description' => $description,
                    'is_required' => $isRequired,
                    'applies_when_amount_exceeds' => $threshold,
                    'sort_order' => $sortOrder,
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        };

        $upsert(
            null,
            'Valid ID of the client or person to be interviewed',
            'Upload a valid government-issued or institution-issued ID for the client or for the person who will be interviewed.',
            true,
            null,
            10
        );

        $upsert(
            null,
            'Authorization Letter',
            'Upload only if someone else is acting on behalf of the client or beneficiary.',
            false,
            null,
            20
        );

        $hospitalBillDetailId = $detailIdsByName['Hospital Bill Payment'] ?? null;
        $medicineDetailId = $detailIdsByName['Medicines or Assistive Devices'] ?? null;
        $laboratoryDetailId = $detailIdsByName['Laboratory, Medical Procedure, or Operation'] ?? null;
        $chemotherapyDetailId = $detailIdsByName['Chemotherapy and Other Special Treatment'] ?? null;

        foreach (array_filter([$hospitalBillDetailId]) as $detailId) {
            $upsert(
                $detailId,
                'Medical Certificate / Clinical Abstract / Discharge Summary / Alagang Pinoy Tagubilin Form',
                'Must include the diagnosis, patient name, physician license number, and physician signature. Issue date must be within the last 3 months. Original or certified true copy only.',
                true,
                null,
                30
            );
            $upsert(
                $detailId,
                'Hospital Bill or Statement of Account / Certificate of Balance and Promissory Note',
                'Upload the outstanding hospital bill or statement of account with the billing clerk signature, or a certificate of balance and promissory note signed by the credit or collection officer or billing clerk.',
                true,
                null,
                40
            );
            $upsert(
                $detailId,
                'Social Case Study Report (SCSR) or Case Summary',
                'Upload the Social Case Study Report or case summary for the hospital bill payment request.',
                true,
                null,
                50
            );
        }

        foreach (array_filter([$medicineDetailId]) as $detailId) {
            $upsert(
                $detailId,
                'Medical Certificate / Clinical Abstract / Discharge Summary / Alagang Pinoy Tagubilin Form',
                'Must include the diagnosis, patient name, physician license number, and physician signature. Issue date must be within the last 3 months. Original or certified true copy only.',
                true,
                null,
                30
            );
            $upsert(
                $detailId,
                'Prescription',
                'Must include the issue date, physician name, physician license number, and physician signature. Issue date must be within the last 3 months.',
                true,
                null,
                40
            );
            $upsert(
                $detailId,
                'Quotation for Medicine or Assistive Device',
                'Required only when the amount requested exceeds P10,000.00.',
                true,
                10000,
                50
            );
            $upsert(
                $detailId,
                'Social Case Study Report (SCSR) or Case Summary',
                'Upload the Social Case Study Report or case summary when the amount requested exceeds P10,000.00.',
                true,
                10000,
                60
            );
        }

        foreach (array_filter([$laboratoryDetailId, $chemotherapyDetailId]) as $detailId) {
            $upsert(
                $detailId,
                'Medical Certificate / Clinical Abstract / Discharge Summary / Alagang Pinoy Tagubilin Form',
                'Must include the diagnosis, patient name, physician license number, and physician signature. Issue date must be within the last 3 months. Original or certified true copy only.',
                true,
                null,
                30
            );
            $upsert(
                $detailId,
                'Laboratory Request / Protocol / Doctor\'s Order',
                'Must include the physician name, physician license number, and physician signature.',
                true,
                null,
                40
            );
            $upsert(
                $detailId,
                'Laboratory Quotation',
                'Required only when the amount requested exceeds P10,000.00.',
                true,
                10000,
                50
            );
            $upsert(
                $detailId,
                'Social Case Study Report (SCSR) or Case Summary',
                'Upload the Social Case Study Report or case summary when the amount requested exceeds P10,000.00.',
                true,
                10000,
                60
            );
        }
    }
};
