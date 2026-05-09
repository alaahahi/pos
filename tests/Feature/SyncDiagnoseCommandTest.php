<?php

namespace Tests\Feature;

use Tests\TestCase;

class SyncDiagnoseCommandTest extends TestCase
{
    public function test_sync_diagnose_command_exits_successfully(): void
    {
        $this->artisan('sync:diagnose')
            ->assertExitCode(0);
    }
}
