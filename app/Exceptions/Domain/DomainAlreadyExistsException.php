<?php

namespace App\Exceptions\Domain;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DomainAlreadyExistsException extends Exception
{
    public function __construct(private readonly string $hostname)
    {
        parent::__construct("Domain '{$hostname}' already exists.");
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
            ], 409);
        }

        return redirect()
            ->back()
            ->withInput()
            ->withErrors(['hostname' => "Domain '{$this->hostname}' already exists."]);
    }
}