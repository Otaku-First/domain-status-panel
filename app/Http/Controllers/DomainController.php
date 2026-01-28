<?php

namespace App\Http\Controllers;

use App\DTO\Domain\CreateDomainDTO;
use App\DTO\Domain\UpdateDomainDTO;
use App\Http\Resources\DomainResource;
use App\Services\DomainService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DomainController extends Controller
{
    public function __construct(
        private readonly DomainService $domainService,
    ) {}

    public function index(Request $request): Response
    {
        $domains = $this->domainService->getAllForUser($request->user());

        return Inertia::render('Dashboard', [
            'domains' => DomainResource::collection($domains)->resolve(),
        ]);
    }

    public function store(Request $request, CreateDomainDTO $data): RedirectResponse
    {
        $this->domainService->create($data, $request->user());

        return redirect()
            ->route('dashboard')
            ->with('success', 'Domain created successfully.');
    }

    public function show(Request $request, int $id): DomainResource
    {
        $domain = $this->domainService->getById($id, $request->user());

        return new DomainResource($domain);
    }

    public function update(Request $request, int $id, UpdateDomainDTO $data): RedirectResponse
    {
        $this->domainService->update($id, $data, $request->user());

        return redirect()
            ->route('dashboard')
            ->with('success', 'Domain updated successfully.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->domainService->delete($id, $request->user());

        return redirect()
            ->route('dashboard')
            ->with('success', 'Domain deleted successfully.');
    }

    public function toggleActive(Request $request, int $id): RedirectResponse
    {
        $domain = $this->domainService->toggleActive($id, $request->user());

        $status = $domain->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('dashboard')
            ->with('success', "Domain {$status} successfully.");
    }
}
