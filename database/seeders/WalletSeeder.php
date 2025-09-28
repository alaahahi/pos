<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;
use App\Models\User;

class WalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users
        $users = User::all();

        foreach ($users as $user) {
            // Create wallet for each user if not exists
            Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance_dinar' => rand(100000, 1000000), // Random balance between 100K and 1M IQD
                    'balance' => rand(100, 1000), // Random balance between 100 and 1000 USD
                    'card' => 'CARD' . str_pad($user->id, 6, '0', STR_PAD_LEFT), // Generate card number
                ]
            );
        }

        $this->command->info('تم إنشاء محافظ المستخدمين بنجاح!');
    }
}
