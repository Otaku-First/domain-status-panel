<?php

namespace App\Exceptions\Domain;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DomainNotFoundException extends Exception
{
    public function __construct(int $domainId)
    {
        parent::__construct("Domain with ID {$domainId} not found.");
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 404);
    }
}