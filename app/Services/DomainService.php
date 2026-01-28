<?php

namespace App\Services;

use App\DTO\Domain\CreateDomainDTO;
use App\DTO\Domain\UpdateDomainDTO;
use App\Exceptions\Domain\DomainAlreadyExistsException;
use App\Exceptions\Domain\DomainNotFoundException;
use App\Jobs\CheckDomainJob;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DomainService
{
    /**
     * Get all domains for a user with latest check.
     *
     * @return Collection<int, Domain>
     */
    public function getAllForUser(User $user): Collection
    {
        return Domain::query()
            ->where('created_by', $user->id)
            ->with('latestCheck')
            ->withCount('checks')
            ->orderBy('hostname')
            ->get();
    }

    /**
     * Get a domain by ID with checks.
     *
     * @throws DomainNotFoundException
     */
    public function getById(int $id, User $user): Domain
    {
        $domain = Domain::query()
            ->where('id', $id)
            ->where('created_by', $user->id)
            ->with(['latestCheck', 'checks' => fn ($q) => $q->latest('checked_at')->limit(50)])
            ->withCount('checks')
            ->first();

        if (! $domain) {
            throw new DomainNotFoundException($id);
        }

        return $domain;
    }

    /**
     * Create a new domain.
     *
     * @throws DomainAlreadyExistsException
     */
    public function create(CreateDomainDTO $data, User $user): Domain
    {
        // Check if domain already exists for this user
        $exists = Domain::query()
            ->where('hostname', $data->hostname)
            ->where('created_by', $user->id)
            ->exists();

        if ($exists) {
            throw new DomainAlreadyExistsException($data->hostname);
        }

        $domain = DB::transaction(function () use ($data, $user) {
            return Domain::create([
                'hostname' => $data->hostname,
                'method' => $data->method,
                'interval' => $data->interval,
                'timeout' => $data->timeout,
                'body' => $data->body,
                'is_active' => $data->is_active,
                'created_by' => $user->id,
            ]);
        });

        if ($domain->is_active) {
            CheckDomainJob::dispatch($domain);
        }

        return $domain;
    }

    /**
     * Update a domain.
     *
     * @throws DomainNotFoundException
     * @throws DomainAlreadyExistsException
     */
    public function update(int $id, UpdateDomainDTO $data, User $user): Domain
    {
        $domain = $this->getById($id, $user);

        // Check hostname uniqueness if changing
        if (! ($data->hostname instanceof \Spatie\LaravelData\Optional) && $data->hostname !== $domain->hostname) {
            $exists = Domain::query()
                ->where('hostname', $data->hostname)
                ->where('created_by', $user->id)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                throw new DomainAlreadyExistsException($data->hostname);
            }
        }

        DB::transaction(function () use ($domain, $data) {
            $domain->update(
                $data->toArray()
            );
        });

        return $domain->fresh(['latestCheck']);
    }

    /**
     * Delete a domain.
     *
     * @throws DomainNotFoundException
     */
    public function delete(int $id, User $user): void
    {
        $domain = $this->getById($id, $user);

        DB::transaction(function () use ($domain) {
            $domain->delete();
        });
    }

    /**
     * Toggle domain active status.
     *
     * @throws DomainNotFoundException
     */
    public function toggleActive(int $id, User $user): Domain
    {
        $domain = $this->getById($id, $user);

        $domain->update([
            'is_active' => ! $domain->is_active,
        ]);

        return $domain->fresh(['latestCheck']);
    }
}
