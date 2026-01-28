<?php

namespace App\Console\Commands;

use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use App\Services\DomainCheckerService;
use Illuminate\Console\Command;

class CheckDomainsCommand extends Command
{
    protected $signature = 'domains:check
                            {--domain= : Check specific domain by ID}
                            {--sync : Run synchronously without queue}
                            {--all : Check all active domains regardless of interval}';

    protected $description = 'Check domains for availability';

    public function handle(DomainCheckerService $checker): int
    {
        $domainId = $this->option('domain');
        $sync = $this->option('sync');
        $all = $this->option('all');

        if ($domainId) {
            $domain = Domain::find($domainId);

            if (! $domain) {
                $this->error("Domain with ID {$domainId} not found.");

                return self::FAILURE;
            }

            $this->checkDomain($domain, $checker, $sync);

            return self::SUCCESS;
        }

        $domains = $all
            ? Domain::active()->get()
            : Domain::needsCheck()->get();

        if ($domains->isEmpty()) {
            $this->info('No domains to check.');

            return self::SUCCESS;
        }

        $this->info("Checking {$domains->count()} domains...");

        $bar = $this->output->createProgressBar($domains->count());
        $bar->start();

        foreach ($domains as $domain) {
            $this->checkDomain($domain, $checker, $sync);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done!');

        return self::SUCCESS;
    }

    private function checkDomain(Domain $domain, DomainCheckerService $checker, bool $sync): void
    {
        if ($sync) {
            $check = $checker->check($domain);
            $this->line(" {$domain->hostname}: {$check->result->label()} ({$check->response_time_ms}ms)");
        } else {
            CheckDomainJob::dispatch($domain);
            $this->line(" {$domain->hostname}: Job dispatched");
        }
    }
}