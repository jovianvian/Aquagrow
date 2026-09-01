<?php

namespace Tests\Unit;

use App\Jobs\QueueProbeJob;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QueueProbeJobTest extends TestCase
{
    public function test_probe_job_is_serializable_and_writes_its_marker(): void
    {
        Cache::spy();
        $job = new QueueProbeJob('test-probe');

        unserialize(serialize($job))->handle();

        Cache::shouldHaveReceived('put')
            ->once()
            ->with('health:queue-probe:test-probe', 'processed', \Mockery::type('DateTimeInterface'));
    }
}
