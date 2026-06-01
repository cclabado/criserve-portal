<?php

namespace Database\Seeders;

use App\Models\Bank;
use App\Models\ServiceProviderBankAccount;
use Illuminate\Database\Seeder;

class PhilippineBankSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/philippine-banks.json');

        if (! is_file($path)) {
            $this->command?->warn('Philippine bank seed file not found: '.$path);

            return;
        }

        $records = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        foreach ($records as $record) {
            Bank::query()->updateOrCreate(
                ['name' => trim((string) ($record['name'] ?? ''))],
                [
                    'category' => filled($record['category'] ?? null) ? trim((string) $record['category']) : null,
                    'is_active' => true,
                ]
            );
        }

        ServiceProviderBankAccount::query()
            ->whereNull('bank_id')
            ->whereNotNull('bank_name')
            ->get()
            ->each(function (ServiceProviderBankAccount $bankAccount) {
                $bank = Bank::query()
                    ->whereRaw('LOWER(name) = ?', [strtolower((string) $bankAccount->bank_name)])
                    ->first();

                if ($bank) {
                    $bankAccount->update([
                        'bank_id' => $bank->id,
                        'bank_name' => $bank->name,
                    ]);
                }
            });
    }
}
