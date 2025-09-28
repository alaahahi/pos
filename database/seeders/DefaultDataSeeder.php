<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Box;
use App\Models\SystemConfig;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Expense;
use Illuminate\Support\Facades\Hash;

class DefaultDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@pos.com'],
            [
                'name' => 'مدير النظام',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        // Assign admin role
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        // Create default box
        Box::firstOrCreate(
            ['name' => 'الصندوق الرئيسي'],
            [
                'balance' => 1000000, // 1,000,000 IQD
                'balance_usd' => 1000, // 1,000 USD
                'is_active' => true,
            ]
        );

        // Create system configuration
        SystemConfig::firstOrCreate(
            ['id' => 1],
            [
                'first_title_ar' => 'نظام إدارة نقاط البيع',
                'first_title_kr' => 'POS Management System',
                'second_title_ar' => 'إدارة المخزون والفواتير',
                'second_title_kr' => 'Inventory & Invoices Management',
                'third_title_ar' => 'نظام شامل لإدارة الأعمال',
                'third_title_kr' => 'Comprehensive Business Management System',
                'exchange_rate' => 1500, // 1 USD = 1500 IQD
                'default_price_s' => json_encode([
                    'IQD' => 1000,
                    'USD' => 1
                ]),
                'default_price_p' => json_encode([
                    'IQD' => 10000,
                    'USD' => 10
                ]),
            ]
        );

        // Create sample products
        $products = [
            [
                'name' => 'منتج تجريبي 1',
                'barcode' => '1234567890123',
                'price' => 5000,
                'currency' => 'IQD',
                'quantity' => 100,
                'is_active' => true,
            ],
            [
                'name' => 'منتج تجريبي 2',
                'barcode' => '1234567890124',
                'price' => 10,
                'currency' => 'USD',
                'quantity' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'منتج تجريبي 3',
                'barcode' => '1234567890125',
                'price' => 7500,
                'currency' => 'IQD',
                'quantity' => 75,
                'is_active' => true,
            ],
        ];

        foreach ($products as $productData) {
            Product::firstOrCreate(
                ['barcode' => $productData['barcode']],
                $productData
            );
        }

        // Create sample customers
        $customers = [
            [
                'name' => 'زبون تجريبي 1',
                'phone' => '07901234567',
                'email' => 'customer1@example.com',
                'address' => 'بغداد، العراق',
            ],
            [
                'name' => 'زبون تجريبي 2',
                'phone' => '07901234568',
                'email' => 'customer2@example.com',
                'address' => 'أربيل، العراق',
            ],
            [
                'name' => 'زبون تجريبي 3',
                'phone' => '07901234569',
                'email' => 'customer3@example.com',
                'address' => 'البصرة، العراق',
            ],
        ];

        foreach ($customers as $customerData) {
            Customer::firstOrCreate(
                ['phone' => $customerData['phone']],
                $customerData
            );
        }

        // Create sample expenses
        $expenses = [
            [
                'title' => 'راتب الموظفين',
                'description' => 'رواتب شهرية للموظفين',
                'amount' => 500000,
                'currency' => 'IQD',
                'category' => 'salaries',
                'expense_date' => now()->subDays(5),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'إيجار المحل',
                'description' => 'إيجار شهري للمحل',
                'amount' => 200,
                'currency' => 'USD',
                'category' => 'shop_expenses',
                'expense_date' => now()->subDays(3),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'فاتورة الكهرباء',
                'description' => 'فاتورة الكهرباء الشهرية',
                'amount' => 150000,
                'currency' => 'IQD',
                'category' => 'utilities',
                'expense_date' => now()->subDays(1),
                'created_by' => $admin->id,
            ],
        ];

        foreach ($expenses as $expenseData) {
            Expense::firstOrCreate(
                [
                    'title' => $expenseData['title'],
                    'expense_date' => $expenseData['expense_date'],
                ],
                $expenseData
            );
        }

        $this->command->info('تم إنشاء البيانات الافتراضية بنجاح!');
        $this->command->info('المستخدم الافتراضي: admin@pos.com');
        $this->command->info('كلمة المرور: password');
    }
}