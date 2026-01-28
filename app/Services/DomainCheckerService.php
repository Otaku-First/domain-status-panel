<?php

namespace App\Services;

use App\Enums\CheckResult;
use App\Models\Domain;
use App\Models\DomainCheck;
use App\Notifications\DomainDownNotification;
use App\Notifications\DomainUpNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DomainCheckerService
{
    public function check(Domain $domain): DomainCheck
    {
        $previousCheck = $domain->latestCheck;
        $startTime = microtime(true);

        try {
            $response = Http::timeout($domain->timeout)
                ->connectTimeout($domain->timeout)
                ->withOptions([
                    'verify' => true,
                ])
                ->send($domain->method, $this->buildUrl($domain), [
                    'body' => $domain->body,
                ]);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            $result = $response->successful() ? CheckResult::SUCCESS : CheckResult::FAIL;

            $check = $this->createCheck($domain, [
                'result' => $result,
                'response_code' => $response->status(),
                'response_time_ms' => $responseTimeMs,
                'error_message' => $response->successful() ? null : $response->reason(),
            ]);

        } catch (ConnectionException $e) {
            $check = $this->handleException($domain, $e, $startTime);
        } catch (\Throwable $e) {
            $check = $this->handleException($domain, $e, $startTime);
        }

        $this->sendNotificationIfStatusChanged($domain, $check, $previousCheck);

        return $check;
    }

    private function buildUrl(Domain $domain): string
    {
        $hostname = $domain->hostname;

        if (! str_starts_with($hostname, 'http://') && ! str_starts_with($hostname, 'https://')) {
            $hostname = 'https://'.$hostname;
        }

        return $hostname;
    }

    private function handleException(Domain $domain, \Throwable $e, float $startTime): DomainCheck
    {
        $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);
        $result = $this->determineResultFromException($e);

        return $this->createCheck($domain, [
            'result' => $result,
            'response_code' => null,
            'response_time_ms' => $responseTimeMs,
            'error_message' => $e->getMessage(),
        ]);
    }

    private function determineResultFromException(\Throwable $e): CheckResult
    {
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return CheckResult::TIMEOUT;
        }

        if (str_contains($message, 'could not resolve') || str_contains($message, 'getaddrinfo')) {
            return CheckResult::DNS_ERROR;
        }

        if (str_contains($message, 'ssl') || str_contains($message, 'certificate')) {
            return CheckResult::SSL_ERROR;
        }

        return CheckResult::FAIL;
    }

    private function createCheck(Domain $domain, array $data): DomainCheck
    {
        $check = $domain->checks()->create([
            ...$data,
            'checked_at' => now(),
        ]);

        $domain->markAsChecked();

        return $check;
    }

    private function sendNotificationIfStatusChanged(Domain $domain, DomainCheck $currentCheck, ?DomainCheck $previousCheck): void
    {
        $wasUp = $previousCheck === null || $previousCheck->result->isSuccessful();
        $isUp = $currentCheck->result->isSuccessful();

        // No status change
        if ($wasUp === $isUp) {
            return;
        }

        // Load creator for notification
        $user = $domain->creator;
        if (! $user) {
            return;
        }

        if ($wasUp && ! $isUp) {
            // Domain went down
            $user->notify(new DomainDownNotification($domain, $currentCheck));
        } elseif (! $wasUp && $isUp) {
            // Domain recovered
            $user->notify(new DomainUpNotification($domain, $currentCheck));
        }
    }
}