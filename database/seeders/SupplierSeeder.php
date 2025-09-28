<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'مورد قطع الغيار الأول',
                'phone' => '07901234567',
                'address' => 'بغداد، شارع الرشيد',
                'is_active' => true,
                'notes' => 'مورد موثوق لقطع غيار السيارات',
            ],
            [
                'name' => 'شركة الإلكترونيات المتقدمة',
                'phone' => '07901234568',
                'address' => 'أربيل، شارع 60 متر',
                'is_active' => true,
                'notes' => 'متخصصون في الأجهزة الإلكترونية',
            ],
            [
                'name' => 'مؤسسة المواد الغذائية',
                'phone' => '07901234569',
                'address' => 'البصرة، شارع الكورنيش',
                'is_active' => true,
                'notes' => 'مواد غذائية طازجة ومعلبة',
            ],
            [
                'name' => 'مورد الأثاث والمفروشات',
                'phone' => '07901234570',
                'address' => 'الموصل، شارع الجامعة',
                'is_active' => true,
                'notes' => 'أثاث منزلي ومكتبي عالي الجودة',
            ],
            [
                'name' => 'شركة الأدوات المكتبية',
                'phone' => '07901234571',
                'address' => 'السليمانية، شارع السوق',
                'is_active' => true,
                'notes' => 'جميع أنواع الأدوات المكتبية',
            ],
        ];

        foreach ($suppliers as $supplierData) {
            Supplier::firstOrCreate(
                ['phone' => $supplierData['phone']],
                $supplierData
            );
        }

        $this->command->info('تم إنشاء بيانات الموردين بنجاح!');
    }
}
