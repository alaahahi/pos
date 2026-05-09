<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Observers\SyncQueueModelObserver;
use App\Services\SyncQueueService;
use Tests\TestCase;

class SyncQueueModelObserverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['sync.queue_in_console' => true]);
    }

    public function test_created_dispatches_queue_insert_with_table_and_key(): void
    {
        $sync = $this->createMock(SyncQueueService::class);
        $sync->expects($this->once())
            ->method('queueInsert')
            ->with(
                'products',
                $this->anything(),
                $this->isType('array')
            )
            ->willReturn(true);

        $observer = new SyncQueueModelObserver($sync);
        $product = new Product;
        $product->forceFill([
            'id' => 'test-uuid',
            'name' => 'ObserverTest',
        ]);

        $observer->created($product);
    }

    public function test_deleted_dispatches_queue_delete(): void
    {
        $sync = $this->createMock(SyncQueueService::class);
        $sync->expects($this->once())
            ->method('queueDelete')
            ->with('products', $this->anything())
            ->willReturn(true);

        $observer = new SyncQueueModelObserver($sync);
        $product = new Product;
        $product->forceFill(['id' => 'del-uuid', 'name' => 'X']);

        $observer->deleted($product);
    }
}
