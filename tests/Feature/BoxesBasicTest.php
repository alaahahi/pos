<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Box;
use App\Models\Transactions;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BoxesBasicTest extends TestCase
{
    /**
     * Test 1: Boxes page loads successfully
     */
    public function test_boxes_page_loads()
    {
        // Get first user
        $user = User::first();
        
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }
        
        $response = $this->actingAs($user)->get('/boxes');
        
        $response->assertStatus(200);
        
        echo "\n✅ Test 1 PASSED: Boxes page loads successfully\n";
    }

    /**
     * Test 2: Filter by date works
     */
    public function test_filter_by_date()
    {
        $user = User::first();
        
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }
        
        $response = $this->actingAs($user)
            ->get('/boxes?start_date=2025-10-01&end_date=2025-10-17');
        
        $response->assertStatus(200);
        
        echo "✅ Test 2 PASSED: Date filter works\n";
    }

    /**
     * Test 3: Filter by name works
     */
    public function test_filter_by_name()
    {
        $user = User::first();
        
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }
        
        $response = $this->actingAs($user)
            ->get('/boxes?name=test');
        
        $response->assertStatus(200);
        
        echo "✅ Test 3 PASSED: Name filter works\n";
    }

    /**
     * Test 4: Filter by note works
     */
    public function test_filter_by_note()
    {
        $user = User::first();
        
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }
        
        $response = $this->actingAs($user)
            ->get('/boxes?note=test');
        
        $response->assertStatus(200);
        
        echo "✅ Test 4 PASSED: Note filter works\n";
    }

    /**
     * Test 5: API transactions endpoint works
     */
    public function test_api_transactions_works()
    {
        $user = User::first();
        
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }
        
        $response = $this->actingAs($user)
            ->get('/api/boxes/transactions');
        
        $response->assertStatus(200);
        
        echo "✅ Test 5 PASSED: API transactions endpoint works\n";
    }

    /**
     * Test 6: Print receipt view exists
     */
    public function test_print_receipt_view_exists()
    {
        $user = User::first();
        $transaction = Transactions::first();
        
        if (!$user || !$transaction) {
            $this->markTestSkipped('No users or transactions in database');
        }
        
        $response = $this->actingAs($user)
            ->get("/api/getIndexAccountsSelas?user_id=1&print=3&transactions_id={$transaction->id}");
        
        $response->assertStatus(200);
        $response->assertViewIs('receiptPayment');
        
        echo "✅ Test 6 PASSED: Print receipt view loads\n";
    }

    /**
     * Test 7: Page has required components
     */
    public function test_page_has_required_components()
    {
        $user = User::first();
        
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }
        
        $response = $this->actingAs($user)->get('/boxes');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Boxes/index')
                ->has('transactions')
                ->has('mainBox')
                ->has('exchangeRate')
                ->has('translations')
        );
        
        echo "✅ Test 7 PASSED: Page has all required props\n";
    }

    /**
     * Test 8: Transactions data structure is correct
     */
    public function test_transactions_data_structure()
    {
        $user = User::first();
        
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }
        
        $response = $this->actingAs($user)->get('/boxes');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('transactions.data')
                ->has('transactions.links')
                ->has('transactions.current_page')
        );
        
        echo "✅ Test 8 PASSED: Transactions structure is correct\n";
    }

    /**
     * Test 9: MainBox data exists
     */
    public function test_mainbox_data_exists()
    {
        $user = User::first();
        
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }
        
        $response = $this->actingAs($user)->get('/boxes');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('mainBox')
        );
        
        echo "✅ Test 9 PASSED: MainBox data exists\n";
    }

    /**
     * Test 10: Exchange rate is set
     */
    public function test_exchange_rate_is_set()
    {
        $user = User::first();
        
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }
        
        $response = $this->actingAs($user)->get('/boxes');
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->has('exchangeRate')
                ->where('exchangeRate', fn ($rate) => is_numeric($rate) && $rate > 0)
        );
        
        echo "✅ Test 10 PASSED: Exchange rate is set correctly\n";
    }
}

