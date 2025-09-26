<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Decoration;

class DecorationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $decorations = [
            [
                'name' => 'ديكور عيد ميلاد كلاسيكي',
                'description' => 'ديكور جميل لعيد الميلاد مع بالونات ملونة وورود',
                'type' => 'birthday',
                'currency' => 'dinar',
                'base_price_dinar' => 150000,
                'base_price_dollar' => 100,
                'duration_hours' => 4,
                'team_size' => 3,
                'is_active' => true,
                'pricing_details' => [
                    'materials' => 50000,
                    'labor' => 80000,
                    'transportation' => 20000
                ],
                'included_items' => ['بالونات', 'ورود', 'شموع', 'لافتة'],
                'optional_items' => ['كيك', 'هدايا', 'موسيقى']
            ],
            [
                'name' => 'ديكور زفاف فاخر',
                'description' => 'ديكور راقي للزفاف مع زهور طبيعية وإضاءة رومانسية',
                'type' => 'wedding',
                'currency' => 'dollar',
                'base_price_dinar' => 2000000,
                'base_price_dollar' => 1200,
                'duration_hours' => 8,
                'team_size' => 6,
                'is_active' => true,
                'pricing_details' => [
                    'materials' => 800000,
                    'labor' => 1000000,
                    'transportation' => 200000
                ],
                'included_items' => ['زهور طبيعية', 'إضاءة LED', 'ستائر', 'كراسي'],
                'optional_items' => ['موسيقى', 'تصوير', 'تأجير ملابس']
            ],
            [
                'name' => 'ديكور تخرج جامعي',
                'description' => 'ديكور احتفالي للتخرج مع ألوان الجامعة',
                'type' => 'graduation',
                'currency' => 'dinar',
                'base_price_dinar' => 200000,
                'base_price_dollar' => 130,
                'duration_hours' => 3,
                'team_size' => 2,
                'is_active' => true,
                'pricing_details' => [
                    'materials' => 80000,
                    'labor' => 100000,
                    'transportation' => 20000
                ],
                'included_items' => ['لافتات', 'بالونات', 'شهادات', 'هدايا'],
                'optional_items' => ['تصوير', 'موسيقى', 'طعام']
            ],
            [
                'name' => 'ديكور حفلة ولادة',
                'description' => 'ديكور جميل لحفلة الولادة مع ألوان ناعمة',
                'type' => 'baby_shower',
                'currency' => 'dinar',
                'base_price_dinar' => 180000,
                'base_price_dollar' => 120,
                'duration_hours' => 5,
                'team_size' => 4,
                'is_active' => true,
                'pricing_details' => [
                    'materials' => 70000,
                    'labor' => 90000,
                    'transportation' => 20000
                ],
                'included_items' => ['بالونات', 'ورود', 'هدايا', 'كيك'],
                'optional_items' => ['تصوير', 'موسيقى', 'ألعاب']
            ],
            [
                'name' => 'ديكور شركات احترافي',
                'description' => 'ديكور احترافي للمؤتمرات والاجتماعات',
                'type' => 'corporate',
                'currency' => 'dollar',
                'base_price_dinar' => 1500000,
                'base_price_dollar' => 900,
                'duration_hours' => 6,
                'team_size' => 5,
                'is_active' => true,
                'pricing_details' => [
                    'materials' => 600000,
                    'labor' => 700000,
                    'transportation' => 200000
                ],
                'included_items' => ['لافتات', 'طاولات', 'كراسي', 'إضاءة'],
                'optional_items' => ['شاشات', 'ميكروفونات', 'تصوير']
            ],
            [
                'name' => 'ديكور ديني تقليدي',
                'description' => 'ديكور ديني للاحتفالات الدينية',
                'type' => 'religious',
                'currency' => 'dinar',
                'base_price_dinar' => 120000,
                'base_price_dollar' => 80,
                'duration_hours' => 3,
                'team_size' => 2,
                'is_active' => true,
                'pricing_details' => [
                    'materials' => 50000,
                    'labor' => 60000,
                    'transportation' => 10000
                ],
                'included_items' => ['شموع', 'ورود', 'لافتات', 'أقمشة'],
                'optional_items' => ['موسيقى دينية', 'طعام', 'هدايا']
            ],
            [
                'name' => 'ديكور كشف جنس المولود',
                'description' => 'ديكور مميز لكشف جنس المولود مع مفاجآت',
                'type' => 'gender_reveal',
                'currency' => 'dinar',
                'base_price_dinar' => 250000,
                'base_price_dollar' => 160,
                'duration_hours' => 4,
                'team_size' => 3,
                'is_active' => true,
                'pricing_details' => [
                    'materials' => 100000,
                    'labor' => 120000,
                    'transportation' => 30000
                ],
                'included_items' => ['بالونات ملونة', 'كيك مفاجأة', 'هدايا', 'تصوير'],
                'optional_items' => ['موسيقى', 'ألعاب', 'طعام']
            ]
        ];

        foreach ($decorations as $decoration) {
            Decoration::create($decoration);
        }

        $this->command->info('تم إنشاء ' . count($decorations) . ' ديكور تجريبي بنجاح!');
    }
}
