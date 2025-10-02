<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;

class MainBoxUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // البحث عن نوع المستخدم المالي أو إنشاؤه إذا لم يكن موجوداً
        $userAccount = UserType::where('name', 'account')->first();
        
        if (!$userAccount) {
            $userAccount = UserType::create([
                'name' => 'account',
                'description' => 'نوع المستخدم المالي للصندوق الأساسي'
            ]);
            $this->command->info('تم إنشاء نوع المستخدم المالي: ' . $userAccount->name);
        }

        // التحقق من وجود المستخدم الأساسي للصندوق
        $existingMainBox = User::where('email', 'mainBox@account.com')->first();
        
        if ($existingMainBox) {
            $this->command->info('المستخدم الأساسي للصندوق موجود بالفعل: ' . $existingMainBox->name);
            return;
        }

        // إنشاء المستخدم الأساسي للصندوق
        $mainBoxUser = User::create([
            'name' => 'الصندوق الرئيسي',
            'email' => 'mainBox@account.com',
            'password' => bcrypt('mainbox123'), // كلمة مرور مؤقتة
            'type_id' => $userAccount->id,
            'is_active' => true,
        ]);

        // إنشاء المحفظة للمستخدم الأساسي
        $wallet = Wallet::create([
            'user_id' => $mainBoxUser->id,
            'balance' => 0,
            'updated_at' => now(),
        ]);

        $this->command->info('تم إنشاء المستخدم الأساسي للصندوق بنجاح:');
        $this->command->line('- اسم المستخدم: ' . $mainBoxUser->name);
        $this->command->line('- الايميل: ' . $mainBoxUser->email);
        $this->command->line('- كلمة المرور: mainbox123');
        $this->command->line('- معرّف المحفظة: ' . $wallet->id);
    }
}
