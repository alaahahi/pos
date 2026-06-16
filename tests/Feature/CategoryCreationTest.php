<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CategoryCreationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->seed(\Database\Seeders\UserRolePermissionSeeder::class);

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'category-test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('admin');
    }

    /** @test */
    public function can_create_category_with_uuid_and_list_it(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'name' => 'تصنيف تجريبي',
                'description' => 'وصف',
                'color' => '#667eea',
                'icon' => 'bi bi-tag',
                'sort_order' => 1,
                'is_active' => true,
            ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');

        $category = Category::where('name', 'تصنيف تجريبي')->first();
        $this->assertNotNull($category);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $category->id
        );

        $index = $this->actingAs($this->user)->get(route('categories.index'));
        $index->assertStatus(200);
        $index->assertInertia(fn ($page) => $page
            ->component('Categories/Index')
            ->has('categories', 1)
            ->where('categories.0.name', 'تصنيف تجريبي')
            ->where('categories.0.products_count', 0));
    }

    /** @test */
    public function sqlite_schema_repair_adds_missing_products_deleted_at_for_with_count(): void
    {
        $sqlitePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_categories_' . uniqid('', true) . '.sqlite';
        if (file_exists($sqlitePath)) {
            @unlink($sqlitePath);
        }
        touch($sqlitePath);

        config([
            'database.connections.sync_sqlite' => [
                'driver' => 'sqlite',
                'database' => $sqlitePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);
        DB::purge('sync_sqlite');

        $conn = DB::connection('sync_sqlite');
        $conn->statement('CREATE TABLE categories (
            id TEXT PRIMARY KEY NOT NULL,
            name TEXT NOT NULL,
            slug TEXT,
            sort_order INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1,
            created_at TEXT,
            updated_at TEXT,
            deleted_at TEXT
        )');
        $conn->statement("INSERT INTO categories (id, name, slug) VALUES ('cat-1', 'A', 'a')");
        $conn->statement('CREATE TABLE products (
            id INTEGER PRIMARY KEY,
            name TEXT,
            category_id TEXT
        )');
        $conn->statement("INSERT INTO products (id, name, category_id) VALUES (1, 'P', 'cat-1')");

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_06_16_120000_ensure_sqlite_categories_and_products_schema.php',
            '--database' => 'sync_sqlite',
            '--force' => true,
        ]);

        $this->assertTrue(Schema::connection('sync_sqlite')->hasColumn('products', 'deleted_at'));

        $count = Category::on('sync_sqlite')->withCount('products')->count();
        $this->assertSame(1, $count);

        DB::purge('sync_sqlite');
        @unlink($sqlitePath);
    }
}
