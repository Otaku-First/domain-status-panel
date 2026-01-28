<?php

namespace App\Jobs;

use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DispatchDomainChecksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Dispatcher job doesn't need retries - if it fails, it's a serious issue.
     */
    public int $tries = 1;

    /**
     * Timeout for dispatching jobs (should be quick).
     */
    public int $timeout = 60;

    public function handle(): void
    {
        $domains = Domain::needsCheck()->get();

        if ($domains->isEmpty()) {
            Log::debug('No domains need checking');
            return;
        }

        Log::info("Dispatching checks for {$domains->count()} domains");

        foreach ($domains as $domain) {
            CheckDomainJob::dispatch($domain);
        }
    }
}