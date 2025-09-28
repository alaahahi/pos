<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Transactions;
use App\Models\Wallet;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $wallets = Wallet::all();

        foreach ($wallets as $wallet) {
            // Create 3-5 random transactions for each wallet
            $transactionCount = rand(3, 5);
            
            for ($i = 0; $i < $transactionCount; $i++) {
                $types = ['deposit', 'withdrawal', 'transfer', 'payment'];
                $currencies = ['IQD', 'USD'];
                $type = $types[array_rand($types)];
                $currency = $currencies[array_rand($currencies)];
                
                // Generate amount based on currency
                $amount = $currency === 'IQD' ? rand(10000, 500000) : rand(10, 500);
                
                $descriptions = [
                    'deposit' => 'إيداع نقدي',
                    'withdrawal' => 'سحب نقدي',
                    'transfer' => 'تحويل مالي',
                    'payment' => 'دفع فاتورة'
                ];

                Transactions::create([
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'type' => $type,
                    'description' => $descriptions[$type],
                    'is_pay' => rand(0, 1),
                    'currency' => $currency,
                    'created' => now()->subDays(rand(1, 30)),
                    'discount' => rand(0, 10),
                    'details' => json_encode([
                        'transaction_id' => 'TXN' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                        'reference' => 'REF' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
                    ])
                ]);
            }
        }

        $this->command->info('تم إنشاء المعاملات المالية بنجاح!');
    }
}
