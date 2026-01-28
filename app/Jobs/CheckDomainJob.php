<?php

namespace App\Jobs;

use App\Models\Domain;
use App\Services\DomainCheckerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckDomainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * No retries - if domain doesn't respond, that's the result.
     * We don't want to mask failures with retries.
     */
    public int $tries = 1;

    /**
     * Job timeout = domain timeout + buffer.
     * Actual HTTP timeout is controlled by domain->timeout.
     */
    public int $timeout = 60;

    /**
     * Don't delete job on model missing - domain was deleted.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public Domain $domain,
    ) {
    }

    public function handle(DomainCheckerService $checker): void
    {
        Log::info("Checking domain: {$this->domain->hostname}");

        $check = $checker->check($this->domain);

        Log::info("Domain {$this->domain->hostname} check completed", [
            'result' => $check->result->value,
            'response_code' => $check->response_code,
            'response_time_ms' => $check->response_time_ms,
        ]);

        // TODO: Dispatch notification if status changed
    }

    public function tags(): array
    {
        return [
            'domain-check',
            'domain:'.$this->domain->id,
        ];
    }
}